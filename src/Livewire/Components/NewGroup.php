<?php

namespace Namu\WireChat\Livewire\Components;

use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Modal\ModalComponent;
use Namu\WireChat\Models\Attachment;

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




  /** 
   * Search For users to create conversations with
   */
  public function updatedSearch()
  {


    //Make sure it's not empty
    if (blank($this->search)) {

      $this->users = null;
    } else {

      $this->users = auth()->user()->searchUsers($this->search);
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
    $conversation= auth()->user()->createGroup($this->name,$this->description);




    #save photo
    if ($this->photo) {

      #save photo to disk 
      $path =  $this->photo->store(WireChat::storageFolder(), WireChat::storageDisk());

      #create attachment
      $conversation->group->cover()->create([
          'file_path' => $path,
          'file_name' => basename($path),
          'original_name' => $this->photo->getClientOriginalName(),
          'mime_type' => $this->photo->getMimeType(),
          'url' => url($path)
      ]);

    }

    #Add participants
    foreach ($this->selectedMembers as $key => $participant) {

      $conversation->addParticipant($participant);
    }


    #redirect
     return redirect()->route('wirechat.chat', [$conversation->id]);


    
  }




  public function mount()
  {

    abort_unless(auth()->check(), 401);
    abort_unless(WireChat::allowsNewGroupModal(), 503, 'The NewChat feature is currently unavailable.');

    $this->selectedMembers = collect();
  }


  public function render()
  {

    return view('wirechat::livewire.components.new-group', ['maxGroupMembers' => WireChat::maxGroupMembers()]);
  }
}
