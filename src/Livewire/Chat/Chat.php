<?php

namespace Namu\WireChat\Livewire\Chat;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class Chat extends Component{

  public $chat;

  public $conversation;



  function mount()  {


    ///make sure user is authenticated
    abort_unless(auth()->check(),401);

    $this->conversation= Conversation::findOrFail($this->chat);


    ///check if auth belongs to conversaiton
    // Check if the user belongs to the conversation
          $belongsToConversation = $this->conversation->participants()
          ->where('participantable_id', auth()->id())
          ->where('participantable_type', get_class(auth()->user()))
          ->exists();
          abort_unless($belongsToConversation, 403);

      
    //Mark as read 
    $this->conversation->markAsRead();
    ///mark messages belonging to receiver as read
    // Message::where('conversation_id',$this->conversation->id)
    //          ->where('receiver_id',auth()->id())
    //         // ->whereNull('read_at')
    //          ->update(['read_at'=>now()]);
    
  }


  #[Layout('wirechat::layouts.app')] 
  public function render()
  {

      return <<<'BLADE'
              <div class="w-full h-[calc(100vh_-_0.0rem)]  flex  bg-white dark:bg-gray-800  rounded-lg" >
                  <div class=" hidden lg:flex   relative w-full h-full md:w-[320px] xl:w-[400px]  shrink-0 overflow-y-auto  ">

                      @livewire('chat-list')
                  </div>
                  
                  <main class="  grid  w-full  grow  h-full relative overflow-y-auto"  style="contain:content">
                    
                    @livewire('chat-box',['conversation'=>$conversation->id])



                  </main>

                 
              
              </div>
      BLADE;
  }



}