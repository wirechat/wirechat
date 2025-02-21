<?php

namespace Namu\WireChat\Livewire\Chat;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Jobs\DeleteConversationJob;
use Namu\WireChat\Livewire\Chats\Chats;
use Namu\WireChat\Livewire\Concerns\ModalComponent;
use Namu\WireChat\Livewire\Concerns\Widget;
use Namu\WireChat\Models\Conversation;

class Info extends ModalComponent
{
    use Widget;
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

    protected $listeners = [
        'participantsCountUpdated',
    ];

    public $totalParticipants;

    public function participantsCountUpdated(int $newCount)
    {

        // dd($newCount);
        return $this->totalParticipants = $newCount;

    }

    private function setDefaultValues()
    {
        $this->description = $this->group?->description;
        $this->groupName = $this->group?->name;
        if ($this->conversation?->isGroup()) {
            $this->cover_url = $this->conversation?->group?->cover_url;
        } else {
            $this->cover_url = $this->conversation->getReceiver()?->cover_url;
        }

    }

    public static function closeModalOnEscapeIsForceful(): bool
    {
        return false;
    }
    // public static function closeModalOnEscape(): bool
    // {
    //     return false;
    // }

    public function updatedDescription($value)
    {

        abort_unless($this->conversation->isGroup(), 405);
        // dd($value,str($value)->length() );

        $this->validate(
            ['description' => 'max:500|nullable'],
            ['description.max' => __('Description cannot exceed 500 characters.')]
        );

        $this->conversation->group?->updateOrCreate(['conversation_id' => $this->conversation->id], ['description' => $value]);
    }

    /* Update Group name when for submittted */
    public function updateGroupName()
    {

        abort_unless($this->conversation->isGroup(), 405);

        $this->validate(
            ['groupName' => 'required|max:120|nullable'],
            [
                'groupName.required' => __('The Group name cannot be empty'),
                'groupName.max' => __('Group name cannot exceed 120 characters.'),

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

        abort_unless($this->conversation->isGroup(), 405);
        // delete photo from group

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

        abort_unless($this->conversation->isGroup(), 405);

        // validate
        $this->validate([
            'photo' => 'image|max:12024|nullable|mimes:png,jpg,jpeg,webp',
        ]);

        // create and save photo is present
        if ($photo) {

            // remove current photo
            $this->group?->cover?->delete();
            // save photo to disk
            $path = $photo->store(WireChat::storageFolder(), WireChat::storageDisk());
            $url = Storage::url($path);
            // create attachment
            $this->conversation->group?->cover()?->create([
                'file_path' => $path,
                'file_name' => basename($path),
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
                'url' => $url,
            ]);

            $this->cover_url = $url;
            $this->reset('photo');

            $this->dispatch('refresh')->to(Chats::class);
            $this->dispatch('refresh')->to(Chat::class);

        }

    }

    /**
     * Delete  private or self chat  */
    public function deleteChat()
    {
        abort_unless(auth()->check(), 401);

        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);
        abort_unless($this->conversation->isSelf() || $this->conversation->isPrivate(), 403, __('This operation is not available for Groups.'));

        // delete conversation
        $this->conversation->deleteFor(auth()->user());

        // redirect to chats page pr
        // Dispatach event instead if isWidget
        // handle widget termination
        $this->handleComponentTermination(
            redirectRoute: route(WireChat::indexRouteName()),
            events: [
                'close-chat',
                Chats::class => ['chat-deleted',  [$this->conversation->id]],
            ]
        );

    }

    /**
     * Delete  private or self chat  */
    public function deleteGroup()
    {
        abort_unless(auth()->check(), 401);

        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, __('Forbidden: You do not have permission to delete this group.'));

        abort_if($this->conversation->isPrivate(), 403, __('Operation not allowed: Private chats cannot be deleted.'));

        abort_unless(auth()->user()->isOwnerOf($this->conversation), 403, __('Forbidden: You do not have permission to delete this group.'));

        // Ensure all participants are removed before deleting the group
        $participantCount = $this->conversation->participants()
            ->withoutParticipantable(auth()->user())
            ->where('role', '!=', ParticipantRole::OWNER)
            ->count();

        abort_unless($participantCount == 0, 403, __('Cannot delete group: Please remove all members before attempting to delete the group.'));

        // handle widget termination
        $this->handleComponentTermination(
            redirectRoute: route(WireChat::indexRouteName()),
            events: [
                ['close-chat',  ['conversation' => $this->conversation->id]],
                Chats::class => ['chat-deleted',  [$this->conversation->id]],
            ]
        );

        // Soft Delete conversation
        $this->conversation->deleteFor(auth()->user());

        // Dispatch job to delete conversation in backgroud
        // This is done to not hold up page for user incase of long running prcoess and to also give time for widget to settle avoiding 404 livewire hydrate errors
        DeleteConversationJob::dispatch($this->conversation);

    }

    public function exitConversation()
    {
        abort_unless(auth()->check(), 401);

        $auth = auth()->user();

        // make sure owner if group cannot be removed from chat
        abort_if($auth->isOwnerOf($this->conversation), 403, __('Owner cannot exit conversation'));

        // delete conversation
        $auth->exitConversation($this->conversation);

        $this->handleComponentTermination(
            redirectRoute: route(WireChat::indexRouteName()),
            events: [
                'close-chat',
                Chats::class => ['chat-exited',  [$this->conversation->id]],
            ]
        );
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

    public function mount()
    {

        abort_if(empty($this->conversation), 404);

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);

        $this->conversation = $this->conversation->load('group.conversation', 'group.cover')->loadCount('participants');

        $this->totalParticipants = $this->conversation->participants_count;
        $this->group = $this->conversation->group;

        $this->setDefaultValues();
    }

    public function render()
    {

        $participant = $this->conversation?->participant(auth()->user());

        //  dd($this->isWidget(),$participant);

        // Pass data to the view
        return view('wirechat::livewire.chat.info', [
            'receiver' => $this->conversation?->getReceiver(),
            'participant' => $participant,
        ]);
    }
}
