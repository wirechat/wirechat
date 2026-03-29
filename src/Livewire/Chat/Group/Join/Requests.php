<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Join;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chat\Group\Info;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class Requests extends ModalComponent
{
    use HasPanel;

    #[Locked]
    public Conversation $conversation;

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

        abort_unless($this->panel()->hasGroupInvitations(), 404);

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);
        abort_if($this->conversation->isPrivate(), 403, 'This feature is only available for groups');

        $this->conversation = $this->conversation->load('group.cover');
        $this->group = $this->conversation->group;
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($this->authParticipant?->isAdmin(), 403, 'Only admins can manage join requests.');
    }

    public function approve(int $requestId): void
    {
        $authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($authParticipant?->isAdmin(), 403, 'Only admins can manage join requests.');

        $request = $this->group->pendingJoinRequests()->with('requester')->findOrFail($requestId);
        $requester = $request->requester;

        if ($requester instanceof Model) {
            if (! $requester->belongsToConversation($this->conversation)) {
                $this->conversation->join($requester, auth()->user());
            } else {
                $request->approve(auth()->user());
            }
        } else {
            $request->dismiss(auth()->user());
        }

        $this->dispatch('refresh')->to(Info::class);
        $this->dispatch('refresh')->to(Chat::class);
        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.join.requests.messages.approved_success'));
    }

    public function dismiss(int $requestId): void
    {
        $authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($authParticipant?->isAdmin(), 403, 'Only admins can manage join requests.');

        $request = $this->group->pendingJoinRequests()->findOrFail($requestId);

        $request->dismiss(auth()->user());

        $this->dispatch('refresh')->to(Info::class);
        $this->dispatch('refresh')->to(Chat::class);
        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.join.requests.messages.dismissed_success'));
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.join.requests', [
            'requests' => $this->group->pendingJoinRequests()->with('requester')->latest()->get(),
        ]);
    }
}
