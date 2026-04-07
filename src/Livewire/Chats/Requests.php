<?php

namespace Wirechat\Wirechat\Livewire\Chats;

use Livewire\Attributes\Computed;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Models\MessageRequest;

class Requests extends ModalComponent
{
    use HasPanel;
    use Widget;

    public string $activeTab = 'incoming';

    public function mount(): void
    {
        abort_unless(auth()->check(), 401);

        $this->activeTab = $this->resolveDefaultTab();
    }

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'destroyOnClose' => true,
            'closeOnClickAway' => true,
        ];
    }

    #[Computed(persist: true)]
    public function auth()
    {
        return auth()->user();
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['incoming', 'outgoing'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    #[Computed]
    public function incomingRequests()
    {
        return Wirechat::messageRequestModelClass()::query()
            ->pending()
            ->whereRecipient($this->auth)
            ->whereHas('conversation')
            ->with([
                'sender',
                'conversation.lastMessage.participant.participantable',
            ])
            ->latest('created_at')
            ->get();
    }

    #[Computed]
    public function outgoingRequests()
    {
        return Wirechat::messageRequestModelClass()::query()
            ->pending()
            ->whereSender($this->auth)
            ->whereHas('conversation')
            ->with([
                'recipient',
                'conversation.lastMessage.participant.participantable',
            ])
            ->latest('created_at')
            ->get();
    }

    #[Computed]
    public function currentRequests()
    {
        return $this->activeTab === 'outgoing'
            ? $this->outgoingRequests
            : $this->incomingRequests;
    }

    #[Computed]
    public function hasRequests(): bool
    {
        return $this->incomingRequests->isNotEmpty() || $this->outgoingRequests->isNotEmpty();
    }

    #[Computed]
    public function currentEmptyState(): string
    {
        return $this->activeTab === 'outgoing'
            ? __('wirechat::chats.requests.labels.outgoing_empty_state')
            : __('wirechat::chats.requests.labels.incoming_empty_state');
    }

    public function openConversation(int $requestId)
    {
        /** @var MessageRequest $request */
        $request = Wirechat::messageRequestModelClass()::query()
            ->pending()
            ->where(function ($query) {
                $query->where(function ($recipientQuery) {
                    $recipientQuery->whereRecipient($this->auth);
                })->orWhere(function ($senderQuery) {
                    $senderQuery->whereSender($this->auth);
                });
            })
            ->with('conversation')
            ->findOrFail($requestId);

        $conversation = $request->conversation;

        abort_if(! $conversation || ! $this->auth->canAccessConversation($conversation), 403);

        $this->closeChatListDrawer();

        if ($this->isWidget()) {
            $this->openChat($conversation->id);

            return null;
        }

        return $this->redirect($this->panel()->chatRoute($conversation->id));
    }

    protected function resolveDefaultTab(): string
    {
        if ($this->incomingRequests->isNotEmpty()) {
            return 'incoming';
        }

        return $this->outgoingRequests->isNotEmpty() ? 'outgoing' : 'incoming';
    }

    public function render()
    {
        return view('wirechat::livewire.chats.requests');
    }
}
