<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Wirechat\Wirechat\Enums\ColorTone;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Widgets\Wirechat;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelRegistry;
use Wirechat\Wirechat\Support\Color;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation = Conversation::factory()->create();
    Livewire::test(Wirechat::class)
        ->assertStatus(401);
});

test('it renders livewire ChatList component', function () {
    $auth = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $response = Livewire::actingAs($auth)->test(Wirechat::class);
    $response->assertSeeLivewire(Chats::class);

});

test('it doest not render livewire ChatBox component', function () {
    $auth = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $response = Livewire::actingAs($auth)->test(Wirechat::class);
    $response->assertDontSeeLivewire(Chat::class);

});

test('it shows label "Send private photos and messages" ', function () {
    $auth = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $response = Livewire::actingAs($auth)->test(Wirechat::class);
    $response->assertSee('Choose a conversation to start messaging.');

});

test('it renders Chat when "openChatWidget" event is selected ', function () {
    $auth = User::factory()->create();

    $conversation = $auth->createConversationWith(User::factory()->create());
    $response = Livewire::actingAs($auth)->test(Wirechat::class);

    $response->assertDontSeeLivewire(Chat::class);

    $response->dispatch('openChatWidget', conversation: $conversation->id);

    // dd($response);
    $response->assertSeeLivewire(Chat::class);

});

test('it renders Chat when widget event payload contains a conversation key', function () {
    $auth = User::factory()->create();
    $conversation = $auth->createConversationWith(User::factory()->create());

    Livewire::actingAs($auth)->test(Wirechat::class)
        ->call('openChatWidget', ['conversation' => $conversation->id])
        ->assertSet('selectedConversationId', $conversation->id)
        ->assertSeeLivewire(Chat::class);
});

test('it removes Chat when "closeChatWidget" event is selected ', function () {
    $auth = User::factory()->create();

    $conversation = $auth->createConversationWith(User::factory()->create());
    $response = Livewire::actingAs($auth)->test(Wirechat::class);

    // assert
    $response->assertDontSeeLivewire(Chat::class);

    // open
    $response->dispatch('openChatWidget', conversation: $conversation->id);

    // assert
    $response->assertSeeLivewire(Chat::class);

    // open
    $response->dispatch('closeChatWidget');

    // assert
    $response->assertDontSeeLivewire(Chat::class);

});

test('it applies ui classes and styles to the widget shell only', function () {
    $auth = User::factory()->create();

    $response = Livewire::actingAs($auth)->test(Wirechat::class, [
        'class' => 'border-none shadow-none',
        'styles' => [
            'min-height' => '32rem',
            'border-radius' => '0',
        ],
    ]);

    $html = $response->html();

    preg_match_all('/class="[^"]*rounded-lg border-none shadow-none[^"]*"/', $html, $classMatches);
    preg_match_all('/style="min-height: 32rem; border-radius: 0;"/', $html, $styleMatches);

    expect($classMatches[0])->toHaveCount(1)
        ->and($styleMatches[0])->toHaveCount(1);
});

test('it centers the widget empty state across the chat panel', function () {
    $html = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/widgets/wire-chat.blade.php');

    expect($html)
        ->toContain('dusk="widget-empty-state"')
        ->toContain('absolute inset-0 flex items-center justify-center px-4 text-center')
        ->toContain("@lang('wirechat::widgets.wirechat.messages.welcome')");
});

test('blade component attributes do not contain uncompiled js directives', function () {
    $views = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/resources/views')
    );

    $matches = [];

    foreach ($views as $view) {
        if (! $view->isFile() || $view->getExtension() !== 'php') {
            continue;
        }

        preg_match_all('/<x-[^>]*@js\([^>]*>/m', file_get_contents($view->getPathname()), $componentMatches);

        foreach ($componentMatches[0] as $match) {
            $matches[] = str_replace(dirname(__DIR__, 2).'/', '', $view->getPathname()).': '.$match;
        }
    }

    expect($matches)->toBeEmpty();
});

test('wirechat styles uses the dark palette and supports extending zinc shades', function () {
    $customDark = [
        900 => 'oklch(0.18 0.01 285.9)',
        800 => 'oklch(0.24 0.01 286.0)',
        700 => 'oklch(0.31 0.01 286.1)',
    ];

    testPanelProvider()->colors([
        'dark' => $customDark,
    ]);

    $styles = Blade::render('@wirechatStyles(panel: "test")');

    expect($styles)
        ->toContain('--wc-dark-primary: '.$customDark[900].';')
        ->toContain('--wc-dark-secondary: '.$customDark[800].';')
        ->toContain('--wc-dark-accent: '.$customDark[700].';')
        ->toContain('--wc-light-secondary: '.Color::Zinc[100].';');
});

