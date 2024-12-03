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
        // Calculate the offset based on the current page and the number of items per page
        $perPage = 10; // Number of items per "page"
        $offset = ($this->page - 1) * $perPage;
    
        $additionalConversations = Conversation::query()
            ->with([
               // 'lastMessage' ,//=> fn($query) => $query->select('id', 'sendable_id','sendable_type', 'created_at'),
                         'messages',
               'lastMessage.attachment',
               'receiver.participantable',
               'group.cover' ,//=> fn($query) => $query->select('id', 'name'),
     
            ])
            ->whereHas('participants', fn($query) => $query->whereParticipantable(auth()->user()))
            ->when(trim($this->search ?? '') != '', fn($query) => $this->applySearchConditions($query)) // Apply search
            ->when(trim($this->search ?? '') == '', fn($query) => $query->withoutDeleted()->withoutBlanks()) // Without blanks & deleted
            ->latest('updated_at')
            ->skip($offset)
            ->take($perPage)
            ->get(); // Fetch only required fields
    
        //    dd($additionalConversations->first);
        // Check if there are more conversations for the next page
        $this->canLoadMore = $additionalConversations->count() === $perPage;
    
        // Merge and sort conversations
        $this->conversations = collect($this->conversations)
            ->concat($additionalConversations) // Append new conversations
            ->unique('id') // Ensure unique conversation IDs
            ->sortByDesc('updated_at') // Sort by updated_at in descending order
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
