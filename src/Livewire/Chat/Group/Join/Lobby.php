<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Join;

use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Group;
use Wirechat\Wirechat\Models\Invite;

class Lobby extends ModalComponent
{
    use HasPanel;
    use Widget;

    #[Locked]
    public string $token;

    public ?Invite $invite = null;

    public ?Conversation $conversation = null;

    public ?Group $group = null;

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

        abort_unless($invite->isActive(), 410, __('wirechat::chat.group.join.lobby.messages.invite_inactive'));

        $group = $invite->inviteable;

        abort_unless($group instanceof Group, 404);

        /** @var Conversation $conversation */
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

        $this->invite = $this->invite->fresh();

        if (! $this->invite->isActive()) {
            $this->dispatch('wirechat-toast', type: 'error', message: __('wirechat::chat.group.join.lobby.messages.invite_inactive'));

            return null;
        }

        if ($this->isMember) {
            return $this->redirectAfterJoin();
        }

        if ($this->joinBlocked) {
            $this->dispatch('wirechat-toast', type: 'error', message: __('wirechat::chat.group.join.lobby.messages.join_blocked'));

            return null;
        }

        if ($this->hasPendingJoinRequest) {
            $this->dispatch('wirechat-toast', type: 'info', message: __('wirechat::chat.group.join.lobby.messages.pending_request'));

            return null;
        }

        if ($this->requiresApproval) {
            $this->group->requestToJoin($auth, $this->invite);
            $this->syncState();

            $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.join.lobby.messages.request_sent'));

            return null;
        }

        $this->conversation->join($auth);
        $this->invite?->markUsed();

        return $this->redirectAfterJoin();
    }

    protected function redirectAfterJoin()
    {
        if ($this->isWidget()) {
            $this->openChat($this->conversation->id);
            $this->closeWirechatModal();

            return null;
        }

        return $this->redirect($this->panel()->chatRoute($this->conversation->id));
    }

    public function render()
    {
        if ($this->conversation === null) {
            return view('wirechat::livewire.chat.group.join.lobby', [
                'membersPreview' => collect(),
                'remainingMembersCount' => 0,
            ]);
        }

        $members = $this->conversation->participants;
        $membersPreview = $members->take(6);
        $memberTotal = $this->conversation->participants_count ?? $members->count();
        $remainingMembersCount = max($memberTotal - $membersPreview->count(), 0);

        return view('wirechat::livewire.chat.group.join.lobby', [
            'membersPreview' => $membersPreview,
            'remainingMembersCount' => $remainingMembersCount,
        ]);
    }
}
