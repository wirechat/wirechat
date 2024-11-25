<?php

namespace Namu\WireChat\Livewire\Components;

use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Livewire\Modals\ModalComponent;

class NewChat extends ModalComponent
{
    public $users;

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

        //Make sure it's not empty
        if (blank($this->search)) {

            $this->users = null;
        } else {

            $this->users = auth()->user()->searchChatables($this->search);
        }
    }

    public function createConversation($id, string $class)
    {

        //resolve model from params -get model class
        $model = app($class);
        $model = $model::find($id);

        if ($model) {
            $createdConversation = auth()->user()->createConversationWith($model);

            if ($createdConversation) {
                $this->closeModal();

                return redirect()->route('chat', [$createdConversation->id]);
            }
        }
    }

    public function mount()
    {

        abort_unless(auth()->check(), 401);
        // abort_unless(WireChat::allowsNewChatModal(),503,'The NewChat feature is currently unavailable.');
    }

    public function render()
    {
        return view('wirechat::livewire.components.new-chat');
    }
}
