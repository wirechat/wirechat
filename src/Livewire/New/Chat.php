<?php

namespace Wirechat\Wirechat\Livewire\New;

use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\Widget;
use Wirechat\Wirechat\Livewire\Widgets\Wirechat as WidgetsWirechat;

class Chat extends ModalComponent
{
    use HasPanel;
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

            /**
             * todo: migrate search chantable to channel
             */
            $this->users = $this->panel()->searchUsers($this->search)->resolve();
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
                $this->closeWirechatModal();

                // redirect to conversation
                $this->handleComponentTermination(
                    redirectRoute: $this->panel()->chatRoute($createdConversation->id),
                    events: [
                        WidgetsWirechat::class => ['open-chat',  ['conversation' => $createdConversation->id]],
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