test('wirechat styles use soft color tone by default', function () {
    app(PanelRegistry::class)->register(
        Panel::make()
            ->id('tone-soft')
            ->path('tone-soft')
            ->colors([
                'primary' => Color::Emerald,
            ])
    );

    $styles = Blade::render('@wirechatStyles(tone-soft)');

    expect($styles)
        ->toContain('--wc-primary-500: '.Color::Emerald[500].';')
        ->toContain('--wc-primary-tone-bg: color-mix(in srgb, var(--wc-primary-300) 35%, transparent);')
        ->toContain('--wc-primary-tone-text: rgb(24 24 27);')
        ->toContain('--wc-primary-tone-dark-bg: color-mix(in srgb, var(--wc-primary-300) 40%, transparent);')
        ->toContain('--wc-primary-tone-dark-text: #fff;');
});

test('wirechat styles can use solid color tone for primary surfaces', function () {
    app(PanelRegistry::class)->register(
        Panel::make()
            ->id('tone-solid')
            ->path('tone-solid')
            ->colors([
                'primary' => Color::Rose,
            ])
            ->colorTone(ColorTone::Solid)
    );

    $styles = Blade::render('@wirechatStyles(tone-solid)');

    expect($styles)
        ->toContain('--wc-primary-500: '.Color::Rose[500].';')
        ->toContain('--wc-primary-tone-bg: var(--wc-primary-500);')
        ->toContain('--wc-primary-tone-text: #fff;')
        ->toContain('--wc-primary-tone-dark-bg: var(--wc-primary-600);')
        ->toContain('--wc-primary-tone-dark-text: #fff;');
});

test('it forwards chatsClass and chatClass to the widget children', function () {
    $auth = User::factory()->create();
    $conversation = $auth->createConversationWith(User::factory()->create());

    $response = Livewire::actingAs($auth)->test(Wirechat::class, [
        'chatsClass' => 'widget-list-shell-test',
        'chatClass' => 'widget-chat-shell-test',
    ]);

    $html = $response->html();

    preg_match_all('/class="[^"]*widget-list-shell-test[^"]*"/', $html, $listClassMatches);
    preg_match_all('/class="[^"]*widget-chat-shell-test[^"]*"/', $html, $chatClassMatchesBeforeOpen);

    expect($listClassMatches[0])->toHaveCount(1)
        ->and($chatClassMatchesBeforeOpen[0])->toHaveCount(0);

    $response->dispatch('openChatWidget', conversation: $conversation->id);

    $html = $response->html();

    preg_match_all('/class="[^"]*widget-chat-shell-test[^"]*"/', $html, $chatClassMatchesAfterOpen);

    expect($chatClassMatchesAfterOpen[0])->toHaveCount(1);
});

test('it keeps the widget chat panel scoped to the widget shell without locking page scroll', function () {
    $auth = User::factory()->create();

    $html = Livewire::actingAs($auth)->test(Wirechat::class)->html();

    expect($html)
        ->toContain('class="absolute inset-0" id="chatwidget-container"')
        ->not->toContain("document.body.classList.add('overflow-y-hidden');")
        ->not->toContain("document.body.classList.remove('overflow-y-hidden');");
});

test('it keeps a single shared chat drawer mounted in the widget shell', function () {
    $auth = User::factory()->create();
    $conversation = $auth->createConversationWith(User::factory()->create());

    $response = Livewire::actingAs($auth)->test(Wirechat::class);

    $children = $response->snapshot['memo']['children'] ?? [];
    $hasDrawerInSnapshot = array_key_exists('widget-chat-drawer', $children);

    if ($hasDrawerInSnapshot) {
        expect($hasDrawerInSnapshot)->toBeTrue();
    } else {
        preg_match_all('/wire:key="widget-chat-drawer"/', $response->html(), $drawerMatches);
        expect($drawerMatches[0])->toHaveCount(1);
    }

    $response->dispatch('openChatWidget', conversation: $conversation->id);

    $childrenAfterOpen = $response->snapshot['memo']['children'] ?? [];
    $hasDrawerAfterOpen = array_key_exists('widget-chat-drawer', $childrenAfterOpen);

    if ($hasDrawerAfterOpen) {
        expect($hasDrawerAfterOpen)->toBeTrue();
    } else {
        preg_match_all('/wire:key="widget-chat-drawer"/', $response->html(), $drawerMatchesAfterOpen);
        expect($drawerMatchesAfterOpen[0])->toHaveCount(1);
    }
});

test('widget chat does not mount its own drawer instance', function () {
    $auth = User::factory()->create();
    $conversation = $auth->createConversationWith(User::factory()->create());

    $html = Livewire::actingAs($auth)->test(Chat::class, [
        'conversation' => $conversation->id,
        'widget' => true,
    ])->html();

    expect($html)->not->toContain('id="chat-drawer"');
});
