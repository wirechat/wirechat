<?php

namespace Wirechat\Wirechat\Livewire\Pages;

use Livewire\Attributes\Title;
use Livewire\Component;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Services\WirechatService;

class Chat extends Component
{
    public $conversation;

    use HasPanel;

    public function mount()
    {
        // /make sure user is authenticated
        abort_unless(auth()->check(), 401);

        // We remove deleted conversation incase the user decides to visit the delted conversation
        $this->conversation = WirechatService::conversationModelClass()::where('id', $this->conversation)->firstOrFail();

        // Check if the user belongs to the conversation
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);

    }

    #[Title('Chats')]
    public function render()
    {
        return view('wirechat::livewire.pages.chat')
            ->layout($this->panel()->getLayout());
    }
}
