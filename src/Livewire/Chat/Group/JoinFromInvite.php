<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group;

use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Invite;

class JoinFromInvite extends ModalComponent
{
    use HasPanel;

    #[Locked]
    public string $token;

    public ?Invite $invite = null;

    public ?Conversation $conversation = null;

    public $group;

    public bool $isMember = false;

    public bool $requiresApproval = false;

    public bool $hasPendingJoinRequest = false;

    public bool $joinBlocked = false;

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

        $this->resolveContext();
        $this->syncState();
    }

    protected function resolveContext(): void
    {
        $invite = Invite::query()
            ->where('panel_id', $this->panel()->getId())
            ->where('token', $this->token)
            ->with(['inviteable.cover', 'createdBy'])
            ->firstOrFail();

        abort_unless($invite->isActive(), 410, 'Invite link is no longer active.');

        $group = $invite->inviteable;

        abort_unless($group instanceof Group, 404);

        $conversation = $group->conversation()
            ->with(['participants.participantable', 'group.cover'])
            ->withCount('participants')
            ->firstOrFail();

        $this->invite = $invite;
        $this->group = $group;
        $this->conversation = $conversation;
    }

    protected function syncState(): void
    {
        $auth = auth()->user();

        $this->isMember = $auth->belongsToConversation($this->conversation);
        $this->hasPendingJoinRequest = ! $this->isMember && $this->group->hasPendingJoinRequest($auth);
        $this->joinBlocked = ! $this->isMember && $this->group->inviteJoinBlockedFor($auth);
        $this->requiresApproval = ! $this->isMember && $this->group->requiresInviteApproval();
    }

    public function proceed()
    {
        $auth = auth()->user();

        if ($this->isMember) {
            return $this->redirect($this->panel()->chatRoute($this->conversation->id));
        }

        if ($this->joinBlocked) {
            $this->dispatch('wirechat-toast', type: 'error', message: 'You cannot join this group with this invite right now.');

            return null;
        }

        if ($this->hasPendingJoinRequest) {
            $this->dispatch('wirechat-toast', type: 'info', message: 'Your join request is already pending.');

            return null;
        }

        if ($this->requiresApproval) {
            $this->group->requestToJoin($auth, $this->invite);
            $this->syncState();

            $this->dispatch('wirechat-toast', type: 'success', message: 'Your join request has been sent to the admins.');

            return null;
        }

        $this->conversation->join($auth);
        $this->invite?->markUsed();

        return $this->redirect($this->panel()->chatRoute($this->conversation->id));
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.join-from-invite', [
            'membersPreview' => $this->conversation?->participants?->take(5) ?? collect(),
        ]);
    }
}
