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

class Info extends ModalComponent
{

  use WithFileUploads;

  #[Locked]
  public Conversation $conversation;

  public $group;


  public $description;

  // #[Validate('required', message: 'Please provide a group name.')]
  // #[Validate('max:120', message: 'Name cannot exceed 120 characters.')]
  public $groupName;


  public $photo;
  public $cover_url;


  protected $listeners=[
    "participantsCountUpdated"
  ];

  public $totalParticipants;


  public function participantsCountUpdated(int $newCount){

   // dd($newCount);
    return  $this->totalParticipants= $newCount;

  }





  private function setDefaultValues()
  {
    $this->description = $this->group?->description;
    $this->groupName = $this->group?->name;
    $this->cover_url = $this->conversation?->group?->cover_url ?? ($receiver?->cover_url ?? null);
  }


  public static function closeModalOnEscapeIsForceful(): bool
  {
      return false;
  }
  // public static function closeModalOnEscape(): bool
  // {
  //     return false;
  // }


  function updatedDescription($value)
  {

    abort_unless($this->conversation->isGroup(),405);
    // dd($value,str($value)->length() );

    $this->validate(
      ['description' => 'max:500|nullable'],
      ['description.max' => 'Description cannot exceed 500 characters.']
    );

    $this->conversation->group?->updateOrCreate(['conversation_id' => $this->conversation->id], ['description' => $value]);
  }

  /* Update Group name when for submittted */
  public function updateGroupName()
  {

    abort_unless($this->conversation->isGroup(),405);

    $this->validate(
      ['groupName' => 'required|max:120|nullable'],
      [
        'groupName.required' => 'The Group name cannot be empty',
        'groupName.max' => 'Group name cannot exceed 120 characters.'

      ]
    );

    $this->conversation->group?->updateOrCreate(['conversation_id' => $this->conversation->id], ['name' => $this->groupName]);

    $this->dispatch('refresh');
  }

  /**
   * Group Photo  Configuration
   */
  public function deletePhoto()
  {

    abort_unless($this->conversation->isGroup(),405);
    #delete photo from group  

    $this->group?->cover()?->delete();

    $this->reset('photo');
    $this->cover_url = null;

    $this->dispatch('refresh');


  }

  /**
   * Group Photo  Configuration
   */
  public function updatedPhoto($photo)
  {

    abort_unless($this->conversation->isGroup(),405);

    #validate
    $this->validate([
      'photo' => 'image|max:12024|nullable'
    ]);


    #create and save photo is present
    if ($photo) {

          #remove current photo
    $this->group?->cover?->delete();
      #save photo to disk 
      $path =  $photo->store(WireChat::storageFolder(), WireChat::storageDisk());
      $url = Storage::url($path);
      #create attachment
      $this->conversation->group?->cover()?->create([
        'file_path' => $path,
        'file_name' => basename($path),
        'original_name' => $photo->getClientOriginalName(),
        'mime_type' => $photo->getMimeType(),
        'url' =>  $url
      ]);

      $this->cover_url = $url;
    $this->reset('photo');

    $this->dispatch('refresh');

    }

  }



  /**
    * Delete chat  */

    function deleteChat()
    {
        abort_unless(auth()->check(), 401);


        abort_unless(auth()->user()->belongsToConversation($this->conversation),403);

        #delete conversation 
        $this->conversation->deleteFor(auth()->user());

        #redirect to chats page 
        $this->redirectRoute("wirechat");
    }


    function exitConversation()
    {
        abort_unless(auth()->check(), 401);

        $auth= auth()->user();

       //dd($auth->isOwnerOfConversation($this->conversation));
        #make sure owner if group cannot be removed from chat
        abort_if($auth->isOwnerOfConversation($this->conversation),403,"Owner cannot exit conversation");

        #delete conversation 
        $auth->exitConversation($this->conversation);

        #redirect to chats page 
        $this->redirectRoute("wirechat");
    }


    public function placeholder()
    {
        return <<<'HTML'
        <div>
            <!-- Loading spinner... -->
            <x-wirechat::loading-spin class="m-auto" />
        </div>
        HTML;
    }
  
  function mount()
  {
    
    abort_if(empty($this->conversation),404);

    abort_unless(auth()->check(), 401);
    abort_unless(auth()->user()->belongsToConversation($this->conversation),403);

    
    $this->conversation = $this->conversation->load('group.conversation', 'group.cover')->loadCount('participants');

  
    $this->totalParticipants= $this->conversation->participants_count;
    $this->group = $this->conversation->group;

    $this->setDefaultValues();
  }

  public function render()
  {

    // Pass data to the view
    return view('wirechat::livewire.info.info', [
      'receiver' => $this->conversation->getReceiver(),
      'participant'=>$this->conversation->participant(auth()->user())
    ]);
  }
}
