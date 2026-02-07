<?php

namespace Wirechat\Wirechat\Livewire\Chats;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Wirechat\Wirechat\Helpers\MorphClassResolver;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Models\Conversation;

/**
 * @property-read \Illuminate\Contracts\Auth\Authenticatable|null $auth
 * @property-read \Illuminate\Support\Collection<int, \Wirechat\Wirechat\Models\Conversation> $conversations
 * @property int|string|null $selectedConversationId
 * @property array<int, int> $conversationIds
 */
class Chats extends Component
{
    use HasPanel, Widget;

    public $search;

    /**
     * Store ONLY ids (no models) to avoid ModelSynth refetching.
     */
    public array $conversationIds = [];

    public int $page = 1;

    public bool $canLoadMore = false;

    public $selectedConversationId;

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
        $this->conversationIds = [];
    }

    public function getListeners(): array
    {
        $user = $this->auth;
        $encodedType = MorphClassResolver::encode($user?->getMorphClass());
        $userId = $user?->getKey();

        $listeners = [
            'refresh' => '$refresh',
            'hardRefresh',
        ];

        if ($this->panel() === null) {
            \Log::warning('Wirechat: No panels registered in Chat Component');

            return $listeners;
        }

        $panelId = $this->panel()->getId();
        $channelName = "$panelId.participant.$encodedType.$userId";
        $listeners["echo-private:{$channelName},.Wirechat\\Wirechat\\Events\\NotifyParticipant"] = 'refreshComponent';

        return $listeners;
    }

    #[Computed(persist: true)]
    public function auth()
    {
        return auth()->user();
    }

    /**
     * Computed conversations:
     * - avoids ModelSynth per-model refetch
     * - uses pure Eloquent ordering (updated_at desc)
     */
    #[Computed]
    public function conversations()
    {
        $this->loadConversationIds();

        if (empty($this->conversationIds)) {
            return collect();
        }

        $user = $this->auth;
        $ids = $this->conversationIds;
        $table = (new Conversation)->getTable();

        $conversations = Conversation::query()
            ->whereIn($table.'.id', $ids)
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
            // ✅ no raw, no driver logic
            ->orderByDesc($table.'.updated_at')
            ->orderByDesc($table.'.id') // tiebreaker
            ->get();

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
     * IDs paging:
     * We still collect the latest ids from the user's relation query,
     * but we don't rely on "preserve this exact list order" anymore.
     */
    protected function loadConversationIds(): void
    {
        $table = (new Conversation)->getTable();
        $perPage = 10;
        $take = $this->page * $perPage;

        $baseQuery = $this->auth->conversations()
            ->with([]) // ids only
            ->when(trim($this->search ?? '') !== '', fn ($q) => $this->applySearchConditions($q))
            ->when(trim($this->search ?? '') === '', function ($q) {
                /** @phpstan-ignore-next-line */
                return $q->withoutDeleted()->withoutBlanks();
            })
            ->latest($table.'.updated_at');

        $ids = $baseQuery
            ->take($take + 1)
            ->pluck($table.'.id')
            ->all();

        $this->canLoadMore = count($ids) > $take;
        $this->conversationIds = array_slice($ids, 0, $take);
    }

    public function loadMore(): void
    {
        if (! $this->canLoadMore) {
            return;
        }

        $this->page++;
    }

    public function updatedSearch($value): void
    {
        $this->hardRefresh();
    }

    public function hardRefresh(): void
    {
        $this->conversationIds = [];
        $this->reset(['page', 'canLoadMore']);
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
            fn ($id) => (int) $id !== (int) $conversationId
        ));
    }

    #[On('chat-exited')]
    public function chatExited($conversationId): void
    {
        $this->chatDeleted($conversationId);
    }

    /**
     * Real-time notify: refresh ids so latest conversation jumps to top.
     */
    public function refreshComponent($event): void
    {
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
