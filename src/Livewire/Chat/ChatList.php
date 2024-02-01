<?php

namespace Namu\WireChat\Livewire\Chat;

use Livewire\Attributes\Layout;
use Livewire\Component;

class ChatList extends Component{


    function test()  {

       dd(' Here we are');
        
    }
    protected $listeners=['refresh'=>'$refresh'];



  public function render()
  {
       $conversations = auth()->user()->conversations()->latest('updated_at')->get();
       return view('wirechat::livewire.chat.chat-list',['conversations'=>$conversations]);
  }



}