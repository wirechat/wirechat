<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group;

use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
// use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class AddMembers extends ModalComponent
{
    use HasPanel;
    use WithFileUploads;

    #[Locked]
    public Conversation $conversation;

    public $group;

    // #[Locked]
    protected ?Participant $authParticipant = null;

    public $users;

    public $search;

    public $selectedMembers;

    public $participants;

    #[Locked]
    public $newTotalCount;

    public $exitingMembersCount;

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

        // Make sure it's not empty
        if (blank($this->search)) {

            $this->users = null;
        } else {

            /**
             * Update the users list based on the search term.
             *
             * Maps the panel search results to an array with:
             * - id, type, wirechat_name, wirechat_avatar_url
             * - belongsToConversation flag for the current conversation
             */
            $this->users = collect($this->panel()->searchUsers($this->search)->collection)
                ->map(function ($resource) {
                    $model = $resource->resource; // underlying model

                    return [
                        'id' => $model->id,
                        'type' => $model->getMorphClass(),
                        'wirechat_name' => $model->wirechat_name,
                        'wirechat_avatar_url' => $model->wirechat_avatar_url,
                        'belongsToConversation' => $model->belongsToConversation($this->conversation),
                    ];
                });

        }
    }

    public function toggleMember($id, string $class)
    {

        $model = app($class)->find($id);

        if ($model) {

            // abort if member already belong to conversation
            abort_if($model->belongsToConversation($this->conversation), 403, $model->wirechat_name.' Is already a member');

            if ($this->selectedMembers->contains(fn ($member) => $member->id == $model->id && get_class($member) == get_class($model))) {
                // Remove member if they are already selected
                $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
                    return $member->id == $id && get_class($member) == $class;
                });
            } else {

                // validate members count
                if ($this->newTotalCount >= $this->panel()->getMaxGroupMembers()) {
                    return $this->dispatch('show-member-limit-error');
                }

                $participant = $this->conversation->participant($model, withoutGlobalScopes: true);

                // abort if member already exited group
                abort_if($participant?->hasExited(), 403, 'Cannot add '.$model->wirechat_name.' because they left the group');

                // check if is removed - if true then
                // abort if non admin member tries to add a participant previously removed by admin
                if ($participant?->isRemovedByAdmin()) {
                    $authParticipant = $this->conversation->participant(auth()->user());

                    abort_unless($authParticipant?->isAdmin(), 403, 'Cannot add '.$model->wirechat_name.' because they were removed from the group by an Admin.');

                }

                // Add member if they are not selected
                $this->selectedMembers->push($model);
            }

            // update total count
            // dd($this->conversation);
            $this->newTotalCount = count($this->selectedMembers) + $this->exitingMembersCount;
        }
    }

    public function save()
    {

        $authParticipant = $this->conversation->participant(auth()->user());

        foreach ($this->selectedMembers as $key => $member) {

            // make sure user does not belong to conversation already
            // we set gloabl scopes to true to as to also check members hidden by scopes- to avoid duplicate constraint error
            $alreadyExists = $member->belongsToConversation($this->conversation);

            if (! $alreadyExists) {
                $this->conversation->addParticipant($member, undoAdminRemovalAction: $authParticipant?->isAdmin());
            }
        }

        $this->closeWirechatModal();

        $this->dispatch('participantsCountUpdated', $this->newTotalCount)->to(\Wirechat\Wirechat\Livewire\Chat\Group\Info::class);
    }

    public function mount()
    {
        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);

        abort_if($this->conversation->isPrivate(), 403, 'Cannot add members to private conversation');

        // Load participants and get the count
        $this->conversation->loadCount('participants');

        // Dump the participants count

        $this->exitingMembersCount = $this->conversation->participants_count;
        $this->newTotalCount = $this->exitingMembersCount;

        $this->selectedMembers = collect();
    }

    public function render()
    {

        // Pass data to the view
        return view('wirechat::livewire.chat.group.add-members', ['maxGroupMembers' => $this->panel()->getMaxGroupMembers()]);
    }
}
