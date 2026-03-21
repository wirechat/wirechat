<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Events\MessageCreated;
use Wirechat\Wirechat\Jobs\NotifyParticipants;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\Participant;

class SendInviteLink extends ModalComponent
{
    use HasPanel;

    #[Locked]
    public Conversation $conversation;

    #[Locked]
    public Invite $invite;

    public $group;

    protected ?Participant $authParticipant = null;

    public $users;

    public $search;

    public $selectedMembers;

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'destroyOnClose' => true,
            'closeOnClickAway' => false,
        ];
    }

    public function updatedSearch(): void
    {
        if (blank($this->search)) {
            $this->users = null;

            return;
        }

        $this->users = collect($this->panel()->searchUsers($this->search)->collection)
            ->map(function ($resource) {
                $model = $resource->resource;

                return [
                    'id' => $model->id,
                    'type' => $model->getMorphClass(),
                    'wirechat_name' => $model->wirechat_name,
                    'wirechat_avatar_url' => $model->wirechat_avatar_url,
                ];
            });
    }

    public function toggleMember($id, string $class): void
    {
        $model = app($class)->find($id);

        if (! $model) {
            return;
        }

        if ($this->selectedMembers->contains(fn ($member) => $member->id == $model->id && get_class($member) == get_class($model))) {
            $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
                return $member->id == $id && get_class($member) == $class;
            })->values();

            return;
        }

        $this->selectedMembers->push($model);
    }

    public function save(): void
    {
        if ($this->selectedMembers->isEmpty()) {
            return;
        }

        $messageBody = __('wirechat::chat.group.invite_link.send_via_chat.messages.invite_message', [
            'group' => $this->group->name ?: __('wirechat::chat.group.invite_link.page.labels.group_fallback'),
            'url' => $this->invite->url($this->panel()),
        ]);

        foreach ($this->selectedMembers as $member) {
            if (! $member instanceof Model) {
                continue;
            }

            $message = auth()->user()->sendMessageTo($member, $messageBody);

            if (! $message) {
                continue;
            }

            try {
                broadcast(new MessageCreated($message, $this->panel()->getId()))->toOthers();

                if (! $message->conversation->isSelf()) {
                    NotifyParticipants::dispatch($message->conversation, $message, $this->panel);
                }
            } catch (\Throwable $throwable) {
                Log::error($throwable->getMessage());
            }
        }

        $this->dispatch('refresh')->to(Chats::class);
        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.invite_link.send_via_chat.messages.sent_success', ['count' => $this->selectedMembers->count()]));
        $this->closeWirechatModal();
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
            'You do not have permission to send group invite links'
        );

        abort_unless(
            $this->invite->inviteable_type === $this->group->getMorphClass() && (string) $this->invite->inviteable_id === (string) $this->group->getKey(),
            404,
            'Invite link not found for this group'
        );

        $this->selectedMembers = collect();
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.send-invite-link');
    }
}
