<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Members;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class Banned extends ModalComponent
{
    use HasPanel;

    #[Locked]
    public Conversation $conversation;

    public $group;

    public $search;

    public $bannedMembers;

    protected ?Participant $authParticipant = null;

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

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, 'You do not have permission to access this resource');
        abort_if($this->conversation->isPrivate(), 403, 'This feature is only available for groups');

        $this->conversation = $this->conversation->load('group');
        $this->group = $this->conversation->group;

        $this->authorizeBannedMembersAccess('You do not have permission to view banned members');

        $this->loadBannedMembers();
    }

    public function updatedSearch(): void
    {
        $this->loadBannedMembers();
    }

    public function liftBan(int $participantId): void
    {
        $this->authorizeBannedMembersAccess('You do not have permission to lift bans');

        $participant = $this->conversation->participants()
            ->withoutGlobalScopes()
            ->with(['participantable', 'actions'])
            ->whereKey($participantId)
            ->firstOrFail();
        /** @var Participant $participant */
        abort_unless($participant->isBlockedByAdmin(), 404, 'Member is not blocked.');

        $participant->liftBlockByAdmin();

        $this->loadBannedMembers();

        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.banned_members.messages.unbanned_success', ['member' => $participant->participantable->wirechat_name]));
        $this->dispatch('refresh')->to(Members::class);
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.members.banned');
    }

    protected function authorizeBannedMembersAccess(string $message): void
    {
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($this->authParticipant?->isAdmin(), 403, $message);
    }

    protected function loadBannedMembers(): void
    {
        $searchableFields = $this->panel()->getSearchableAttributes();
        $columnCache = [];

        $this->bannedMembers = $this->conversation->participants()
            ->withoutGlobalScopes()
            ->with(['participantable', 'actions.actor.participantable'])
            ->whereHas('actions', function ($query) {
                $query->where('type', Actions::BLOCKED_BY_ADMIN->value);
            })
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
            ->latest('updated_at')
            ->get()
            ->values();
    }
}
