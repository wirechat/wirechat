<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Members;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class PastMembers extends ModalComponent
{
    use HasPanel;

    #[Locked]
    public Conversation $conversation;

    public $group;

    public $search;

    public $pastMembers;

    public int $perPage = 10;

    public int $page = 1;

    public bool $canLoadMore = false;

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
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($this->authParticipant?->isAdmin(), 403, 'You do not have permission to view past members');

        $this->loadPastMembers();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->loadPastMembers();
    }

    public function loadMore(): void
    {
        if (! $this->canLoadMore) {
            return;
        }

        do {
            $this->page++;
            $addedCount = $this->loadPastMembers();
        } while ($addedCount === 0 && $this->canLoadMore);
    }

    public function render()
    {
        return view('wirechat::livewire.chat.group.members.past');
    }

    protected function loadPastMembers(): int
    {
        $searchableFields = $this->panel()->getSearchableAttributes();
        $columnCache = [];

        $this->pastMembers = $this->pastMembers ?? collect();
        if ($this->page === 1) {
            $this->pastMembers = collect();
        }

        $beforeCount = $this->pastMembers->count();

        $participants = $this->conversation->participants()
            ->withoutGlobalScopes()
            ->with(['participantable', 'actions.actor.participantable'])
            ->where(function ($query) {
                $query->whereNotNull('exited_at')
                    ->orWhereHas('actions', function ($actionQuery) {
                        $actionQuery->whereIn('type', [
                            Actions::REMOVED_BY_ADMIN->value,
                            ...Actions::bannedValues(),
                        ]);
                    });
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
            ->paginate($this->perPage, ['*'], 'page', $this->page);

        /** @var \Illuminate\Support\Collection<int, Participant> $filtered */
        $filtered = collect($participants->items())
            ->filter(fn (Participant $participant) => $participant->pastMembershipReason() !== null)
            ->values();

        $this->pastMembers = $this->pastMembers->merge($filtered)->unique('id')->values();
        $this->canLoadMore = $participants->hasMorePages();

        return $this->pastMembers->count() - $beforeCount;
    }
}
