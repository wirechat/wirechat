<?php

namespace Wirechat\Wirechat\Livewire\Chat\Group\Join;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Enums\JoinRequestStatus;
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

    public int $visibleRequestCount = 5;

    public array $loadedRequestIds = [];

    public bool $hasMoreRequests = false;

    public $group;

    protected ?Participant $authParticipant = null;

    protected int $perPageStep = 5;

    protected int $bulkActionChunkSize = 100;

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

        $this->syncLoadedRequests();
    }

    public function approve(int $requestId): void
    {
        $reviewedBy = $this->authorizeAdmin();
        $request = $this->pendingRequestsQuery()->findOrFail($requestId);

        $this->reviewRequest($request, $reviewedBy);

        $this->syncLoadedRequests();
        $this->refreshSurfaces();
        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.join.requests.messages.approved_success'));
    }

    public function dismiss(int $requestId): void
    {
        $reviewedBy = $this->authorizeAdmin();
        $request = $this->pendingRequestsQuery()->findOrFail($requestId);

        $request->dismiss($reviewedBy);

        $this->syncLoadedRequests();
        $this->refreshSurfaces();
        $this->dispatch('wirechat-toast', type: 'success', message: __('wirechat::chat.group.join.requests.messages.dismissed_success'));
    }

    public function approveAll(): void
    {
        $reviewedBy = $this->authorizeAdmin();
        $approvedCount = 0;

        $this->group->pendingJoinRequests()
            ->with(['requester', 'invite'])
            ->orderBy('id')
            ->chunkById($this->bulkActionChunkSize, function ($requests) use (&$approvedCount, $reviewedBy) {
                foreach ($requests as $request) {
                    $this->reviewRequest($request, $reviewedBy);
                    $approvedCount++;
                }
            });

        $this->syncLoadedRequests();
        $this->refreshSurfaces();

        if ($approvedCount > 0) {
            $this->dispatch(
                'wirechat-toast',
                type: 'success',
                message: trans_choice('wirechat::chat.group.join.requests.messages.approved_all_success', $approvedCount, ['count' => $approvedCount]),
            );

            $this->dispatch('refresh')->to(Info::class);
        }
    }

    public function dismissAll(): void
    {
        $reviewedBy = $this->authorizeAdmin();
        $dismissedCount = $this->group->pendingJoinRequests()->count();

        if ($dismissedCount > 0) {
            $this->group->pendingJoinRequests()->update([
                'status' => JoinRequestStatus::DISMISSED,
                'reviewed_by_id' => $reviewedBy->getKey(),
                'reviewed_by_type' => $reviewedBy->getMorphClass(),
                'reviewed_at' => now(),
            ]);
        }

        $this->syncLoadedRequests();
        $this->refreshSurfaces();

        if ($dismissedCount > 0) {
            $this->dispatch(
                'wirechat-toast',
                type: 'success',
                message: trans_choice('wirechat::chat.group.join.requests.messages.dismissed_all_success', $dismissedCount, ['count' => $dismissedCount]),
            );

            $this->dispatch('refresh')->to(Info::class);
        }
    }

    public function loadMore(): void
    {
        $this->visibleRequestCount += $this->perPageStep;
        $this->syncLoadedRequests();
    }

    public function render()
    {
        $requests = empty($this->loadedRequestIds)
            ? collect()
            : $this->pendingRequestsQuery()
                ->whereKey($this->loadedRequestIds)
                ->get();

        return view('wirechat::livewire.chat.group.join.requests', [
            'requests' => $requests,
            'hasMoreRequests' => $this->hasMoreRequests,
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
        $pendingCount = $this->group->pendingJoinRequests()->count();

        $this->dispatch(
            'wirechat-join-requests-banner-updated',
            conversationId: $this->conversation->id,
            count: $pendingCount,
            summary: trans_choice('wirechat::chat.group.join.requests.labels.summary', $pendingCount, ['count' => $pendingCount]),
        );
    }

    protected function syncLoadedRequests(): void
    {
        $currentPendingIds = $this->group->pendingJoinRequests()
            ->whereKey($this->loadedRequestIds)
            ->pluck('id')
            ->all();

        $this->loadedRequestIds = array_values(array_map('intval', $currentPendingIds));

        while (count($this->loadedRequestIds) < $this->visibleRequestCount) {
            $missingCount = $this->visibleRequestCount - count($this->loadedRequestIds);

            $nextRequestIds = $this->pendingRequestsQuery()
                ->when($this->loadedRequestIds !== [], fn ($query) => $query->whereNotIn('id', $this->loadedRequestIds))
                ->limit($missingCount)
                ->pluck('id')
                ->all();

            if ($nextRequestIds === []) {
                break;
            }

            $this->loadedRequestIds = array_values(array_unique([
                ...$this->loadedRequestIds,
                ...array_map('intval', $nextRequestIds),
            ]));
        }

        $this->hasMoreRequests = $this->pendingRequestsQuery()
            ->when($this->loadedRequestIds !== [], fn ($query) => $query->whereNotIn('id', $this->loadedRequestIds))
            ->exists();
    }
}
