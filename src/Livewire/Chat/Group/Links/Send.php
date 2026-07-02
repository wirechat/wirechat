<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Links;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Events\MessageCreated;
use Wirechat\Wirechat\Jobs\NotifyParticipants;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\ResolvesPanelSearchResults;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\Participant;

class Send extends ModalComponent
{
    use HasPanel;
    use ResolvesPanelSearchResults;

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
            ->filter(fn ($resource) => $this->canReceiveInviteLink($resource->resource))
            ->map(function ($resource) {
                $model = $resource->resource;

                return [
                    'id' => $model->getKey(),
                    'type' => $model->getMorphClass(),
                    'wirechat_name' => $model->wirechat_name,
                    'wirechat_avatar_url' => $model->wirechat_avatar_url,
                    'wirechat_subtitle' => data_get($model, 'wirechat_subtitle'),
                ];
            })
            ->values();
    }

    public function toggleMember($id, string $class): void
    {
        $this->authorizeSendAccess();

        if ($this->selectedMembers->contains(fn ($member) => (string) $member->getKey() === (string) $id && $member->getMorphClass() === $class)) {
            $this->selectedMembers = $this->selectedMembers
                ->reject(fn ($member) => (string) $member->getKey() === (string) $id && $member->getMorphClass() === $class)
                ->values();

            return;
        }

        $model = $this->resolvePanelSearchResult($id, $class);

        if (! $model) {
            return;
        }

        abort_unless($this->canReceiveInviteLink($model, shouldAbort: true), 403);

        $this->selectedMembers->push($model);
    }

    public function save(): void
    {
        $this->authorizeSendAccess();

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

        // Refresh the chat list so newly created or updated DM threads show up immediately.
        $this->dispatch('refresh-chats')->to(Chats::class);
        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.invite_link.send_via_chat.messages.sent_success', ['count' => $this->selectedMembers->count()]));
        $this->closeWirechatModal();
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

        $this->authorizeSendAccess();

        abort_unless(
            $this->invite->inviteable_type === $this->group->getMorphClass() && (string) $this->invite->inviteable_id === (string) $this->group->getKey(),
            404,
            'Invite link not found for this group'
        );

        $this->selectedMembers = collect();
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.links.send');
    }

    protected function canReceiveInviteLink(Model $model, bool $shouldAbort = false): bool
    {
        $participant = $this->conversation->participant($model, withoutGlobalScopes: true);

        if (! $participant) {
            return true;
        }

        if ($participant->isBannedByAdmin()) {
            abort_if($shouldAbort, 403, __('wirechat::chat.group.invite_link.send_via_chat.messages.unavailable_blocked', ['member' => $model->wirechat_name]));

            return false;
        }

        if ($participant->isRemovedByAdmin()) {
            abort_if($shouldAbort, 403, __('wirechat::chat.group.invite_link.send_via_chat.messages.unavailable_removed', ['member' => $model->wirechat_name]));

            return false;
        }

        if ($participant->hasExited()) {
            abort_if($shouldAbort, 403, __('wirechat::chat.group.invite_link.send_via_chat.messages.unavailable_left', ['member' => $model->wirechat_name]));

            return false;
        }

        return true;
    }

    protected function authorizeSendAccess(): void
    {
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless(
            $this->authParticipant?->isAdmin() || $this->group?->allowsMembersToInviteOthersViaLink(),
            403,
            'You do not have permission to send group invite links'
        );

        abort_unless($this->authParticipant?->isAdmin() || $this->invite->is_primary, 403, 'You do not have permission to send this invite link');
    }
}
