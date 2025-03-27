<?php

namespace Namu\WireChat\Livewire\New;

use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Concerns\ModalComponent;
use Namu\WireChat\Livewire\Concerns\Widget;
use Namu\WireChat\Livewire\Widgets\WireChat as WidgetsWireChat;

class Chat extends ModalComponent
{
    use Widget;

    public $users = [];

    public $search;

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => true,
            'destroyOnClose' => true,
            'closeOnClickAway' => true,
        ];

    }

    /**
     * Search For users to create conversations with
     */
    public function updatedsearch()
    {

        // Make sure it's not empty
        if (blank($this->search)) {

            $this->users = [];
        } else {

            $this->users = auth()->user()->searchChatables($this->search);
        }
    }

    public function createConversation($id, string $class)
    {

        // resolve model from params -get model class
        $model = app($class);
        $model = $model::find($id);

        if ($model) {
            $createdConversation = auth()->user()->createConversationWith($model);

            if ($createdConversation) {

                // close dialog
                $this->closeWireChatModal();

                // redirect to conversation
                $this->handleComponentTermination(
                    redirectRoute: route(WireChat::viewRouteName(), [$createdConversation->id]),
                    events: [
                        WidgetsWireChat::class => ['open-chat',  ['conversation' => $createdConversation->id]],
                    ]
                );

            }
        }
    }

    public function mount()
    {

        abort_unless(auth()->check(), 401);
    }

    public function render()
    {
        return view('wirechat::livewire.new.chat');
    }
}
