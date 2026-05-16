<?php

namespace Wirechat\Wirechat\Livewire\Chats;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Helpers\MorphClassResolver;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\InteractsWithUI;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;

/**
 * @property-read \Illuminate\Contracts\Auth\Authenticatable|null $auth
 * @property-read \Illuminate\Support\Collection<int, \Wirechat\Wirechat\Models\Conversation> $conversations
 * @property int|string|null $selectedConversationId
 * @property array<int, int|string> $conversationIds
 */
class Chats extends Component
{
    use HasPanel, InteractsWithUI, Widget;

    public $search;

    /**
     * Store ONLY ids (no models) to avoid ModelSynth refetching.
     *
     * @var array<int,int|string>
     */
    public array $conversationIds = [];

    public bool $canLoadMore = false;

    public $selectedConversationId;

    public ?string $pendingInviteToken = null;

    public ?string $pendingInvitePanel = null;

    // Cursor state for stable "Load more"
    // Cursor state for stable "Load more" (3-tuple: updated_at, created_at, id)
    public ?string $cursorUpdatedAt = null;

    public ?string $cursorCreatedAt = null;

    /** @var int|string|null */
    public $cursorId = null;

    #[Locked]
    public ?bool $createChatAction = null;

    #[Locked]
    public ?bool $chatsSearch = null;

    #[Locked]
    public ?bool $redirectToHomeAction = null;

    #[Locked]
    public ?string $heading = '';

    public function mount(): void
    {
        abort_unless(auth()->check(), 401);

        $this->selectedConversationId = request()->conversation;
        $this->pendingInviteToken = session()->pull('wirechat_pending_invite_token');
        $this->pendingInvitePanel = session()->pull('wirechat_pending_invite_panel');
        $this->conversationIds = [];
        $this->cursorUpdatedAt = null;
        $this->cursorCreatedAt = null;
        $this->cursorId = null;
        $this->canLoadMore = false;
    }

    public function getListeners(): array
    {
        $user = $this->auth;
        $encodedType = MorphClassResolver::encode($user?->getMorphClass());
        $userId = $user?->getKey();

        $listeners = [
            'refresh' => 'hardRefresh',
            'hardRefresh',
        ];

        if ($this->panel() === null) {
            \Log::warning('Wirechat: No panels registered in Chat Component');

            return $listeners;
        }

        $panelId = $this->panel()->getId();
        $channelName = "$panelId.participant.$encodedType.$userId";
        $listeners["echo-private:{$channelName},.Wirechat\\Wirechat\\Events\\NotifyParticipant"] = 'refreshComponent';

        if ($this->panel()->hasMessageRequests()) {
            $listeners["echo-private:{$channelName},.Wirechat\\Wirechat\\Events\\MessageRequestUpdated"] = 'refreshComponent';
        }

        return $listeners;
    }

    #[Computed]
    public function auth()
    {
        return auth()->user();
    }

    public function pendingMessageRequestsCount(): int
    {
        $user = $this->auth;

        if (! $user || ! $this->panel()->hasMessageRequests()) {
            return 0;
        }

        return Wirechat::messageRequestModelClass()::query()
            ->pending()
            ->whereHas('conversation')
            ->where(function ($query) use ($user) {
                $query->where(function ($recipientQuery) use ($user) {
                    $recipientQuery->whereRecipient($user);
                })->orWhere(function ($senderQuery) use ($user) {
                    $senderQuery->whereSender($user);
                });
            })
            ->count();
    }

