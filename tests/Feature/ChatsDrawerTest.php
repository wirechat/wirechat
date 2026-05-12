<?php

use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chats\ChatsDrawer;

test('chats drawer stays scoped to the chats shell without locking page scroll', function () {
    $html = Livewire::test(ChatsDrawer::class)->html();

    expect($html)
        ->toContain('id="chats-drawer"')
        ->toContain('class="absolute inset-0 z-50 h-full overflow-y-auto bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]"')
        ->toContain('x-on:click.self="closeChatListDrawerOnClickAway()"')
        ->toContain('this.closeOnClickAway = attributes.closeOnClickAway ?? false')
        ->not->toContain('attributes.closeModalOnClickAway')
        ->not->toContain("document.body.classList.add('overflow-y-hidden');")
        ->not->toContain("document.body.classList.remove('overflow-y-hidden');");
});
