<?php

namespace Namu\WireChat\Livewire\Pages;

use Livewire\Attributes\Title;
use Livewire\Component;
use Namu\WireChat\Livewire\Concerns\HasPanel;

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
