<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Members;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Livewire\Widgets\Wirechat as WidgetsWirechat;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class Members extends ModalComponent
{
    use HasPanel;
    use Widget;
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public Conversation $conversation;

    public $group;

    public int $totalMembersCount;

    protected $page = 1;

    public $users;

    public $search;

    public $selectedMembers;

    public $participants;

    public $canLoadMore;

    public int $perPage = 10;

    #[Locked]
    public $newTotalCount;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public static function closeModalOnClickAway(): bool
    {

        return true;
    }

    public static function closeModalOnEscape(): bool
    {

        return true;
    }

    public function updatedSearch($value)
    {
        $this->page = 1; // Reset page number when search changes
        $this->participants = collect([]); // Reset to empty collection

        $this->loadParticipants();
    }

    /**
     * Actions
     */
    public function sendMessage(Participant $participant)
    {

        abort_unless(auth()->check(), 401);

        // Load missing relationship in case of strict models types
        $participant->loadMissing('participantable');

        $conversation = auth()->user()->createConversationWith($participant->participantable);

        $this->handleComponentTermination(
            redirectRoute: $this->panel()->chatRoute($conversation->id),
            events: [
                WidgetsWirechat::class => ['open-chat',  ['conversation' => $conversation->id]],
                'closeWirechatModal',
            ]
        );

        // $this->closeModalWithEvents([
        //   //  WidgetsWirechat::class => ['close-chat'],
        //     WidgetsWirechat::class => ['open-chat',  ['conversation' => $conversation->id]],
        //    // 'closeChatDrawer',
        // ]);
        // $this->dispatch('closeChatDrawer');
        // $this->dispatch('open-chat',conversation: $conversation->id);
        // $this->dispatch('closeModal');

    }

    /**
     * Admin actions */
    public function dismissAdmin(Participant $participant)
    {
        // Load missing relationship in case of strict models types
        $participant->loadMissing('participantable');

        $this->toggleAdmin($participant);
    }

    public function makeAdmin(Participant $participant)
    {
        // Load missing relationship in case of strict models types
        $participant->loadMissing('participantable');

        $this->toggleAdmin($participant);
    }

    private function toggleAdmin(Participant $participant)
    {

        abort_unless(auth()->check(), 401);

        // Load missing relationship in case of strict models types
        $participant->loadMissing('participantable');
        // abort if user does not belong to conversation
        abort_unless($participant->participantable->belongsToConversation($this->conversation), 403, 'This user does not belong to conversation');

        // abort if user participants is owner
        abort_if($participant->isOwner(), 403, 'Owner role cannot be changed');

        // toggle
        if ($participant->isAdmin()) {
            $participant->update(['role' => ParticipantRole::PARTICIPANT]);
        } else {
            $participant->update(['role' => ParticipantRole::ADMIN]);
        }
        $this->dispatch('refresh')->self();

    }

    protected function loadParticipants(): int
    {

        $searchableFields = $this->panel()->getSearchableAttributes();
        $columnCache = []; // Initialize cache for column checks
        // Check if $this->participants is initialized
        $this->participants = $this->participants ?? collect();

        $beforeCount = $this->participants->count();

        $additionalParticipants = $this->conversation->participants()
            ->with('participantable')
            ->when($this->search, function ($query) use ($searchableFields, &$columnCache) {
                $query->whereHas('participantable', function ($query2) use ($searchableFields, &$columnCache) {
                    $query2->where(function ($query3) use ($searchableFields, &$columnCache) {
                        $table = $query3->getModel()->getTable();

                        foreach ($searchableFields as $field) {
                            if (! isset($columnCache[$table])) {
                                $columnCache[$table] = Schema::getColumnListing($table);
                            }

                            if (in_array($field, $columnCache[$table])) {
                                $query3->orWhere($field, 'LIKE', '%'.$this->search.'%');
                            }
                        }
                    });
                });
            })
            ->orderByRaw('
            CASE role
                WHEN ? THEN 1
                WHEN ? THEN 2
                WHEN ? THEN 3
                ELSE 4
            END', [
                ParticipantRole::OWNER->value,
                ParticipantRole::ADMIN->value,
                ParticipantRole::PARTICIPANT->value,
            ])
            ->latest('updated_at')
            ->paginate($this->perPage, ['*'], 'page', $this->page);
        // Merge participants and remove duplicates
        $this->participants = $this->participants->merge($additionalParticipants->items())->unique('id')->values();

        // Only allow loading more if paginator has more pages AND this page added new unique members.
        $addedCount = $this->participants->count() - $beforeCount;
        $this->canLoadMore = $additionalParticipants->hasMorePages();

        return $addedCount;
    }

    /* Deleting from group */
    public function removeFromGroup(Participant $participant)
    {
        $this->authorizeAdminAction($participant, 'remove');

        $participant->removeByAdmin(auth()->user());

        // abort if auth is not admin
        abort_unless(auth()->user()->isAdminIn($this->conversation), 403, 'You do not have permission to perform this action in this group. Only admins can proceed.');

        // abort if user participants is owner
        abort_if($participant->isOwner(), 403, 'Owner cannot be removed from group');

        // determine the admin's participant in this conversation to use as the actor
        $adminParticipant = $this->conversation->participant(auth()->user());

        // ensure the admin participant exists
        if (! $adminParticipant) {
            abort(403, 'Admin participant not found in conversation');
        }

        // remove from group
        // Create the 'remove' action record in the actions table
        Wirechat::actionModelClass()::create([
            'actionable_id' => $participant->getKey(),
            'actionable_type' => $participant->getMorphClass(),
            'actor_id' => $adminParticipant->getKey(),  // The admin participant who performed the action
            'actor_type' => $adminParticipant->getMorphClass(),  // The participant model as actor
            'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
        ]);

        // remove from
        // Remove member if they are already selected
        $this->participants = $this->participants->reject(function ($member) use ($participant) {
            return $member->getKey() == $participant->getKey() && get_class($member) == get_class($participant);
        });

        $this->totalMembersCount = $this->totalMembersCount - 1;

        $this->dispatch('participantsCountUpdated', $this->totalMembersCount)->to(\Wirechat\Wirechat\Livewire\Chat\Group\Info::class);
    }

    public function blockMember(Participant $participant)
    {
        $this->authorizeAdminAction($participant, 'block');

        $participant->banByAdmin(auth()->user());

        $this->participants = $this->participants->reject(function ($member) use ($participant) {
            return $member->id == $participant->id && get_class($member) == get_class($participant);
        });

        $this->totalMembersCount = $this->totalMembersCount - 1;

        $this->dispatch('participantsCountUpdated', $this->totalMembersCount)->to(\Wirechat\Wirechat\Livewire\Chat\Group\Info::class);
        $this->dispatch('refresh')->self();
    }

    public function banMember(Participant $participant)
    {
        $this->blockMember($participant);
    }

    /**
     * loadmore conversation
     */
    public function loadMore()
    {

        // Check if no more conversations
        if (! $this->canLoadMore) {
            return null;
        }
        // Skip empty/duplicate-only pages in one click.
        do {
            $this->page++;
            $addedCount = $this->loadParticipants();
        } while ($addedCount === 0);
    }

    public function mount(Conversation $conversation)
    {
        $this->initializePanel($this->panel);
        abort_unless(auth()->check(), 401);

        $this->conversation = $conversation->load('group')->loadCount('participants');

        $this->totalMembersCount = $this->conversation->participants_count ?? 0;

        abort_if($this->conversation->isPrivate(), 403, 'This is a private conversation');

        $this->participants = collect();

        $this->loadParticipants();
    }

    protected function authorizeAdminAction(Participant $participant, string $action = 'manage'): void
    {
        $participant->loadMissing('participantable');

        abort_unless($participant->participantable->belongsToConversation($this->conversation), 403, 'This user does not belong to conversation');
        abort_unless(auth()->user()->isAdminIn($this->conversation), 403, 'You do not have permission to perform this action in this group. Only admins can proceed.');
        abort_if($participant->isOwner(), 403, ucfirst($action).' action cannot target the group owner.');
        abort_if($participant->isAdmin(), 403, ucfirst($action).' action cannot target another admin.');
        abort_if(
            $participant->participantable_id == auth()->id() && $participant->participantable_type == auth()->user()->getMorphClass(),
            403,
            'You cannot '.strtolower($action).' yourself from the group.'
        );
    }

    public function render()
    {

        //

        // Pass data to the view
        return view('wirechat::livewire.chat.group.members.list', [
            'participant' => $this->conversation->participant(auth()->user()),

        ]);
    }
}
