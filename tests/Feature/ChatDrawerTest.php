<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chat\Drawer;

test('chat drawer stays scoped to the chat shell without locking page scroll', function () {
    $html = Livewire::test(Drawer::class)->html();

    expect($html)
        ->toContain('class="pointer-events-none absolute inset-0 z-50 h-full overflow-y-auto overscroll-contain"')
        ->toContain('class="pointer-events-auto relative h-full overflow-x-hidden bg-[var(--wc-light-primary)] text-left dark:bg-[var(--wc-dark-primary)] dark:text-white"')
        ->toContain('x-show="show && showActiveComponent"')
        ->toContain('x-transition:enter-start="opacity-0 translate-x-full"')
        ->toContain('x-transition:leave-end="opacity-0 translate-x-full"')
        ->toContain('class="relative h-full w-full overflow-x-hidden overscroll-contain transition-all"')
        ->toContain('previousDrawerComponent: false')
        ->toContain('drawerComponentTransitionDirection')
        ->toContain('shouldShowDrawerComponent(id)')
        ->toContain('drawerComponentTransitionClasses(id)')
        ->toContain('translate-x-full opacity-100')
        ->toContain('-translate-x-full opacity-100')
        ->not->toContain('this.showActiveComponent = false;')
        ->not->toContain('x-on:click.self="closeChatDrawerOnClickAway()"')
        ->not->toContain('x-trap.noscroll.inert="show && showActiveComponent"')
        ->toContain('this.closeOnClickAway = attributes.closeOnClickAway ?? false')
        ->not->toContain('attributes.closeModalOnClickAway')
        ->not->toContain("document.body.classList.add('overflow-y-hidden');")
        ->not->toContain("document.body.classList.remove('overflow-y-hidden');");
});
