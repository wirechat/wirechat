<?php

namespace Namu\WireChat\Livewire\Chats;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Helpers\MorphClassResolver;
use Namu\WireChat\Livewire\Concerns\Widget;
use Namu\WireChat\Models\Conversation;

/**
 * Chats Component
 *
 * Handles chat conversations, search, and real-time updates.
 *
 * @property \Illuminate\Contracts\Auth\Authenticatable|null $auth
 */
class Chats extends Component
{
    use Widget;

    /**
     * The search query.
     *
     * @var mixed
     */
    public $search;

    /**
     * The list of conversations.
     *
     * @var \Illuminate\Support\Collection|array
     */
    public $conversations = [];

    /**
     * Features
     */
    #[Locked]
    public bool $showNewChatModalButton;

    #[Locked]
    public bool $allowChatsSearch;

    #[Locked]
    public bool $showHomeRouteButton;

    #[Locked]
    public ?string $title;

    /**
     * Indicates if more conversations can be loaded.
     */
    public bool $canLoadMore = false;

    /**
     * The current page for pagination.
     *
     * @var int
     */
    public $page = 1;

    /**
     * The ID of the selected conversation.
     *
     * @var mixed
     */
    public $selectedConversationId;

    /**
     * Returns an array of event listeners.
     *
     * @return array
     */
    public function getListeners()
    {
        $user = $this->auth;
        $encodedType = MorphClassResolver::encode($user?->getMorphClass());
        $userId = $user?->getKey();

        // dd($encodedType,$userId);
        return [
            'refresh' => '$refresh',
            'hardRefresh',
            // Construct the channel name using the encoded type and user ID.
            "echo-private:participant.{$encodedType}.{$userId},.Namu\\WireChat\\Events\\NotifyParticipant" => 'refreshComponent',
        ];
    }

    /**
     * Forces the conversation list to reset as if it was newly opened.
     *
     * @return void
     */
    public function hardRefresh()
    {
        $this->conversations = collect();
        $this->reset(['page', 'canLoadMore']);
    }

    /**
     * Refreshes the chats by resetting the conversation list and pagination.
     *
     * @return void
     */
    #[On('refresh-chats')]
    public function refreshChats()
    {
        $this->conversations = collect();
        $this->reset(['page', 'canLoadMore']);
    }

    /**
     * Handle the 'chat-deleted' event.
     *
     * @param  mixed  $conversationId  The ID of the deleted conversation.
     * @return void
     */
    #[On('chat-deleted')]
    public function chatDeleted($conversationId)
    {
        $this->conversations = $this->conversations->reject(function ($conversation) use ($conversationId) {
            return $conversation->id === $conversationId;
        });
    }

    /**
     * Handle the 'chat-exited' event.
     *
     * @param  mixed  $conversationId  The ID of the exited conversation.
     * @return void
     */
    #[On('chat-exited')]
    public function chatExited($conversationId)
    {
        $this->conversations = $this->conversations->reject(function ($conversation) use ($conversationId) {
            return $conversation->id === $conversationId;
        });
    }

    /**
     * Refreshes the component if the event's conversation ID does not match the selected conversation.
     *
     * @param  array  $event  Event data containing message and conversation details.
     * @return void
     */
    public function refreshComponent($event)
    {
        if ($event['message']['conversation_id'] != $this->selectedConversationId) {
            $this->dispatch('refresh')->self();
        }
    }

    /**
     * Loads more conversations if available.
     *
     * @return void|null
     */
    public function loadMore()
    {
        // Check if no more conversations are available.
        if (! $this->canLoadMore) {
            return null;
        }

        // Load the next page.
        $this->page++;
    }

    /**
     * Resets conversations and pagination when the search query is updated.
     *
     * @param  mixed  $value  The new search query.
     * @return void
     */
    public function updatedSearch($value)
    {
        $this->conversations = []; // Clear previous results when a new search is made.
        $this->reset(['page', 'canLoadMore']);
    }

