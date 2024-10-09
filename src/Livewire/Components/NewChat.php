<?php

namespace Namu\WireChat\Livewire\Components;
use Namu\WireChat\Livewire\Modal\ModalComponent ;

class NewChat extends ModalComponent
{

    public $users;
    public $searchUsers;
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



  public  function createConversation($id, string $class)
  {


    $model = app($class);

    $model = $model::find($id);



    if ($model) {
      $createdConversation =  auth()->user()->createConversationWith($model);

      if ($createdConversation) {
        $this->closeModal();
        return redirect()->route('wirechat.chat', [$createdConversation->id]);
      }
    }
  }



    public function render()
    {
        return view('wirechat::livewire.components.new-chat');
    }
}