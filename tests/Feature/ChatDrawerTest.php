<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chat\Drawer;

test('chat drawer stays scoped to the chat shell without locking page scroll', function () {
    $html = Livewire::test(Drawer::class)->html();

    expect($html)
        ->toContain('class="pointer-events-none absolute inset-0 z-50 h-full overflow-y-auto overscroll-contain"')
        ->toContain('class="pointer-events-auto h-full w-full overscroll-contain bg-[var(--wc-light-primary)] transition-all dark:bg-[var(--wc-dark-primary)] dark:text-white"')
        ->not->toContain('x-on:click.self="closeChatDrawerOnClickAway()"')
        ->not->toContain('x-trap.noscroll.inert="show && showActiveComponent"')
        ->toContain('this.closeOnClickAway = attributes.closeOnClickAway ?? false')
        ->not->toContain('attributes.closeModalOnClickAway')
        ->not->toContain("document.body.classList.add('overflow-y-hidden');")
        ->not->toContain("document.body.classList.remove('overflow-y-hidden');");
});
