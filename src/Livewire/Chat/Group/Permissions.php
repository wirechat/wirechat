<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group;

use Livewire\Attributes\Locked;
// use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class Permissions extends ModalComponent
{
    use HasPanel;
    use WithFileUploads;

    #[Locked]
    public Conversation $conversation;

    public $group;

    // #[Locked]
    protected ?Participant $authParticipant = null;

    public $users;

    public $search;

    public $selectedMembers;

    /* Permissions */
    public bool $allow_members_to_send_messages = false;

    public bool $allow_members_to_add_others = false;

    public bool $allow_members_to_edit_group_info = false;

    public static function closeModalOnEscape(): bool
    {

        return true;
    }

    public static function closeModalOnEscapeIsForceful(): bool
    {
        return false;
    }

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
        ];
    }

    public function updating($field, $value)
    {
        // dd($field, $value);
        $this->group->setAttribute($field, $value)->save();
    }

    public function mount()
    {
        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, 'You do not have permission to access this resource');

        abort_unless($this->conversation->isOwner(auth()->user()), 403, 'You do not have permission to edit group permissions');

        abort_if($this->conversation->isPrivate(), 403, 'This feature is only available for groups');

        $this->group = $this->conversation->group;

        $this->setDefaultValues();
    }

    private function setDefaultValues(): void
    {

        $this->allow_members_to_send_messages = $this->group->allow_members_to_send_messages;
        $this->allow_members_to_add_others = $this->group->allow_members_to_add_others;
        $this->allow_members_to_edit_group_info = $this->group->allow_members_to_edit_group_info;

    }

    public function render()
    {

        // Pass data to the view
        return view('wirechat::livewire.chat.group.permissions', ['maxGroupMembers' => $this->panel()->getMaxGroupMembers()]);
    }
}
