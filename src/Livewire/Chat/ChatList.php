<?php

namespace Namu\WireChat\Livewire\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Namu\WireChat\Models\Conversation;

class ChatList extends Component
{


  public $search;


  // function searchUsers()
  // {

  //   dd(' Here we are');
  // }
  //protected $listeners = ['refresh' => '$refresh'];
  public $selectedConversationId;


  public function getListeners()
  {
      return [
        'refresh' => '$refresh',
        "echo-private:wirechat.".auth()->id().",.Namu\\WireChat\\Events\\MessageCreated" => '$refresh',
      ];
  }




  public static function getUnReadMessageDotColor(): string
  {

    $color = config('wirechat.theme', 'blue');

    return  'text-' . $color . '-500';
  }

  public static function getUnReadMessageBadgeColor(): string
  {

    $color = config('wirechat.theme', 'blue');

    return 'bg-' . $color . '-500/20';
  }




  function mount()
  {

    abort_unless(auth()->check(),401);
    $this->selectedConversationId = request()->chat;

  }



  public function render()
  {

    #get user searcable fiels
    $searchableFields =auth()->user()->getWireSearchableFields();

   // dd(empty($fields));
    #Load the authenticated user with their conversations and related sender and receiver models
    $user = auth()->user()->load('conversations.sender', 'conversations.receiver');

    #Query conversations where the authenticated user is either the sender or receiver
    $conversations = Conversation::where(function ($query)  {
      $query->where('receiver_id', auth()->id())
        ->orWhere('sender_id', auth()->id());
    })
      #Filter conversations based on sender or receiver name matching the search query
      ->where(function ($query)use($searchableFields)  {
        
        $query->whereHas('sender', function ($subquery) use($searchableFields) {
          $subquery->where('id', '<>', auth()->id())
            ->whereAny($searchableFields, 'LIKE', '%' . $this->search . '%');
        })
          ->orWhereHas('receiver', function ($subquery)use($searchableFields) {
            $subquery->where('id', '<>', auth()->id())
              ->whereAny($searchableFields, 'LIKE', '%' . $this->search . '%');
          });
      })
      #Order conversations by the latest updated_at timestamp
      ->latest('updated_at')
      #Retrieve the conversations
      ->get();

    #Pass data to the view
    return view('wirechat::livewire.chat.chat-list', [
      'conversations' => $conversations, // Pass filtered conversations
      'unReadMessagesCount' => $user->getUnReadCount(), // Get unread messages count for the authenticated user
      'authUser' => $user // Pass authenticated user data
    ]);
  }
}
