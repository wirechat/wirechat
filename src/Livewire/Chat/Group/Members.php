<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
// use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Livewire\Widgets\Wirechat as WidgetsWirechat;
use Wirechat\Wirechat\Models\Action;
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

    protected function loadParticipants(): void
    {

        $searchableFields = $this->panel()->getSearchableAttributes();
        $columnCache = []; // Initialize cache for column checks
        // Check if $this->participants is initialized
        $this->participants = $this->participants ?? collect();

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
            ->paginate(10, ['*'], 'page', $this->page);
        // Check if cannot load more
        $this->canLoadMore = $additionalParticipants->hasMorePages();

        // Merge current participants with the additional ones
        // Merge current participants with the additional ones and remove duplicates
        $this->participants = $this->participants->merge($additionalParticipants->items())->unique('id');
    }

    /* Deleting from group */
    public function removeFromGroup(Participant $participant)
    {

        // Load missing relationship in case of strict models types
        $participant->loadMissing('participantable');
        // abort if user does not belong to conversation
        abort_unless($participant->participantable->belongsToConversation($this->conversation), 403, 'This user does not belong to conversation');

        // abort if auth is not admin
        abort_unless(auth()->user()->isAdminIn($this->conversation), 403, 'You do not have permission to perform this action in this group. Only admins can proceed.');

        // abort if user participants is owner
        abort_if($participant->isOwner(), 403, 'Owner cannot be removed from group');

        // remove from group
        // Create the 'remove' action record in the actions table
        Action::create([
            'actionable_id' => $participant->id,
            'actionable_type' => Participant::class,
            'actor_id' => auth()->id(),  // The admin who performed the action
            'actor_type' => auth()->user()->getMorphClass(),  // Assuming 'User' is the actor model
            'type' => Actions::REMOVED_BY_ADMIN,  // Type of action
        ]);

        // remove from
        // Remove member if they are already selected
        $this->participants = $this->participants->reject(function ($member) use ($participant) {
            return $member->id == $participant->id && get_class($member) == get_class($participant);
        });

        // subtract one from total members and update chat list
        $this->totalMembersCount = $this->totalMembersCount - 1;

        $this->dispatch('participantsCountUpdated', $this->totalMembersCount)->to(\Wirechat\Wirechat\Livewire\Chat\Group\Info::class);
        //  $this->dispatch('refresh')->self();

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
        // Load the next page
        $this->page++;
        $this->loadParticipants();
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

    public function render()
    {

        //

        // Pass data to the view
        return view('wirechat::livewire.chat.group.members', [
            'participant' => $this->conversation->participant(auth()->user()),

        ]);
    }
}
