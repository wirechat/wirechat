<?php

use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;
use Wirechat\Wirechat\Support\Enums\UnReadType;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

beforeEach(function () {
    testPanelProvider()->registerRoutes(true);
    testPanelProvider()->groupInvitations(true);
    testPanelProvider()->invitePageLayout('wirechat::layouts.app');
    testPanelProvider()->inviteJoinRedirect(null);
});

test(' panel hasRoutes is true by default()', function () {
    $auth = User::factory()->create();

    expect(testPanelProvider()->hasRoutes())->toBeTrue();

});

test(' panel hasRoutes is false when registerRoutes is FALSE', function () {
    $auth = User::factory()->create();

    testPanelProvider()->registerRoutes(false);

    expect(testPanelProvider()->hasRoutes())->toBeFalse();

});

test('panel hasGroupInvitations is true by default()', function () {
    expect(testPanelProvider()->hasGroupInvitations())->toBeTrue();
});

test('panel hasGroupInvitations is false when disabled', function () {
    testPanelProvider()->groupInvitations(false);

    expect(testPanelProvider()->hasGroupInvitations())->toBeFalse();
});

test('panel invitePageLayout defaults to wirechat app layout', function () {
    expect(testPanelProvider()->getInvitePageLayout())->toBe('wirechat::layouts.app');
});

test('panel invitePageLayout can be customized', function () {
    testPanelProvider()->invitePageLayout('layouts.guest');

    expect(testPanelProvider()->getInvitePageLayout())->toBe('layouts.guest');
});

test('panel inviteJoinRedirect is null by default', function () {
    expect(testPanelProvider()->getInviteJoinRedirectUrl())->toBeNull();
});

test('panel inviteJoinRedirect can be customized', function () {
    testPanelProvider()->inviteJoinRedirect('/widget');

    expect(testPanelProvider()->getInviteJoinRedirectUrl())->toBe('/widget');
});

test('primary utility theme is mapped to the provider palette tokens', function () {
    $providerContents = file_get_contents(__DIR__.'/../../src/WirechatServiceProvider.php');
    $cssContents = file_get_contents(__DIR__.'/../../resources/css/app.css');

    expect($providerContents)
        ->toContain('--wc-primary-50: {$primary50};')
        ->toContain('--wc-primary-500: {$primary500};')
        ->toContain('--wc-primary-950: {$primary950};')
        ->toContain('--wc-brand-primary: var(--wc-primary-500);');

    expect($cssContents)
        ->toContain('@theme inline {')
        ->toContain('--color-primary-50: var(--wc-primary-50);')
        ->toContain('--color-primary-500: var(--wc-primary-500);')
        ->toContain('--color-primary-950: var(--wc-primary-950);')
        ->toContain('--wc-tint-primary-50: color-mix(in srgb, var(--wc-primary-50) 35%, transparent);')
        ->toContain('--wc-tint-primary-500: color-mix(in srgb, var(--wc-primary-500) 35%, transparent);')
        ->toContain('--wc-tint-primary-950: color-mix(in srgb, var(--wc-primary-950) 35%, transparent);')
        ->toContain('.wc-tint-primary-bg  { background-color: var(--wc-tint-primary-300); }');

});
test('panel unread messages type defaults to dot', function () {
    User::factory()->create();

    expect(testPanelProvider()->hasUnreadIndicator())->toBeTrue()
        ->and(testPanelProvider()->getUnreadIndicatorType())->toBe(UnreadIndicatorType::Dot);
});

test('panel unread messages type can be set to count', function () {
    User::factory()->create();

    testPanelProvider()->unreadIndicator(type: UnreadIndicatorType::Count);

    expect(testPanelProvider()->hasUnreadIndicator())->toBeTrue()
        ->and(testPanelProvider()->getUnreadIndicatorType())->toBe(UnreadIndicatorType::Count);
});

test('legacy unread messages panel aliases still work', function () {
    User::factory()->create();

    testPanelProvider()->unReadMessages(type: UnReadType::Count);

    expect(testPanelProvider()->hasUnReadMessages())->toBeTrue()
        ->and(testPanelProvider()->getUnReadMessagesType())->toBe(UnReadType::Count)
        ->and(testPanelProvider()->getUnreadIndicatorType())->toBe(UnreadIndicatorType::Count);
});

test('panel message requests are disabled by default and can be enabled', function () {
    $panel = new Panel;

    expect($panel->hasMessageRequests())->toBeFalse();

    $panel->messageRequests();

    expect($panel->hasMessageRequests())->toBeTrue();
});

test('panel settings are disabled by default and can be enabled', function () {
    $panel = new Panel;

    expect($panel->hasSettings())->toBeFalse();

    $panel->settings();

    expect($panel->hasSettings())->toBeTrue();
});

test('panel default user search uses the configured user model', function () {
    User::factory()->create(['name' => 'Taylor User']);
    $admin = Admin::factory()->create([
        'name' => 'Taylor Admin',
        'email' => 'taylor.admin@example.test',
    ]);

    config(['wirechat.models.user' => Admin::class]);

    $results = (new Panel)->searchUsers('Taylor')->toArray(request());

    expect($results)
        ->toHaveCount(1)
        ->and($results[0]['id'])->toBe($admin->id)
        ->and($results[0]['wirechat_name'])->toBe('Taylor Admin')
        ->and($results[0]['wirechat_subtitle'])->toBe('taylor.admin@example.test');
});

test('panel private chat actions are disabled by default and can be enabled', function () {
    $panel = new Panel;

    expect($panel->hasClearChatAction())->toBeFalse()
        ->and($panel->hasDeleteChatAction())->toBeFalse();

    $panel
        ->clearChatAction()
        ->deleteChatAction();

    expect($panel->hasClearChatAction())->toBeTrue()
        ->and($panel->hasDeleteChatAction())->toBeTrue();
});

test('panel message actions are enabled by default and can be disabled', function () {
    $panel = new Panel;

    expect($panel->hasDeleteMessageActions())->toBeTrue()
        ->and($panel->hasMessageReplyAction())->toBeTrue();

    $panel
        ->deleteMessageActions(false)
        ->messageReplyAction(false);

    expect($panel->hasDeleteMessageActions())->toBeFalse()
        ->and($panel->hasMessageReplyAction())->toBeFalse();
});

describe('Chats Route', function () {

    test('return 404 if user canAccessWirechatPanel() returns false on chats route', function () {

        $auth = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($auth)->get(testPanelProvider()->chatsRoute())->assertStatus(404);

    });

    test('it 200 OK  if canAccessWirechatPanel() returns true on chats route', function () {

        $auth = User::factory()->create();

        $this->actingAs($auth)->get(testPanelProvider()->chatsRoute())->assertStatus(200);

    });

});

describe('Chat Route', function () {

    test('return 404 if user canAccessWirechatPanel() returns false on chats route', function () {

        $auth = User::factory()->create(['email_verified_at' => null]);

        // create conversatin using other user because they are veirified
        $otherUser = User::factory()->create();
        $conversation = $otherUser->createConversationWith($auth);

        $this->actingAs($auth)->get(testPanelProvider()->chatRoute($conversation->id))->assertStatus(404);

    });

    test('it 200 OK  if canAccessWirechatPanel() returns true on chat route', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createConversationWith(User::factory()->create());

        $this->actingAs($auth)->get(testPanelProvider()->chatRoute($conversation->id))->assertStatus(200);

    });

});
