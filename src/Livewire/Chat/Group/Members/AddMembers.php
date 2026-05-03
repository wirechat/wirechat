<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Members;

use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\Participant;

class AddMembers extends ModalComponent
{
    use HasPanel;
    use WithFileUploads;

    #[Locked]
    public Conversation $conversation;

    public $group;

    protected ?Participant $authParticipant = null;

    public $users;

    public $search;

    public $selectedMembers;

    public $participants;

    #[Locked]
    public $newTotalCount;

    public $exitingMembersCount;

    public ?string $primaryInviteUrl = null;

    public static function closeOnClickAway(): bool
    {
        return false;
    }

    public static function closeModalOnEscape(): bool
    {
        return false;
    }

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'dispatchCloseEvent' => false,
            'destroyOnClose' => true,
            'closeOnClickAway' => false,
        ];
    }

    /**
     * Search For users to create conversations with
     */
    public function updatedSearch()
    {
        if (blank($this->search)) {
            $this->users = null;
        } else {
            $this->users = collect($this->panel()->searchUsers($this->search)->collection)
                ->map(function ($resource) {
                    $model = $resource->resource;

                    return [
                        'id' => $model->id,
                        'type' => $model->getMorphClass(),
                        'wirechat_name' => $model->wirechat_name,
                        'wirechat_avatar_url' => $model->wirechat_avatar_url,
                        'belongsToConversation' => $model->belongsToConversation($this->conversation),
                        'isBanned' => (bool) $this->conversation->participant($model, withoutGlobalScopes: true)?->isBannedByAdmin(),
                    ];
                });
        }
    }

    public function toggleMember($id, string $class)
    {
        $this->authorizeAddMembersAccess();

        $model = app($class)->find($id);

        if ($model) {
            abort_if($model->belongsToConversation($this->conversation), 403, $model->wirechat_name.' Is already a member');

            if ($this->selectedMembers->contains(fn ($member) => $member->id == $model->id && get_class($member) == get_class($model))) {
                $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
                    return $member->id == $id && get_class($member) == $class;
                });
            } else {
                if ($this->newTotalCount >= $this->panel()->getMaxGroupMembers()) {
                    return $this->dispatch('show-member-limit-error');
                }

                $participant = $this->conversation->participant($model, withoutGlobalScopes: true);

                if ($participant?->isBannedByAdmin()) {
                    $this->dispatch(
                        'wirechat-toast',
                        type: 'warning',
                        message: 'Cannot add '.$model->wirechat_name.' because they were banned from the group by an Admin.'
                    );

                    return;
                }
                abort_if($participant?->hasExited(), 403, 'Cannot add '.$model->wirechat_name.' because they left the group');

                if ($participant?->isRemovedByAdmin()) {
                    abort_unless($this->authParticipant?->isAdmin(), 403, 'Cannot add '.$model->wirechat_name.' because they were removed from the group by an Admin.');
                }

                $this->selectedMembers->push($model);
            }

            $this->newTotalCount = count($this->selectedMembers) + $this->exitingMembersCount;
        }
    }

    public function save()
    {
        $this->authorizeAddMembersAccess();

        foreach ($this->selectedMembers as $member) {
            $alreadyExists = $member->belongsToConversation($this->conversation);

            if (! $alreadyExists) {
                $this->conversation->addParticipant($member, undoAdminRemovalAction: $this->authParticipant?->isAdmin(), reviewedBy: auth()->user());
            }
        }

        $this->closeWirechatModal();

        $this->dispatch('participantsCountUpdated', $this->newTotalCount)->to(\Wirechat\Wirechat\Livewire\Chat\Group\Info::class);
    }

    public function mount()
    {
        $this->initializePanel($this->panel);

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);
        abort_if($this->conversation->isPrivate(), 403, 'Cannot add members to private conversation');

        $this->conversation = $this->conversation->load('group')->loadCount('participants');
        $this->group = $this->conversation->group;
        $this->authParticipant = $this->conversation->participant(auth()->user());

        $this->authorizeAddMembersAccess();

        $this->exitingMembersCount = $this->conversation->participants_count;
        $this->newTotalCount = $this->exitingMembersCount;
        $this->selectedMembers = collect();
        $this->primaryInviteUrl = $this->resolvePrimaryInviteUrl();
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.members.add', [
            'maxGroupMembers' => $this->panel()->getMaxGroupMembers(),
        ]);
    }

    protected function authorizeAddMembersAccess(): void
    {
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless(
            $this->authParticipant?->isAdmin() || $this->group?->allowsMembersToAddOthers(),
            403,
            'You do not have permission to add members to this group'
        );
    }

    protected function resolvePrimaryInviteUrl(): ?string
    {
        if (! $this->panel() || ! $this->panel()->hasGroupInvitations()) {
            return null;
        }

        $invite = $this->group->inviteLinks()
            ->active()
            ->where('panel_id', $this->panel()->getId())
            ->primary()
            ->latest('id')
            ->first();

        if (! $invite) {
            $auth = auth()->user();

            $invite = $this->group->inviteLinks()->create([
                'panel_id' => $this->panel()->getId(),
                'created_by_id' => $auth?->getKey(),
                'created_by_type' => $auth?->getMorphClass(),
                'token' => Invite::generateToken(),
                'is_primary' => true,
            ]);
        }

        return $invite->url($this->panel());
    }
}
