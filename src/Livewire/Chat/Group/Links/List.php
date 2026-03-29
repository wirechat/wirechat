<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Links;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Models\Conversation;

class ListComponent extends Component
{
    use HasPanel;

    protected $listeners = [
        'refreshGroupInvites' => '$refresh',
    ];

    #[Locked]
    public Conversation $conversation;

    public int $perPage = 3;

    protected int $perPageStep = 10;

    public function mount(): void
    {
        $this->initializePanel($this->panel);

        $this->authorizeAccess('You do not have permission to manage group invite links');

        $this->conversation = $this->conversation->load('group');
    }

    public function loadMore(): void
    {
        $this->authorizeAccess('You do not have permission to load more group invite links');

        $this->perPage += $this->perPageStep;
    }

    public function render()
    {
        $this->authorizeAccess('You do not have permission to view group invite links');

        $group = $this->conversation->loadMissing('group')->group;

        $invites = $group->inviteLinks()
            ->active()
            ->where('panel_id', $this->panel()->getId())
            ->additional()
            ->with('createdBy')
            ->latest('id')
            ->take($this->perPage + 1)
            ->get();

        $canLoadMore = $invites->count() > $this->perPage;

        return view('wirechat::livewire.chat.group.links.list', [
            'additionalInvites' => $invites->take($this->perPage),
            'canLoadMore' => $canLoadMore,
        ]);
    }

    protected function authorizeAccess(string $message): void
    {
        abort_unless($this->panel()->hasGroupInvitations(), 404);

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403, $message);
        abort_if($this->conversation->isPrivate(), 403, 'This feature is only available for groups');

        $participant = $this->conversation->participant(auth()->user());

        abort_unless($participant?->isAdmin(), 403, $message);
    }
}

class_alias(ListComponent::class, __NAMESPACE__.'\\List');
