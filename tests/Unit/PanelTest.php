<?php

use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;
use Wirechat\Wirechat\Support\Enums\UnReadType;
use Workbench\App\Models\User;

test(' panel hasRoutes is true by default()', function () {
    $auth = User::factory()->create();

    expect(testPanelProvider()->hasRoutes())->toBeTrue();

});

test(' panel hasRoutes is false when registerRoutes is FALSE', function () {
    $auth = User::factory()->create();

    testPanelProvider()->registerRoutes(false);

    expect(testPanelProvider()->hasRoutes())->toBeFalse();

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
