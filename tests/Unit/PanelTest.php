<?php

use Workbench\App\Models\User;

beforeEach(function () {
    testPanelProvider()->registerRoutes(true);
    testPanelProvider()->groupInvitations(true);
    testPanelProvider()->invitePageLayout('wirechat::layouts.app');
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
