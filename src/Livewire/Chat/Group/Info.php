<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group;

use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Jobs\DeleteConversationJob;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Models\Conversation;

/**
 * Info Component
 *
 * @property \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable|null $sendable
 */
class Info extends ModalComponent
{
    use HasPanel;
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

    /**
     * Returns the authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable|null
     */
    #[Computed(persist: true)]
    public function sendable()
    {
        return Wirechat::getSendable();
    }

    public function participantsCountUpdated(int $newCount)
    {

        return $this->totalParticipants = $newCount;

    }

    private function setDefaultValues()
    {
        $this->description = $this->group?->description;
        $this->groupName = $this->group?->name;
        $this->cover_url = $this->conversation->group?->cover_url;

    }

    public function messages(): array
    {
        return [
            'groupName.required' => __('wirechat::validation.required', ['attribute' => __('wirechat::chat.group.info.inputs.name.label')]),
            'groupName.max' => __('wirechat::validation.max.string', ['attribute' => __('wirechat::chat.group.info.inputs.name.label')]),
            'description.max' => __('wirechat::validation.max.string', ['attribute' => __('wirechat::chat.group.info.inputs.description.label')]),
            'photo.max' => __('wirechat::validation.max.file', ['attribute' => __('wirechat::chat.group.info.inputs.photo.label')]),
            'photo.image' => __('wirechat::validation.image', ['attribute' => __('wirechat::chat.group.info.inputs.photo.label')]),
            'photo.mimes' => __('wirechat::validation.mimes', ['attribute' => __('wirechat::chat.group.info.inputs.photo.label')]),
        ];
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
            ['description' => 'max:500|nullable']
        );

        $this->conversation->group?->updateOrCreate(['conversation_id' => $this->conversation->id], ['description' => $value]);
    }

    /* Update Group name when for submittted */
    public function updateGroupName()
    {

        abort_unless($this->conversation->isGroup(), 405);

        $this->validate(
            ['groupName' => 'required|max:120|nullable']
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
            $path = $photo->store(Wirechat::storage()->attachmentsDirectory(), Wirechat::storage()->disk());
            $url = Storage::disk(Wirechat::storage()->disk())->url($path);
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
    public function deleteGroup()
    {
        abort_unless(auth()->check(), 401);

        abort_unless($this->sendable->belongsToConversation($this->conversation), 403, 'Forbidden: You do not have permission to delete this group.');

        abort_if($this->conversation->isPrivate(), 403, 'Operation not allowed: Private chats cannot be deleted.');

        abort_unless($this->sendable->isOwnerOf($this->conversation), 403, 'Forbidden: You do not have permission to delete this group.');

        // Ensure all participants are removed before deleting the group
        $participantCount = $this->conversation->participants()
            ->withoutParticipantable($this->sendable)
            ->where('role', '!=', ParticipantRole::OWNER)
            ->count();

        abort_unless($participantCount == 0, 403, 'Cannot delete group: Please remove all members before attempting to delete the group.');

        // handle widget termination
        $this->handleComponentTermination(
            redirectRoute: $this->panel()->chatsRoute(),
            events: [
                ['close-chat',  ['conversation' => $this->conversation->id]],
                Chats::class => ['chat-deleted',  [$this->conversation->id]],
            ]
        );

        // Soft Delete conversation
        $this->conversation->deleteFor($this->sendable);

        // Dispatch job to delete conversation in backgroud
        // This is done to not hold up page for user incase of long running prcoess and to also give time for widget to settle avoiding 404 livewire hydrate errors
        DeleteConversationJob::dispatch($this->conversation, $this->panel);

    }

    public function exitConversation()
    {
        abort_unless(auth()->check(), 401);

        // make sure owner if group cannot be removed from chat
        abort_if($this->sendable->isOwnerOf($this->conversation), 403, 'Owner cannot exit conversation');

        // delete conversation
        $this->sendable->exitConversation($this->conversation);

        $this->handleComponentTermination(
            redirectRoute: $this->panel()->chatsRoute(),
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
        abort_unless($this->conversation->isGroup(), 403, __('wirechat::chat.info.group.messages.invalid_conversation_type_error'));
        abort_unless($this->sendable->belongsToConversation($this->conversation), 403);

        $this->conversation = $this->conversation->load('group.conversation', 'group.cover')->loadCount('participants');

        $this->totalParticipants = $this->conversation->participants_count;
        $this->group = $this->conversation->group;

        $this->setDefaultValues();
    }

    public function render()
    {

        $participant = $this->conversation->participant($this->sendable);

        //  dd($this->isWidget(),$participant);

        // Pass data to the view
        return view('wirechat::livewire.chat.group.info', [
            'receiver' => $this->conversation->getReceiver(),
            'participant' => $participant,
        ]);
    }
}