    /**
     * Computed conversations:
     * - avoids ModelSynth per-model refetch
     * - stable "load more" via cursor paging
     */
    #[Computed]
    public function conversations()
    {
        if (empty($this->conversationIds)) {
            $this->loadConversationIds(); // initial load only
        }

        if (empty($this->conversationIds)) {
            return collect();
        }

        $user = $this->auth;
        $ids = $this->conversationIds;
        $positions = array_flip($ids);
        $table = Wirechat::conversationModelTable();

        $conversationQuery = Wirechat::conversationModelClass()::query()
            ->whereIn($table.'.id', $ids);

        if ($this->panel()->hasUnreadIndicator()) {
            if ($this->panel()->getUnreadIndicatorType() === UnreadIndicatorType::Count) {
                $conversationQuery->withUnreadCountFor($user);
            } else {
                $conversationQuery->withUnreadExistsFor($user);
            }
        }

        $conversations = $conversationQuery
            ->with([
                'lastMessage.participant.participantable',
                'group.cover' => fn ($q) => $q->select('id', 'url', 'attachable_type', 'attachable_id', 'file_path'),
                'participants' => fn ($q) => $q->select(
                    'id',
                    'participantable_id',
                    'participantable_type',
                    'conversation_id',
                    'conversation_read_at'
                )->with(['participantable', 'actions']),
            ])
            ->get()
            // Preserve the exact order of loaded ids (prevents swapping)
            ->sortBy(fn (Conversation $c) => $positions[$c->id] ?? PHP_INT_MAX)
            ->values();

        // Set peer/auth participants without extra queries (participants already loaded)
        $conversations->each(function (Conversation $conversation) use ($user) {
            if ($conversation->isPrivate() || $conversation->isSelf()) {
                $conversation->auth_participant = $conversation->participant($user);
                $conversation->peer_participant = $conversation->peerParticipant($user);
            }
        });

        return $conversations;
    }

    /**
     * Cursor-based IDs paging:
     * Appends older conversations; does not reshuffle existing items.
     */
    protected function loadConversationIds(): void
    {
        $auth = $this->auth;
        abort_if($auth == null, 401);

        $table = Wirechat::conversationModelTable();
        $perPage = 10;

        $baseQuery = Wirechat::conversationModel()->newQuery();
        $baseQuery->whereHasParticipant($auth->getKey(), $auth->getMorphClass());
        $baseQuery->where(function ($query) {
            $query->where('type', ConversationType::GROUP)
                ->orWhere('type', ConversationType::SELF)
                ->orWhere(function ($privateQuery) {
                    $privateQuery->where('type', ConversationType::PRIVATE)
                        ->has('participants', '=', 2);
                });
        });

        if (trim($this->search ?? '') !== '') {
            $baseQuery = $this->applySearchConditions($baseQuery);
        } else {
            $baseQuery->withoutDeleted()->withoutBlanks();
        }

        // deterministic ordering for cursor paging (3-tuple: updated_at, created_at, id)
        $baseQuery
            ->orderByDesc($table.'.updated_at')
            ->orderByDesc($table.'.created_at')
            ->orderByDesc($table.'.id');

        // If we already have a cursor, load older than it (3-tuple comparison)
        if ($this->cursorUpdatedAt !== null && $this->cursorCreatedAt !== null && $this->cursorId !== null) {
            $baseQuery->where(function ($q) use ($table) {
                $q->where($table.'.updated_at', '<', $this->cursorUpdatedAt)
                    ->orWhere(function ($q2) use ($table) {
                        $q2->where($table.'.updated_at', '=', $this->cursorUpdatedAt)
                            ->where($table.'.created_at', '<', $this->cursorCreatedAt);
                    })
                    ->orWhere(function ($q3) use ($table) {
                        $q3->where($table.'.updated_at', '=', $this->cursorUpdatedAt)
                            ->where($table.'.created_at', '=', $this->cursorCreatedAt)
                            ->where($table.'.id', '<', $this->cursorId);
                    });
            });
        }

        // Select id + updated_at + created_at so we can advance the cursor without another query
        /** @var \Illuminate\Database\Eloquent\Collection<int, Conversation> $rows */
        $rows = $baseQuery
            ->select([$table.'.id', $table.'.updated_at', $table.'.created_at'])
            ->take($perPage + 1)
            ->get();

        $this->canLoadMore = $rows->count() > $perPage;

        $rows = $rows->take($perPage);

        $newIds = $rows->pluck('id')->all();

        // Append only; stable
        $this->conversationIds = array_values(array_unique([
            ...$this->conversationIds,
            ...$newIds,
        ]));

        // Update cursor
        /** @var Conversation|null $last */
        $last = $rows->last();
        if ($last) {
            $this->cursorUpdatedAt = (string) $last->updated_at;
            $this->cursorCreatedAt = (string) $last->created_at;
            $this->cursorId = $last->id;
        }
    }

