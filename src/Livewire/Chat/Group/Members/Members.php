<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Members;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
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
    public function sendMessage(int|string $participantId)
    {
        abort_unless(auth()->check(), 401);

        $participant = $this->resolveParticipant($participantId);
        $this->authorizeConversationParticipant($participant);

        abort_unless($this->canMessageParticipant($participant), 403, 'You are not allowed to send messages to this user.');

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

    public function canMessageParticipant(Participant $participant): bool
    {
        abort_unless(auth()->check(), 401);

        $participant->loadMissing('participantable');

        return auth()->user()->canSendMessageTo($participant->participantable);
    }

    /**
     * Admin actions */
    public function dismissAdmin(int|string $participantId)
    {
        $this->toggleAdmin($this->resolveParticipant($participantId));
    }

    public function makeAdmin(int|string $participantId)
    {
        $this->toggleAdmin($this->resolveParticipant($participantId));
    }

    private function toggleAdmin(Participant $participant)
    {

        abort_unless(auth()->check(), 401);

        $this->authorizeConversationParticipant($participant);

        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, 'You do not have permission to perform this action in this group. Only admins can proceed.');
        abort_unless(auth()->user()->isOwnerOf($this->conversation), 403, 'Only the group owner can manage admins.');

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
    public function removeFromGroup(int|string $participantId)
    {
        $participant = $this->resolveParticipant($participantId);

        $this->authorizeAdminAction($participant, 'remove');

        $participant->removeByAdmin(auth()->user());

        $this->removeParticipantFromLoadedList($participant);

        $this->totalMembersCount = $this->totalMembersCount - 1;

        $this->dispatch('participantsCountUpdated', $this->totalMembersCount)->to('wirechat.chat.group.info');
    }

    public function blockMember(int|string $participantId)
    {
        $participant = $this->resolveParticipant($participantId);

        $this->authorizeAdminAction($participant, 'block');

        $participant->banByAdmin(auth()->user());

        $this->removeParticipantFromLoadedList($participant);

        $this->totalMembersCount = $this->totalMembersCount - 1;

        $this->dispatch('participantsCountUpdated', $this->totalMembersCount)->to('wirechat.chat.group.info');
        $this->dispatch('refresh')->self();
    }

    public function banMember(int|string $participantId)
    {
        $this->blockMember($participantId);
    }

    /**
     * loadmore conversation
     */
    public function loadMore()
    {

        // Skip empty/duplicate-only pages in one click.
        while ($this->canLoadMore) {
            $this->page++;
            $addedCount = $this->loadParticipants();

            if ($addedCount > 0) {
                break;
            }
        }
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
        $this->authorizeConversationParticipant($participant);

        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, 'You do not have permission to perform this action in this group. Only admins can proceed.');
        abort_unless(auth()->user()->isAdminIn($this->conversation), 403, 'You do not have permission to perform this action in this group. Only admins can proceed.');
        abort_if($participant->isOwner(), 403, ucfirst($action).' action cannot target the group owner.');
        abort_if($participant->isAdmin(), 403, ucfirst($action).' action cannot target another admin.');
        abort_if(
            $participant->participantable_id == auth()->id() && $participant->participantable_type == auth()->user()->getMorphClass(),
            403,
            'You cannot '.strtolower($action).' yourself from the group.'
        );
    }

    protected function resolveParticipant(int|string $participantId): Participant
    {
        /** @var Participant $participant */
        $participant = Wirechat::participantModelClass()::query()
            ->with('participantable')
            ->findOrFail($participantId);

        return $participant;
    }

    protected function authorizeConversationParticipant(Participant $participant): void
    {
        $participant->loadMissing('participantable');

        abort_unless((string) $participant->conversation_id === (string) $this->conversation->getKey(), 403, 'This user does not belong to conversation');
        abort_unless($participant->participantable->belongsToConversation($this->conversation), 403, 'This user does not belong to conversation');
    }

    protected function removeParticipantFromLoadedList(Participant $participant): void
    {
        $this->participants = $this->participants->reject(function ($member) use ($participant) {
            return (string) $member->getKey() === (string) $participant->getKey()
                && $member->getMorphClass() === $participant->getMorphClass();
        });
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
