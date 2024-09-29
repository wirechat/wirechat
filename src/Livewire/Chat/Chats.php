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
  public $searchUsers;
  public $users;


  public $conversations = [];
  public bool $canLoadMore = true;
  public $page = 1;



  // function searchUsers()
  // {

  //   dd(' Here we are');
  // }
  //protected $listeners = ['refresh' => '$refresh'];
  public $selectedConversationId;


  public function getListeners()
  {
    //dd(MorphTypeHelper::deslash(get_class(auth()->user())));
    return [
      'refresh' => '$refresh',
      "echo-private:participant." . MorphTypeHelper::deslash(get_class(auth()->user())) . "." . auth()->id() . ",.Namu\\WireChat\\Events\\MessageCreated" => '$refresh',
    ];
  }


  /** 
   * Search For users to create conversations with
   */
  public function updatedSearchUsers()
  {


    //Make sure it's not empty
    if (blank($this->searchUsers)) {

      $this->users = null;
    } else {

      $this->users = auth()->user()->searchUsers($this->searchUsers);
    }
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
    }

    // Start the query with eager loading
    $additionalConversations = Conversation::with([
      'participants.participantable',    // Eager load participants and the related participantable model
      'lastMessage'                // Eager load reads for each message to prevent individual checks
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

    // Append the new conversations to the existing list
     // Append the new conversations to the existing list and ensure uniqueness
     $this->conversations = collect($this->conversations)
     ->merge($additionalConversations->items())
     ->unique('id') // Ensure only unique conversations by comparing their IDs
     ->values() // Reset the array keys
     ; // Convert the collection back to an array
  }




  public  function createConversation($id, string $class)
  {



    $model = app($class);

    $model = $model::find($id);






    if ($model) {
      $createdConversation =  auth()->user()->createConversationWith($model);

      if ($createdConversation) {
        return redirect()->route('wirechat.chat', [$createdConversation->id]);
      }
    }
  }



  function mount()
  {

    abort_unless(auth()->check(), 401);
    $this->selectedConversationId = request()->chat;
  }


  public function render()
  {

    // Get user searchable fields
    $searchableFields = WireChat::searchableFields();

    //dd($searchableFields);

    /// dump(auth()->user());

    // Load the authenticated user with their conversations and related participants
    $user = auth()->user();

    // Query conversations where the authenticated user is a participant
    // $conversations = $this->loadConversations();

    // dd('here');

    $this->loadConversations();

    //    dd(['conversations'=>$conversations]);

    // dd($conversations->first()->messages);
    // Pass data to the view
    return view('wirechat::livewire.chat.chats', [
      //'conversations'=>$conversations,
      //'unReadMessagesCount' => $user->getUnReadCount(), // Get unread messages count for the authenticated user
      'authUser' => $user // Pass authenticated user data
    ]);
  }
}
