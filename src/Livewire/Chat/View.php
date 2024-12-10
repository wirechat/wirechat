<?php

namespace Namu\WireChat\Livewire\Chat;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Namu\WireChat\Models\Conversation;

class View extends Component
{
    public $conversation_id;

    public $conversation;

    public function mount()
    {
        ///make sure user is authenticated
        abort_unless(auth()->check(), 401);

        //We remove deleted conversation incase the user decides to visit the delted conversation
        $this->conversation = Conversation::where('id', $this->conversation_id)->firstOrFail();

        // Check if the user belongs to the conversation
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);
    }

    #[Layout('wirechat::layouts.app')]
    #[Title('Chats')]
    public function render()
    {

        return <<<'BLADE'
              <div class="w-full h-[calc(100vh_-_0.0rem)]  flex rounded-lg" >
                  <div class=" hidden md:grid bg-inherit  dark:bg-inherit  relative w-full h-full md:w-[360px] lg:w-[400px] xl:w-[500px]  shrink-0 overflow-y-auto  ">

            
                     <livewire:chats/> 

                  </div>
                  
                  <main  class="  grid  w-full  grow  h-full relative overflow-y-auto"  style="contain:content">

                    
                    <livewire:chat  conversation="{{$conversation_id}}"/>

                  </main>


              </div>
      BLADE;
    }
}
