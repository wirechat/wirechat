<?php

use Workbench\App\Models\User;

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