    /**
     * Loads conversations based on the current page and search filters.
     * Applies search filters and updates the conversations collection.
     *
     * @return void
     */
    protected function loadConversations()
    {
        $perPage = 10;
        $offset = ($this->page - 1) * $perPage;

        $additionalConversations = $this->auth->conversations()
            ->with([
                'lastMessage.sendable',
                'group.cover' => fn ($query) => $query->select('id', 'url', 'attachable_type', 'attachable_id', 'file_path'),
            ])
            ->when(trim($this->search ?? '') != '', fn ($query) => $this->applySearchConditions($query))
            ->when(trim($this->search ?? '') == '', function ($query) {
                /** @phpstan-ignore-next-line */
                return $query->withoutDeleted()->withoutBlanks();
            })
            ->latest('updated_at')
            ->skip($offset)
            ->take($perPage)
            ->get();

        // Set participants manually where needed
        $additionalConversations->each(function ($conversation) {
            if ($conversation->isPrivate() || $conversation->isSelf()) {
                // Manually load participants (only 2 expected in private/self)
                $participants = $conversation->participants()->select('id', 'participantable_id', 'participantable_type', 'conversation_id', 'conversation_read_at')->with('participantable')->get();
                $conversation->setRelation('participants', $participants);

                // Set peer and auth participants
                $conversation->auth_participant = $conversation->participant($this->auth);
                $conversation->peer_participant = $conversation->peerParticipant($this->auth);
            }
        });

        $this->canLoadMore = $additionalConversations->count() === $perPage;

        $this->conversations = collect($this->conversations)
            ->concat($additionalConversations)
            ->unique('id')
            ->sortByDesc('updated_at')
            ->values();
    }

    /**
     * Eager loads additional conversation relationships.
     *
     * @return void
     */
    public function hydrateConversations()
    {
        $this->conversations->map(function ($conversation) {
            // Only load participants manually if not a group
            if (! $conversation->isGroup()) {
                $participants = $conversation->participants()->select('id', 'participantable_id', 'participantable_type', 'conversation_id', 'conversation_read_at')->with(['participantable', 'actions'])->get();

                $conversation->setRelation('participants', $participants);

                // Set peer and auth participants
                $conversation->auth_participant = $conversation->participant($this->auth);
                $conversation->peer_participant = $conversation->peerParticipant(reference: $this->auth);
            }

            return $conversation->loadMissing([
                'lastMessage',
                'group.cover' => fn ($query) => $query->select('id', 'url', 'attachable_type', 'attachable_id', 'file_path'),
            ]);
        });
    }

    /**
     * Returns the authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    #[Computed(persist: true)]
    public function auth()
    {
        return auth()->user();
    }

    /**
     * Applies search conditions to the conversations query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     */
    protected function applySearchConditions($query): \Illuminate\Database\Eloquent\Builder
    {
        $searchableFields = WireChat::searchableFields();
        $groupSearchableFields = ['name', 'description'];
        $columnCache = [];

        // Use withDeleted to reverse withoutDeleted in order to make deleted chats appear in search.
        /** @phpstan-ignore-next-line */
        return $query->withDeleted()->where(function ($query) use ($searchableFields, $groupSearchableFields, &$columnCache) {
            // Search in participants' participantable fields.
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

            // Search in group fields directly.
            return $query->orWhereHas('group', function ($groupQuery) use ($groupSearchableFields) {
                $groupQuery->where(function ($query4) use ($groupSearchableFields) {
                    foreach ($groupSearchableFields as $field) {
                        $query4->orWhere($field, 'LIKE', '%'.$this->search.'%');
                    }
                });
            });
        });
    }

    /**
     * Checks if a column exists in the table and caches the result.
     *
     * @param  string  $table  The name of the table.
     * @param  string  $field  The column name.
     * @param  array  $columnCache  Reference to the cache array.
     * @return bool
     */
    protected function columnExists($table, $field, &$columnCache)
    {
        if (! isset($columnCache[$table])) {
            $columnCache[$table] = Schema::getColumnListing($table);
        }

        return in_array($field, $columnCache[$table]);
    }

    /**
     * Mounts the component and initializes conversations.
     *
     * @return void
     */
    public function mount(
        $showNewChatModalButton = null,
        $allowChatsSearch = null,
        $showHomeRouteButton = null,
        ?string $title = null,
    ) {
        // If a value is passed, use it; otherwise fallback to WireChat defaults.
        $this->showNewChatModalButton = isset($showNewChatModalButton) ? $showNewChatModalButton : WireChat::showNewChatModalButton();
        $this->allowChatsSearch = isset($allowChatsSearch) ? $allowChatsSearch : WireChat::allowChatsSearch();
        $this->showHomeRouteButton = isset($showHomeRouteButton) ? $showHomeRouteButton : ! $this->widget;
        $this->title = isset($title) ? $title : __('wirechat::chats.labels.heading');

        abort_unless(auth()->check(), 401);
        $this->selectedConversationId = request()->conversation;
        $this->conversations = collect();
    }

    /**
     * Loads conversations and renders the view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $this->loadConversations();

        return view('wirechat::livewire.chats.chats');
    }
}
