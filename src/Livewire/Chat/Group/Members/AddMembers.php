<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Members;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Wirechat\Wirechat\Livewire\Concerns\CreatesGroupInvites;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\ProtectsGroupAddPrivacy;
use Wirechat\Wirechat\Livewire\Concerns\ResolvesPanelSearchResults;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class AddMembers extends ModalComponent
{
    use CreatesGroupInvites;
    use HasPanel;
    use ProtectsGroupAddPrivacy;
    use ResolvesPanelSearchResults;
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
            $users = collect();

            foreach ($this->groupAddableSearchResultModels() as $model) {
                $users->push([
                    'id' => $model->getKey(),
                    'type' => $model->getMorphClass(),
                    'wirechat_name' => $model->wirechat_name,
                    'wirechat_avatar_url' => $model->wirechat_avatar_url,
                    'wirechat_subtitle' => data_get($model, 'wirechat_subtitle'),
                    'belongsToConversation' => $model->belongsToConversation($this->conversation),
                    'isBanned' => (bool) $this->conversation->participant($model, withoutGlobalScopes: true)?->isBannedByAdmin(),
                ]);
            }

            $this->users = $users;
        }
    }

    public function toggleMember($id, string $class)
    {
        $this->authorizeAddMembersAccess();

        if ($this->selectedMembers->contains(fn ($member) => (string) $member->getKey() === (string) $id && $member->getMorphClass() === $class)) {
            $this->selectedMembers = $this->selectedMembers
                ->reject(fn ($member) => (string) $member->getKey() === (string) $id && $member->getMorphClass() === $class)
                ->values();
            $this->newTotalCount = count($this->selectedMembers) + $this->exitingMembersCount;

            return;
        }

        $model = $this->resolveGroupAddableSearchResult($id, $class);

        if ($model) {
            abort_if($model->belongsToConversation($this->conversation), 403, $model->wirechat_name.' Is already a member');

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
            $this->newTotalCount = count($this->selectedMembers) + $this->exitingMembersCount;
        }
    }

    public function save()
    {
        $this->authorizeAddMembersAccess();

        if ($this->selectedMembers->isEmpty()) {
            return;
        }

        if ($this->newTotalCount > $this->panel()->getMaxGroupMembers()) {
            return $this->dispatch('show-member-limit-error');
        }

        foreach ($this->selectedMembers as $member) {
            if (! $member instanceof Model || ! $this->canAddSelectedMember($member)) {
                return;
            }
        }

        DB::transaction(function () {
            foreach ($this->selectedMembers as $member) {
                $alreadyExists = $member->belongsToConversation($this->conversation);

                if (! $alreadyExists) {
                    $this->conversation->addParticipant($member, undoAdminRemovalAction: $this->authParticipant?->isAdmin(), reviewedBy: auth()->user());
                }
            }
        });

        $this->closeWirechatModal();

        $this->dispatch('participantsCountUpdated', $this->newTotalCount)->to('wirechat.chat.group.info');
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

    protected function canAddSelectedMember(Model $member): bool
    {
        if (! $this->canBeAddedToGroups($member)) {
            $this->dispatch('wirechat-toast', type: 'error', message: __('wirechat::chat.group.add_members.messages.group_add_privacy_denied', [
                'member' => data_get($member, 'wirechat_name', __('wirechat::chat.labels.user')),
            ]));

            return false;
        }

        if ($member->belongsToConversation($this->conversation)) {
            $this->dispatch('wirechat-toast', type: 'error', message: $member->wirechat_name.' is already a member of this group.');

            return false;
        }

        $participant = $this->conversation->participant($member, withoutGlobalScopes: true);

        if ($participant?->isBannedByAdmin()) {
            $this->dispatch('wirechat-toast', type: 'error', message: 'Cannot add '.$member->wirechat_name.' because they are banned from this group.');

            return false;
        }

        if ($participant?->hasExited()) {
            $this->dispatch('wirechat-toast', type: 'error', message: 'Cannot add '.$member->wirechat_name.' because they left this group.');

            return false;
        }

        if ($participant?->isRemovedByAdmin() && ! $this->authParticipant?->isAdmin()) {
            $this->dispatch('wirechat-toast', type: 'error', message: 'Cannot add '.$member->wirechat_name.' because they were removed from this group by an admin.');

            return false;
        }

        return true;
    }

    protected function resolvePrimaryInviteUrl(): ?string
    {
        if (! $this->panel() || ! $this->panel()->hasGroupInvitations()) {
            return null;
        }

        if (! $this->authParticipant?->isAdmin() && ! $this->group?->allowsMembersToInviteOthersViaLink()) {
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

            $invite = $this->createInviteWithUniqueToken($this->group->inviteLinks(), [
                'panel_id' => $this->panel()->getId(),
                'created_by_id' => $auth?->getKey(),
                'created_by_type' => $auth?->getMorphClass(),
                'is_primary' => true,
            ]);
        }

        return $this->panel()->inviteRouteIfRegistered($invite->token);
    }
}
