<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Links;

use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\Participant;

class Links extends ModalComponent
{
    use HasPanel;

    protected $listeners = [
        'refreshGroupInvites' => '$refresh',
    ];

    #[Locked]
    public Conversation $conversation;

    public $group;

    protected ?Participant $authParticipant = null;

    public Invite $invite;

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

        abort_unless($this->panel()->hasGroupInvitations(), 404);

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, 'You do not have permission to access this resource');
        abort_if($this->conversation->isPrivate(), 403, 'This feature is only available for groups');

        $this->conversation = $this->conversation->load('group.cover');
        $this->group = $this->conversation->group;
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($this->authParticipant?->isAdmin(), 403, 'You do not have permission to manage group invite links');

        $this->invite = $this->resolvePrimaryInvite();
    }

    protected function resolvePrimaryInvite(): Invite
    {
        $invite = $this->group->inviteLinks()
            ->active()
            ->where('panel_id', $this->panel()->getId())
            ->primary()
            ->latest('id')
            ->first();

        return $invite ?? $this->createInvite(primary: true);
    }

    protected function createInvite(bool $primary = false, array $attributes = []): Invite
    {
        $auth = auth()->user();

        return $this->group->inviteLinks()->create(array_merge([
            'panel_id' => $this->panel()->getId(),
            'created_by_id' => $auth?->getKey(),
            'created_by_type' => $auth?->getMorphClass(),
            'token' => Invite::generateToken(),
            'is_primary' => $primary,
        ], $attributes));
    }

    public function resetLink(): void
    {
        $authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($authParticipant?->isAdmin(), 403, 'You do not have permission to reset group invite links');

        $this->invite->revoke();
        $this->invite = $this->createInvite(primary: true);

        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.invite_link.messages.reset_success'));
    }

    public function render()
    {
        $authParticipant = $this->conversation->participant(auth()->user());
        $primaryInvite = $this->invite->fresh(['createdBy']);

        return view('wirechat::livewire.chat.group.links.links', [
            'primaryInvite' => $primaryInvite,
            'primaryInviteUrl' => $primaryInvite->url($this->panel()),
            'canResetLink' => (bool) $authParticipant?->isAdmin(),
            'canManageJoinRequests' => (bool) $authParticipant?->isAdmin(),
            'canEditGroupAccess' => (bool) $authParticipant?->isOwner(),
            'pendingJoinRequestsCount' => $this->group->pendingJoinRequests()->count(),
            'requiresAdminApproval' => $this->group->requiresInviteApproval(),
        ]);
    }
}
