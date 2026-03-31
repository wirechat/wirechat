<?php

namespace Wirechat\Wirechat\Livewire\Chat;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Models\Conversation;

/**
 * Info Component
 *
 * @property \Illuminate\Database\Eloquent\Model|\Wirechat\Wirechat\Contracts\Participantable|null $participantable
 */
class Info extends ModalComponent
{
    use HasPanel;
    use Widget;

    #[Locked]
    public Conversation $conversation;

    public static function closeModalOnEscapeIsForceful(): bool
    {
        return false;
    }

    /**
     * Returns the authenticated participantable.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Wirechat\Wirechat\Contracts\Participantable|null
     */
    #[Computed(persist: true)]
    public function participantable()
    {
        return Wirechat::getParticipantable();
    }

    /**
     * @deprecated Use participantable() instead.
     */
    public function sendable()
    {
        return $this->participantable();
    }

    /**
     * @deprecated Use participantable() instead.
     */
    public function user()
    {
        return $this->participantable();
    }

    /**
     * @deprecated Use participantable() instead.
     */
    public function participant()
    {
        return $this->participantable();
    }

    /**
     * -----------------------------
     * Delete Chat
     * */
    public function deleteChat()
    {
        abort_unless(auth()->check(), 401);

        abort_unless($this->participantable->belongsToConversation($this->conversation), 403);
        abort_unless($this->conversation->isSelf() || $this->conversation->isPrivate(), 403, 'This operation is not available for Groups.');

        // delete conversation
        $this->conversation->deleteFor($this->participantable);

        // redirect to chats page pr
        // Dispatach event instead if isWidget
        // handle widget termination
        $this->handleComponentTermination(
            redirectRoute: $this->panel()->chatsRoute(),
            events: [
                'close-chat',
                Chats::class => ['chat-deleted',  [$this->conversation->id]],
            ]
        );

    }

    public function placeholder()
    {
        return <<<'HTML'
        <div>
            <!-- Loading spinner... -->
            <x-wirechat::loading-spin class="m-auto" />
        </div>
        HTML;
    }

    public function mount()
    {

        abort_if(empty($this->conversation), 404);

        abort_unless(auth()->check(), 401);
        abort_unless($this->participantable->belongsToConversation($this->conversation), 403);

        abort_if($this->conversation->isGroup(), 403, __('wirechat::chat.info.messages.invalid_conversation_type_error'));

        // load participants
        $this->conversation->load('participants.participantable');

    }

    public function render()
    {

        $receiver = $this->conversation->peerParticipant($this->participantable)?->participantable;

        // Pass data to the view
        return view('wirechat::livewire.chat.info', [
            'receiver' => $receiver,
            'wirechat_avatar_url' => $receiver?->wirechat_avatar_url,
        ]);
    }
}
