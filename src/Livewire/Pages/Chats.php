<?php

namespace Namu\WireChat\Livewire\Pages;

use Livewire\Attributes\Title;
use Livewire\Component;

class Chats extends Component
{
    #[Title('Chats')]
    public function render()
    {
        return view('wirechat::livewire.pages.chats')
            ->layout(config('wirechat.layout', 'wirechat::layouts.app'));

    }
}
