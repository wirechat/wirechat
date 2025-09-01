<?php

namespace Wirechat\Wirechat\Livewire\Pages;

use Livewire\Attributes\Title;
use Livewire\Component;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;

class Chats extends Component
{
    use HasPanel;

    #[Title('Chats')]
    public function render()
    {

        return view('wirechat::livewire.pages.chats')
            ->layout($this->panel()->getLayout());

    }
}
