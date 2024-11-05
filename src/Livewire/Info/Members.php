<?php

namespace Namu\WireChat\Livewire\Info;

use App\Models\User;
use App\Notifications\TestNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Jobs\BroadcastMessage;
use Namu\WireChat\Livewire\Modals\ModalComponent;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Models\Scopes\WithoutClearedScope;

class Members extends ModalComponent
{

    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public Conversation $conversation;

    public $group;


    protected $page = 1;
    public $users;
    public $search;
    public $selectedMembers;

    public $participants;
    public $canLoadMore;

    #[Locked]
    public $newTotalCount;

    protected $listeners=[
        'refresh'=>'$refresh'
    ];




    public static function closeModalOnClickAway(): bool
    {

        return true;
    }


    public static function closeModalOnEscape(): bool
    {

        return true;
    }






    public function updatedSearch($value)
    {
        $this->page = 1; // Reset page number when search changes
        $this->participants = collect(); // Reset to empty collection


        $this->loadParticipants();
    }


    /**
     * Actions
     */

    function sendMessage(Participant $participant)
    {

        abort_unless(auth()->check(), 401);

        $conversation =  auth()->user()->createConversationWith($participant->participantable);

        return redirect()->route('wirechat.chat', [$conversation->id]);
    }



    /**
     * Admin actions */
    function dismissAdmin(Participant $participant)
    {
        $this->toggleAdmin($participant);
    }
    function makeAdmin(Participant $participant)
    {
        $this->toggleAdmin($participant);
    }

    private function toggleAdmin(Participant $participant)
    {

        abort_unless(auth()->check(), 401);

        #abort if user does not belong to conversation
        abort_unless($participant->participantable->belongsToConversation($this->conversation), 403, 'This user does not belong to conversation');

        #abort if user participants is owner
        abort_if($participant->isOwner(), 403, "Owner role cannot be changed");

        #toggle
        if ($participant->isAdmin()) {
            $participant->update(['role' => ParticipantRole::PARTICIPANT]);
        } else {
            $participant->update(['role' => ParticipantRole::ADMIN]);
        }
        $this->dispatch('refresh')->self();

    }

    protected function loadParticipants()
    {
        $searchableFields = WireChat::searchableFields();
        $columnCache = []; // Initialize cache for column checks
        // Check if $this->participants is initialized
        $this->participants = $this->participants ?? collect();

        $additionalParticipants = Participant::where('conversation_id', $this->conversation->id)
            ->with('participantable')
            ->when($this->search, function ($query) use ($searchableFields, &$columnCache) {

                $query->whereHas('participantable', function ($query2) use ($searchableFields, &$columnCache) {
                    $query2->where(function ($query3) use ($searchableFields, &$columnCache) {
                        $table = $query3->getModel()->getTable();

                        foreach ($searchableFields as $field) {
                            // Check if column existence is already cached for the table
                            if (!isset($columnCache[$table])) {
                                $columnCache[$table] = Schema::getColumnListing($table);
                            }

                            if (in_array($field, $columnCache[$table])) {
                                $query3->orWhere($field, 'LIKE', '%' . $this->search . '%');
                            }
                        }
                    });
                });
            })
            ->latest('updated_at')
            ->paginate(10, ['*'], 'page', $this->page);

        // Check if cannot load more
        $this->canLoadMore = $additionalParticipants->hasMorePages();


        // Merge current participants with the additional ones
        // Merge current participants with the additional ones and remove duplicates
        $this->participants = $this->participants->merge($additionalParticipants->items())->unique('id');
    }


    /*Deleting from group*/
    function removeFromGroup(Participant $participant)  {


        #abort if user does not belong to conversation
        abort_unless($participant->participantable->belongsToConversation($this->conversation), 403, 'This user does not belong to conversation');

        #abort if user participants is owner
        abort_if($participant->isOwner(), 403, "Owner cannot be removed from group");


        #remove from group
        $participant->delete();

        #remove from 
         // Remove member if they are already selected
         $this->participants = $this->participants->reject(function ($member) use ($participant) {
            return $member->id == $participant->id && get_class($member) == get_class($participant);
          });

        $this->dispatch('refresh')->to(Info::class);
      //  $this->dispatch('refresh')->self();


    }


    /**
     * loadmore conversation
     */
    public function loadMore()
    {


        //Check if no more conversations
        if (!$this->canLoadMore) {
            return null;
        }
        // Load the next page
        $this->page++;
        $this->loadParticipants();
    }


    function mount(Conversation $conversation)
    {
        abort_unless(auth()->check(), 401);


        $this->conversation = $conversation;

        // Log::info(['$conversation'=>$conversation]);
        abort_if($this->conversation->isPrivate(), 403, 'This is a private conversation');


        $this->participants = collect();
        // Load participants and get the count
        //    $this->conversation->loadCount('participants');
    }


    public function render()
    {



        $this->loadParticipants();

        // Pass data to the view
        return view('wirechat::livewire.info.members');
    }
}