    public function loadMore(): void
    {
        if (! $this->canLoadMore) {
            return;
        }

        $this->loadConversationIds();
    }

    public function updatedSearch($value): void
    {
        $this->hardRefresh();
    }

    public function hardRefresh(): void
    {
        $this->conversationIds = [];
        $this->cursorUpdatedAt = null;
        $this->cursorCreatedAt = null;
        $this->cursorId = null;
        $this->canLoadMore = false;
    }

    #[On('refresh-chats')]
    public function refreshChats(): void
    {
        $this->hardRefresh();
    }

    #[On('chat-deleted')]
    public function chatDeleted($conversationId): void
    {
        $this->conversationIds = array_values(array_filter(
            $this->conversationIds,
            fn ($id) => (string) $id !== (string) $conversationId
        ));
    }

    #[On('chat-exited')]
    public function chatExited($conversationId): void
    {
        $this->chatDeleted($conversationId);
    }

    /**
     * Real-time notify: reset so latest conversation can jump to top.
     * Skip for message requests — those are handled by the Requests component.
     */
    public function refreshComponent($event): void
    {
        if (! empty($event['is_request'])) {
            return;
        }

        $this->hardRefresh();
    }

    protected function initialize(): void
    {
        $defaults = get_class_vars(static::class);

        if ($this->heading === $defaults['heading']) {
            $this->heading = $this->panel()?->getHeading();
        }

        if ($this->createChatAction === null) {
            $this->createChatAction = $this->panel()?->hasCreateChatAction();
        }

        if ($this->chatsSearch === null) {
            $this->chatsSearch = $this->panel()?->hasChatsSearch();
        }

        if ($this->redirectToHomeAction === null) {
            $this->redirectToHomeAction = $this->widget
                ? false
                : $this->panel()?->hasRedirectToHomeAction();
        }
    }

    protected function applySearchConditions($query): \Illuminate\Database\Eloquent\Builder
    {
        $searchableFields = $this->panel()->getSearchableAttributes();
        $groupSearchableFields = ['name', 'description'];
        $columnCache = [];

        return $query->withDeleted()->where(function ($query) use ($searchableFields, $groupSearchableFields, &$columnCache) {
            $query->whereHas('participants', function ($subquery) use ($searchableFields, &$columnCache) {
                $subquery->whereHas('participantable', function ($query2) use ($searchableFields, &$columnCache) {
                    $query2->where(function ($query3) use ($searchableFields, &$columnCache) {
                        $table = $query3->getModel()->getTable();
                        foreach ($searchableFields as $field) {
                            if ($this->columnExists($table, $field, $columnCache)) {
                                $query3->orWhere($field, 'LIKE', '%'.$this->search.'%');
                            }
                        }
                    });
                });
            });

            return $query->orWhereHas('group', function ($groupQuery) use ($groupSearchableFields) {
                $groupQuery->where(function ($q) use ($groupSearchableFields) {
                    foreach ($groupSearchableFields as $field) {
                        $q->orWhere($field, 'LIKE', '%'.$this->search.'%');
                    }
                });
            });
        });
    }

    protected function columnExists($table, $field, &$columnCache): bool
    {
        if (! isset($columnCache[$table])) {
            $columnCache[$table] = Schema::getColumnListing($table);
        }

        return in_array($field, $columnCache[$table], true);
    }

    public function render()
    {
        $this->initialize();

        return view('wirechat::livewire.chats.chats', [
            'conversations' => $this->conversations,
        ]);
    }
}
