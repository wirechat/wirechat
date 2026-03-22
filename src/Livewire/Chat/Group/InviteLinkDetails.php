<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group;

use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\Participant;

class InviteLinkDetails extends ModalComponent
{
    use HasPanel;

    #[Locked]
    public Conversation $conversation;

    #[Locked]
    public Invite $invite;

    public $group;

    protected ?Participant $authParticipant = null;

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'destroyOnClose' => true,
        ];
    }

    public function mount(): void
    {
        $this->initializePanel($this->panel);

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, 'You do not have permission to access this resource');
        abort_if($this->conversation->isPrivate(), 403, 'This feature is only available for groups');

        $this->conversation = $this->conversation->load('group');
        $this->group = $this->conversation->group;
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless(
            $this->authParticipant?->isAdmin() || $this->group?->allowsMembersToAddOthers(),
            403,
            'You do not have permission to access invite links'
        );

        abort_unless(
            $this->invite->inviteable_type === $this->group->getMorphClass()
            && (string) $this->invite->inviteable_id === (string) $this->group->getKey()
            && $this->invite->panel_id === $this->panel()->getId(),
            404,
            'Invite link not found for this group'
        );

        $this->invite = $this->invite->loadMissing('createdBy');
    }

    public function revokeLink(): void
    {
        abort_unless($this->authParticipant?->isAdmin(), 403, 'You do not have permission to revoke invite links');
        abort_if($this->invite->is_primary, 403, 'Primary invite links cannot be revoked here.');

        $this->invite->revoke();

        $this->dispatch('refreshGroupInvites');
        $this->dispatch('wirechat-toast', type: 'success', message: 'Invite link revoked.');
        $this->closeWirechatModal();
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.invite-link-details', [
            'inviteUrl' => $this->invite->url($this->panel()),
            'canRevokeLink' => (bool) $this->authParticipant?->isAdmin() && ! $this->invite->is_primary,
        ]);
    }
}
