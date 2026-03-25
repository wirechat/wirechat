<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Widgets\Wirechat;
use Wirechat\Wirechat\Models\Conversation;
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
    $response->assertSee('Select a conversation to start messaging');

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

test('it forwards listClass and chatClass to the widget children', function () {
    $auth = User::factory()->create();
    $conversation = $auth->createConversationWith(User::factory()->create());

    $response = Livewire::actingAs($auth)->test(Wirechat::class, [
        'listClass' => 'widget-list-shell-test',
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
