<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Join;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chat\Group\Info;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Participant;

class Requests extends ModalComponent
{
    use HasPanel;

    #[Locked]
    public Conversation $conversation;

    public int $perPage = 10;

    public $group;

    protected ?Participant $authParticipant = null;

    protected int $perPageStep = 10;

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'destroyOnClose' => true,
        ];
    }

    public function mount(): void
    {
        $this->initializePanel($this->panel);

        abort_unless($this->panel()->hasGroupInvitations(), 404);

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);
        abort_if($this->conversation->isPrivate(), 403, 'This feature is only available for groups');

        $this->conversation = $this->conversation->load('group.cover');
        $this->group = $this->conversation->group;
        $this->authParticipant = $this->conversation->participant(auth()->user());

        abort_unless($this->authParticipant?->isAdmin(), 403, 'Only admins can manage join requests.');
    }

    public function approve(int $requestId): void
    {
        $reviewedBy = $this->authorizeAdmin();
        $request = $this->pendingRequestsQuery()->findOrFail($requestId);

        $this->reviewRequest($request, $reviewedBy);

        $this->refreshSurfaces();
        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.join.requests.messages.approved_success'));
    }

    public function dismiss(int $requestId): void
    {
        $reviewedBy = $this->authorizeAdmin();
        $request = $this->pendingRequestsQuery()->findOrFail($requestId);

        $request->dismiss($reviewedBy);

        $this->refreshSurfaces();
        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.join.requests.messages.dismissed_success'));
    }

    public function approveAll(): void
    {
        $reviewedBy = $this->authorizeAdmin();
        $requests = $this->pendingRequestsQuery()->get();

        $approvedCount = 0;

        foreach ($requests as $request) {
            $this->reviewRequest($request, $reviewedBy);
            $approvedCount++;
        }

        $this->refreshSurfaces();

        if ($approvedCount > 0) {
            $this->dispatch(
                'wirechat-toast',
                type: 'success',
                message: trans_choice('wirechat::chat.group.join.requests.messages.approved_all_success', $approvedCount, ['count' => $approvedCount]),
            );
        }
    }

    public function dismissAll(): void
    {
        $reviewedBy = $this->authorizeAdmin();
        $requests = $this->pendingRequestsQuery()->get();

        $dismissedCount = 0;

        foreach ($requests as $request) {
            $request->dismiss($reviewedBy);
            $dismissedCount++;
        }

        $this->refreshSurfaces();

        if ($dismissedCount > 0) {
            $this->dispatch(
                'wirechat-toast',
                type: 'success',
                message: trans_choice('wirechat::chat.group.join.requests.messages.dismissed_all_success', $dismissedCount, ['count' => $dismissedCount]),
            );
        }
    }

    public function loadMore(): void
    {
        $this->perPage += $this->perPageStep;
    }

    public function render()
    {
        $requests = $this->pendingRequestsQuery()
            ->limit($this->perPage)
            ->get();

        $totalRequests = $this->group->pendingJoinRequests()->count();

        return view('wirechat::livewire.chat.group.join.requests', [
            'requests' => $requests,
            'hasMoreRequests' => $totalRequests > $requests->count(),
        ]);
    }

    protected function pendingRequestsQuery()
    {
        return $this->group->pendingJoinRequests()
            ->with(['requester', 'invite'])
            ->latest();
    }

    protected function authorizeAdmin(): Model|Authenticatable
    {
        $reviewedBy = auth()->user();
        $authParticipant = $this->conversation->participant($reviewedBy);

        abort_unless($authParticipant?->isAdmin(), 403, 'Only admins can manage join requests.');

        return $reviewedBy;
    }

    protected function reviewRequest($request, Model|Authenticatable $reviewedBy): void
    {
        $requester = $request->requester;

        if ($requester instanceof Model) {
            if (! $requester->belongsToConversation($this->conversation)) {
                $this->conversation->join($requester, $reviewedBy);
            } else {
                $request->approve($reviewedBy);
            }
        } else {
            $request->dismiss($reviewedBy);
        }
    }

    protected function refreshSurfaces(): void
    {
        $this->dispatch('refresh')->to(Info::class);
        $this->dispatch('refresh')->to(Chat::class);
    }
}
