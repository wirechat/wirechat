<?php

namespace Namu\WireChat\Livewire\Chat;

use Livewire\Attributes\Layout;
use Livewire\Component;

class ChatList extends Component{


    function test()  {

       dd(' Here we are');
        
    }
    protected $listeners=['refresh'=>'$refresh'];
    public $selectedConversationId;


    function mount()  {

      $this->selectedConversationId= request()->chat;
      
    }

    public static function getUnReadMessageDotColor() : string {

      $color= config('wirechat.theme','blue');

      return  'text-'.$color.'-500';

        
    }

    public static function getUnReadMessageBadgeColor() : string {

      $color= config('wirechat.theme','blue');

      return 'bg-'.$color.'-500/20';

        
    }



  public function render()
  {
       $conversations = auth()->user()->load('conversations')->conversations()->latest('updated_at')->get();
       return view('wirechat::livewire.chat.chat-list',['conversations'=>$conversations,'unReadMessagesCount'=>auth()->user()->unReadMessagesCount()]);
  }



}