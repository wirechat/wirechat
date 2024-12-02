<?php

namespace Namu\WireChat\Livewire\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Scopes\WithoutDeletedScope;

class Chats extends Component
{
    public $search;

    public $conversations = [];

    public bool $canLoadMore = false;

    public $page = 1;

    public $selectedConversationId;

    public function getListeners()
    {
        return [
            'refresh' => '$refresh',
            
            //   "echo-private:participant." .auth()->id() . ",.Namu\\WireChat\\Events\\NotifyParticipant" => '$refresh',
        ];
    }

    /**
     * loadmore conversation
     */
    public function loadMore()
    {
        //dd('cannot load more');

        //Check if no more conversations
        if (! $this->canLoadMore) {
            // dd('cannot load more');
            return null;
        }

        // Load the next page
        $this->page++;

    }

    public function updatedSearch($value)
    {

        // if ($value!=$this->search) {
        // code...
        $this->conversations = []; // Clear previous results when a new search is made
        $this->reset(['page', 'canLoadMore']);
        // }


    }

    /**
     * ----------------
     * Load conversations
     * Apply search filters & update $this->conversations
     */
    protected function loadConversations()
    {

        $additionalConversations = Conversation::query()
            ->tap(fn ($query) => $this->applyEagerLoading($query))
            ->whereHas('participants', function ($query) {
                $query->whereParticipantable(auth()->user());
            })
            ->when(trim($this->search ?? '') != '', fn ($query) => $this->applySearchConditions($query)) // Apply search
            ->when(trim($this->search ?? '') == '', fn ($query) => $query->withoutDeleted()->withoutBlanks()) // Without blanks & deletedByUser when no search
            ->latest('updated_at')
            ->paginate(10, ["*"], 'page', $this->page);

        $this->canLoadMore = $additionalConversations->hasMorePages();

        // Merge the new conversations, ensure uniqueness, and sort by latest updated_at
        $this->conversations = collect($this->conversations)
            ->concat($additionalConversations->items()) // Efficiently append the new conversations
            ->unique('id') // Ensure uniqueness by conversation ID
            ->sortByDesc(function ($conversation) {
                return $conversation->updated_at; // Sort by updated_at in descending order
            })
            ->values(); // Reset the array keys

    }

    //Helper method for applying search logic
    protected function applySearchConditions($query)
    {
        $searchableFields = WireChat::searchableFields();
        $groupSearchableFields = ['name', 'description'];
        $columnCache = [];

        $query->withoutGlobalScope(WithoutDeletedScope::class)->where(function ($query) use ($searchableFields, $groupSearchableFields, &$columnCache) {
            // Search in participants' participantable fields
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

            // Search in group fields directly
            $query->orWhereHas('group', function ($groupQuery) use ($groupSearchableFields) {
                $groupQuery->where(function ($query4) use ($groupSearchableFields) {
                    foreach ($groupSearchableFields as $field) {
                        $query4->orWhere($field, 'LIKE', '%'.$this->search.'%');
                    }
                });
            });
        });
    }

    //Eager loading relationships for better readability
    protected function applyEagerLoading($query)
    {
        return $query->with([
            'participants',
            'lastMessage.sendable',
            'group.cover',
           /// 'messages.sendable'
        ]);
    }

    //Helper function to check and cache column existence
    protected function columnExists($table, $field, &$columnCache)
    {
        if (! isset($columnCache[$table])) {
            $columnCache[$table] = Schema::getColumnListing($table);
        }

        return in_array($field, $columnCache[$table]);
    }


    public function mount()
    {
        abort_unless(auth()->check(), 401);
        $this->selectedConversationId = request()->conversation_id;
        //$this->loadConversations();
    }

    public function render()
    {

        $this->loadConversations();
        return view('wirechat::livewire.chat.chats');
    }
}
