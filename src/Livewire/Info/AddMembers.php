<?php

namespace Namu\WireChat\Livewire\Info;

use App\Models\User;
use App\Notifications\TestNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
//use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithPagination;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Jobs\BroadcastMessage;
use Namu\WireChat\Livewire\Modals\ModalComponent;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Scopes\WithoutClearedScope;

class AddMembers extends ModalComponent
{

  use WithFileUploads;

  #[Locked]
  public Conversation $conversation;

  public $group;



  public $users;
  public $search;
  public $selectedMembers;

  public $participants;

  #[Locked]
  public $newTotalCount;




  public static function closeModalOnClickAway(): bool
  {

    return false;
  }


  public static function closeModalOnEscape(): bool
  {

    return false;
  }

  public static function closeModalOnEscapeIsForceful(): bool
  {
      return false;
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
  public function toggleMember($id, string $class)
  {

    //exist operation if is already a participant/member
    $isAlreadyAParticipant =  $this->participants->contains(fn($participant) => $participant->participantable_id == $id && $participant->participantable_type == $class);
    if ($isAlreadyAParticipant) {
      return null;
    }


    $model = app($class)->find($id);

    if ($model) {
      if ($this->selectedMembers->contains(fn($member) => $member->id == $model->id && get_class($member) == get_class($model))) {
        // Remove member if they are already selected
        $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
          return $member->id == $id && get_class($member) == $class;
        });
      } else {

        #validte members count
        if ($this->newTotalCount >= WireChat::maxGroupMembers()) {
          return $this->dispatch('show-member-limit-error');
        }

        // Add member if they are not selected
        $this->selectedMembers->push($model);
      }


      #update total count 
      $this->newTotalCount = count($this->selectedMembers) + $this->participants->count();
    }
  }



  function save()
  {


    foreach ($this->selectedMembers as $key => $member) {

      #make sure user does not belong to conversation already 
      #mostly this is the auth user
      $alreadyExists =  $this->participants->contains(fn($participant) => $participant->participantable_id == $member->id && $participant->participantable_type == get_class($member));
      //$this->participants->where('participantable_id', $participant->id) ->where('participantable_type', get_class($participant)) ->exists();
      if (!$alreadyExists) {
        $this->conversation->addParticipant($member);
      }
    }

    $this->closeModal();

    $this->dispatch('refresh')->to(Info::class);
  }


  function mount()
  {
    abort_unless(auth()->check(), 401);
    abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);

    abort_if($this->conversation->isPrivate(), 403, 'Cannot add members to private conversation');


    // Load participants and get the count
    $this->conversation->loadCount('participants');
    $this->participants = $this->conversation->participants;

    // Dump the participants count
    //  dump($this->participants->count());

    $this->newTotalCount = $this->participants->count();
    $this->selectedMembers = collect();
  }


  public function render()
  {

    // Pass data to the view
    return view('wirechat::livewire.info.add-members', ['maxGroupMembers' => WireChat::maxGroupMembers()]);
  }
}
