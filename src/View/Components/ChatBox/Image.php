<?php

namespace Namu\WireChat\View\Components\ChatBox;

use Illuminate\View\Component;
use Illuminate\View\View;

class Image extends Component
{
    public function __construct(
        public $previousMessage,
        public $message,
        public $nextMessage,
        public $belongsToAuth,
        public $attachment,

    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('wirechat::components.chatbox.image');
    }
}
