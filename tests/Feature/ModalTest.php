<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Modals\Modal;

test('modal shell keeps the panel above the backdrop and stops panel click bubbling', function () {
    Livewire::test(Modal::class)
        ->assertSeeHtml('class="fixed inset-0 z-0 transition-all"')
        ->assertSeeHtml('class="relative z-10 inline-block');
    // ->assertSeeHtml('x-on:click.stop');
});
