<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chats\ChatsDrawer;

test('chats drawer stays scoped to the chats shell without locking page scroll', function () {
    $html = Livewire::test(ChatsDrawer::class)->html();

    expect($html)
        ->toContain('id="chats-drawer"')
        ->toContain('class="pointer-events-none absolute inset-0 z-50 h-full overflow-y-auto"')
        ->toContain('class="pointer-events-auto relative h-full overflow-x-hidden bg-[var(--wc-light-primary)] text-left dark:bg-[var(--wc-dark-primary)]"')
        ->toContain('x-show="show && showActiveComponent"')
        ->toContain('class="h-full w-full transition-all"')
        ->not->toContain('x-on:click.self="closeChatListDrawerOnClickAway()"')
        ->not->toContain('x-trap.noscroll.inert="show && showActiveComponent"')
        ->toContain('this.closeOnClickAway = attributes.closeOnClickAway ?? false')
        ->not->toContain('attributes.closeModalOnClickAway')
        ->not->toContain("document.body.classList.add('overflow-y-hidden');")
        ->not->toContain("document.body.classList.remove('overflow-y-hidden');");
});
