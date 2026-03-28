<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Link;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\Participant;

class CreateInviteLink extends ModalComponent
{
    use HasPanel;

    #[Locked]
    public Conversation $conversation;

    public $group;

    protected ?Participant $authParticipant = null;

    public string $name = '';

    public string $expiryPreset = 'never';

    public string $usagePreset = 'unlimited';

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'destroyOnClose' => true,
            'closeOnClickAway' => false,
        ];
    }

    public function mount(): void
    {
        $this->initializePanel($this->panel);

        abort_unless($this->panel()->hasGroupInvitations(), 404);

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, 'You do not have permission to access this resource');
        abort_if($this->conversation->isPrivate(), 403, 'This feature is only available for groups');

        $this->conversation = $this->conversation->load('group');
        $this->group = $this->conversation->group;
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($this->authParticipant?->isAdmin(), 403, 'You do not have permission to create invite links');
    }

    public function createLink(): void
    {
        $authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($authParticipant?->isAdmin(), 403, 'You do not have permission to create invite links');

        $this->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'expiryPreset' => ['required', 'in:1_hour,1_day,1_week,never'],
            'usagePreset' => ['required', 'in:1,10,50,100,unlimited'],
        ]);

        $auth = auth()->user();

        $this->group->inviteLinks()->create([
            'panel_id' => $this->panel()->getId(),
            'created_by_id' => $auth?->getKey(),
            'created_by_type' => $auth?->getMorphClass(),
            'token' => Invite::generateToken(),
            'name' => filled($this->name) ? trim($this->name) : null,
            'limit' => $this->resolveLimit(),
            'expires_at' => $this->resolveExpiry(),
            'is_primary' => false,
        ]);

        $this->dispatch('refreshGroupInvites');
        $this->dispatch('wirechat-toast', type: 'success', message: 'Invite link created.');
        $this->closeWirechatModal();
    }

    protected function resolveLimit(): ?int
    {
        return $this->usagePreset === 'unlimited' ? null : (int) $this->usagePreset;
    }

    protected function resolveExpiry(): ?Carbon
    {
        return match ($this->expiryPreset) {
            '1_hour' => now()->addHour(),
            '1_day' => now()->addDay(),
            '1_week' => now()->addWeek(),
            default => null,
        };
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.link.create');
    }
}
