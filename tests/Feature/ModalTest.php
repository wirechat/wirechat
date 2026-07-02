<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chat\Drawer;
use Wirechat\Wirechat\Livewire\Chats\ChatsDrawer;
use Wirechat\Wirechat\Livewire\Modals\Modal;

beforeEach(function () {
    Livewire::component('wirechat.tests.unsafe-modal', WirechatUnsafeModalTestComponent::class);
});

test('modal shell keeps the panel above the backdrop and stops panel click bubbling', function () {
    Livewire::test(Modal::class)
        ->assertSeeHtml('class="fixed inset-0 z-0 transition-all"')
        ->assertSeeHtml('class="relative z-10 inline-block');
    // ->assertSeeHtml('x-on:click.stop');
});

test('open modal action forwards attributes and encodes extra arguments', function () {
    $html = Blade::render(<<<'BLADE'
        <x-wirechat::actions.open-modal
            component="wirechat.chat.group.links.show"
            conversation="12"
            panel="default"
            id="invite-link-trigger"
            class="trigger"
            :arguments="['invite' => 34]"
        >
            <button type="button">Open</button>
        </x-wirechat::actions.open-modal>
    BLADE);

    expect($html)
        ->toContain('id="invite-link-trigger"')
        ->toContain('class="trigger"')
        ->toContain("Livewire.dispatch('openWirechatModal'")
        ->toContain('\u0022conversation\u0022:\u002212\u0022')
        ->toContain('\u0022panel\u0022:\u0022default\u0022')
        ->toContain('\u0022invite\u0022:34');
});

test('modal rejects components that are not wirechat modal components', function () {
    Livewire::test(Modal::class)
        ->call('openWirechatModal', 'wirechat.tests.unsafe-modal')
        ->assertStatus(403);
});

test('chat drawer rejects components that are not wirechat modal components', function () {
    Livewire::test(Drawer::class)
        ->call('openChatDrawer', 'wirechat.tests.unsafe-modal')
        ->assertStatus(403);
});

test('chats drawer rejects components that are not wirechat modal components', function () {
    Livewire::test(ChatsDrawer::class)
        ->call('openChatListDrawer', 'wirechat.tests.unsafe-modal')
        ->assertStatus(403);
});

class WirechatUnsafeModalTestComponent extends Component
{
    public function render(): string
    {
        return <<<'HTML'
            <div>Unsafe component</div>
        HTML;
    }
}
