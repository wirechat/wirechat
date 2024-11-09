<?php

namespace Namu\WireChat\Livewire\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Helpers\MorphTypeHelper;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Scopes\WithoutClearedScope;
use Namu\WireChat\Models\Scopes\WithoutDeletedScope;

class Chats extends Component
{


  public $search;
  public $conversations = [];
  public bool $canLoadMore = true;
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

    //Check if no more conversations
    if (!$this->canLoadMore) {
      return null;
    }

    // Load the next page
    $this->page++;
  }


  protected function loadConversations()
  {
    $searchableFields = WireChat::searchableFields();
    $groupSearchableFields = ['name', 'description']; // Define fields in the group to search
    
    // Initialize cache for column checks
    $columnCache = [];
    
    // Clear previous results if there is a new search term
    if ($this->search) {
        $this->conversations = []; // Clear previous results when a new search is made
        $this->reset(['page', 'canLoadMore']);
    }
    
    // Start the query with eager loading
    $additionalConversations = Conversation::with([
        'participants.participantable', // Eager load participants and the related participantable model
        'lastMessage',                  // Eager load last message
        'group.cover'                   // Eager load group cover
    ])->whereHas('participants', function ($query) {
        $query->where('participantable_id', auth()->id())
            ->where('participantable_type', get_class(auth()->user())); // Ensure correct type (User model)
    })
    ->when($this->search, function ($query) use ($searchableFields, $groupSearchableFields, &$columnCache) {
        $query->withoutGlobalScope(WithoutDeletedScope::class)->where(function ($query) use ($searchableFields, $groupSearchableFields, &$columnCache) {
            
            // Search in participants' participantable fields
            $query->whereHas('participants', function ($subquery) use ($searchableFields, &$columnCache) {
                $subquery->whereHas('participantable', function ($query2) use ($searchableFields, &$columnCache) {
                    $query2->where(function ($query3) use ($searchableFields, &$columnCache) {
                        $table = $query3->getModel()->getTable();
    
                        foreach ($searchableFields as $field) {
                            // Check if column existence is already cached for the table
                            if (!isset($columnCache[$table])) {
                                $columnCache[$table] = Schema::getColumnListing($table);
                            }
    
                            if (in_array($field, $columnCache[$table])) {
                                $query3->orWhere($field, 'LIKE', '%' . $this->search . '%');
                            }
                        }
                    });
                });
            });
    
           // Search in group fields directly
            $query->orWhereHas('group', function ($groupQuery) use ($groupSearchableFields) {
              $groupQuery->where(function ($query4) use ($groupSearchableFields) {
                  foreach ($groupSearchableFields as $field) {
                      $query4->orWhere($field, 'LIKE', '%' . $this->search . '%');
                  }
              });
          });
        });
    }, function ($query) {
      // When there's no search term, apply the 'withoutBlanks' scope to filter out blank conversations
      $query->withoutBlanks();
  })
    ->latest('updated_at') ->paginate(10, ['*'], 'page', $this->page); 
    // Load the next page of conversations
    // Check if cannot load more
    if (!$additionalConversations->hasMorePages()) {
      $this->canLoadMore = false;
    }

    // Ninj 

    // Merge the new conversations, ensure uniqueness, and sort by latest updated_at
    $this->conversations = collect($this->conversations)
                                        ->merge($additionalConversations->items())
                                        ->unique('id') //Ensure only unique conversations by comparing their IDs
                                        ->sortByDesc('updated_at') //Sort by updated_at in descending order
                                        ->values(); //Reset the array keys
  }




  public function mount()
  {

    abort_unless(auth()->check(), 401);
    $this->selectedConversationId = request()->conversation_id;
  }


  public function render()
  {

    $this->loadConversations();
    // Pass data to the view
    return view('wirechat::livewire.chat.chats');
  }
}
