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
      "echo-private:participant." . MorphTypeHelper::deslash(get_class(auth()->user())) . "." . auth()->id() . ",.Namu\\WireChat\\Events\\MessageCreated" => '$refresh',
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

    // Clear previous results if there is a new search term
    if ($this->search) {
      $this->conversations = []; // Clear previous results when a new search is made
       #also reset page
       $this->reset(['page','canLoadMore']);
    }

    // Start the query with eager loading
    $additionalConversations = Conversation::with([
      'participants.participantable',    // Eager load participants and the related participantable model
      'lastMessage'                      // Eager load reads for each message to prevent individual checks
    ])->whereHas('participants', function ($query) {
      $query->where('participantable_id', auth()->id())
        ->where('participantable_type', get_class(auth()->user())); // Ensure correct type (User model)
    })
      ->when($this->search, function ($query) use ($searchableFields) {
        $query->where(function ($query) use ($searchableFields) {
          $query->whereHas('participants', function ($subquery) use ($searchableFields) {
            // Exclude the authenticated user
            $subquery->whereHas('participantable', function ($subquery2) use ($searchableFields) {
              $subquery2->whereAny($searchableFields, 'LIKE', '%' . $this->search . '%');
            });
          });
        });
      })
      ->latest('updated_at')
      ->paginate(10, ['*'], 'page', $this->page); // Load the next page of conversations

    // Check if cannot load more
    if (!$additionalConversations->hasMorePages()) {
      $this->canLoadMore = false;
    }

    // Merge the new conversations, ensure uniqueness, and sort by latest updated_at
    $this->conversations = collect($this->conversations)
      ->merge($additionalConversations->items())
      ->unique('id') // Ensure only unique conversations by comparing their IDs
      ->sortByDesc('updated_at') // Sort by updated_at in descending order
      ->values(); // Reset the array keys
  }




  public function mount()
  {

    abort_unless(auth()->check(), 401);
    $this->selectedConversationId = request()->chat;
  }


  public function render()
  {

    $this->loadConversations();
    // Pass data to the view
    return view('wirechat::livewire.chat.chats');
  }
}
