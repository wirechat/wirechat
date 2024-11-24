<?php

namespace Namu\WireChat\Livewire\Components;

use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Modals\ModalComponent;

class NewGroup extends ModalComponent
{


  use WithFileUploads;

  public $users;
  public $search;
  public $selectedMembers;


  #[Validate('required', message: 'Please provide a group name.')]
  #[Validate('max:120', message: 'Name cannot exceed 120 characters.')]
  public $name;

  #[Validate('nullable')]
  #[Validate('max:500', message: 'Description cannot exceed 500 characters.')]
  public $description;


  #[Validate('image|max:12024|nullable')] // 1MB Max
  public $photo = null;

  public bool $showAddMembers = false;

  function deletePhoto()
  {

    //delete from tmp-folder
    // $this->removeUpload('photo', $this->photo->temporaryUrl());

    //delete photo
    $this->reset('photo');
  }



  public static function modalAttributes(): array
  {
      return [
          'closeOnEscape' => false,
          'closeOnEscapeIsForceful' => false,
          'destroyOnClose' => false,
          'closeOnClickAway'=>false
      ];
      
  }



  /** 
   * Search For users to create conversations with
   */
  public function updatedSearch()
  {

    //Make sure it's not empty
    if (blank($this->search)) {

      $this->users = null;
    } else {

      $this->users = auth()->user()->searchChatables($this->search);
    }
  }

  //Add members to selectedMembers list
  public  function addMember($id, string $class)
  {
    try {
      $model = app($class);

      $model = $model::find($id);

      if ($model) {
        if ($model && !$this->selectedMembers->contains($model)) {
          $this->selectedMembers->push($model);
        }
      }
    } catch (\Throwable $th) {


      throw $th;
    }
  }


  //Remove Member from   selectedMembers list
  public function removeMember($id, string $class)
  {
    // Filter out the member with the specified ID and class
    $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
      return $member->id == $id && get_class($member) == $class;
    });
  }

  public function toggleMember($id, string $class)
  {


    $model = app($class)->find($id);

    if ($model) {
      if ($this->selectedMembers->contains(fn($member) => $member->id == $model->id && get_class($member) == get_class($model))) {
        // Remove member if they are already selected
        $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
          return $member->id == $id && get_class($member) == $class;
        });
      } else {

        #validte members count
        if (count($this->selectedMembers)>=WireChat::maxGroupMembers()) {
          return $this->dispatch('show-member-limit-error');
        }

        // Add member if they are not selected
        $this->selectedMembers->push($model);
      
      }


    }
  }


  function validateDetails()
  {

    $this->validate();

    #if validation passed then show members to true
    $this->showAddMembers = true;
  }

  /**
   * Create group
  */
  public function create()  {

    $this->validate();


    #create group
    $conversation= auth()->user()->createGroup($this->name,$this->description,$this->photo);

    #Add participants
    foreach ($this->selectedMembers as $key => $participant) {

       #make sure user does not belong to conversation already 
       #mostly this is the auth user
       $alreadyExists =  $conversation->participants()->where('participantable_id', $participant->id) ->where('participantable_type', get_class($participant)) ->exists();
       if (!$alreadyExists) {
         $conversation->addParticipant($participant);
       }
    }

    #redirect to view route
     return redirect()->route( WireChat::viewRouteName(), [$conversation->id]);


  }




  public function mount()
  {

    abort_unless(auth()->check(), 401);
    abort_unless(auth()->user()->canCreateGroups(), 403, "You do not have permission to create groups.");

    $this->selectedMembers = collect();
  }


  public function render()
  {

    return view('wirechat::livewire.components.new-group', ['maxGroupMembers' => WireChat::maxGroupMembers()]);
  }
}
