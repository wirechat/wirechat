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
use Namu\WireChat\Jobs\BroadcastMessage;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Scopes\WithoutClearedScope;

class Info extends Component
{


    public Conversation $conversation;
    public $group;


    public $description;

    // #[Validate('required', message: 'Please provide a group name.')]
    // #[Validate('max:120', message: 'Name cannot exceed 120 characters.')]
    public $groupName;




    private function setDefaultValues()  {
      $this->description = $this->group?->description;
      $this->groupName = $this->group?->name;
    }



    function updatedDescription($value)  {

    // dd($value,str($value)->length() );

          $this->validate(
                ['description'=>'max:500|nullable'],
                ['description.max'=>'Description cannot exceed 500 characters.']
              );

          $this->conversation->group?->updateOrCreate(['conversation_id'=>$this->conversation->id],['description'=>$value]);
      
    }

    /* Update Group name when for submittted */
   public function updateGroupName()  {



      $this->validate(
            ['groupName'=>'required|max:120|nullable'],
            [
              'groupName.required'=>'The Group name cannot be empty',
              'groupName.max'=>'Group name cannot exceed 120 characters.'
            
            ]
          );

      $this->conversation->group?->updateOrCreate(['conversation_id'=>$this->conversation->id],['name'=>$this->groupName]);

      $this->dispatch('refresh');
  
}


    function mount( Conversation $conversation)  {
        
        abort_unless(auth()->check(),401);
        $this->conversation = $conversation;
        $this->group = $this->conversation->group;

        $this->setDefaultValues();


    }


   

  public function render()
  {

    // Pass data to the view
    return view('wirechat::livewire.info.info',['receiver'=>$this->conversation->getReceiver()]);
  }
}