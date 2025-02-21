<?php

namespace Namu\WireChat\Livewire\Chat\Group;

use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
// use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Chat\Info;
use Namu\WireChat\Livewire\Concerns\ModalComponent;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Participant;

class AddMembers extends ModalComponent
{
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

            $this->users = auth()->user()->searchChatables($this->search);
        }
    }

    public function toggleMember($id, string $class)
    {

        $model = app($class)->find($id);

        if ($model) {

            // abort if member already belong to conversation
            abort_if($model->belongsToConversation($this->conversation), 403, __(':name Is already a member', ['name' => $model->display_name]));

            if ($this->selectedMembers->contains(fn ($member) => $member->id == $model->id && get_class($member) == get_class($model))) {
                // Remove member if they are already selected
                $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
                    return $member->id == $id && get_class($member) == $class;
                });
            } else {

                // validate members count
                if ($this->newTotalCount >= WireChat::maxGroupMembers()) {
                    return $this->dispatch('show-member-limit-error');
                }

                $participant = $this->conversation->participant($model, withoutGlobalScopes: true);

                // abort if member already exited group
                abort_if($participant?->hasExited(), 403, __('Cannot add :name because they left the group', ['name' => $model->display_name]));

                // check if is removed - if true then
                // abort if non admin member tries to add a participant previously removed by admin
                if ($participant?->isRemovedByAdmin()) {
                    $authParticipant = $this->conversation->participant(auth()->user());

                    abort_unless($authParticipant?->isAdmin(), 403, __('Cannot add :name because they were removed from the group by an Admin.', ['name' => $model->display_name]));

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

        $this->closeWireChatModal();

        $this->dispatch('participantsCountUpdated', $this->newTotalCount)->to(Info::class);
    }

    public function mount()
    {
        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);

        abort_if($this->conversation->isPrivate(), 403, __('Cannot add members to private conversation'));

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
        return view('wirechat::livewire.chat.group.add-members', ['maxGroupMembers' => WireChat::maxGroupMembers()]);
    }
}
