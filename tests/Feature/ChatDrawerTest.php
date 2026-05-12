<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chat\Drawer;

test('chat drawer stays scoped to the chat shell without locking page scroll', function () {
    $html = Livewire::test(Drawer::class)->html();

    expect($html)
//        ->toContain('class="absolute bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]  dark:text-white opacity-100 inset-0 z-50 h-full overflow-y-auto"')
        ->toContain('x-on:click.self="closeChatDrawerOnClickAway()"')
        ->toContain('this.closeOnClickAway = attributes.closeOnClickAway ?? false')
        ->not->toContain('attributes.closeModalOnClickAway')
        ->not->toContain("document.body.classList.add('overflow-y-hidden');")
        ->not->toContain("document.body.classList.remove('overflow-y-hidden');");
});
