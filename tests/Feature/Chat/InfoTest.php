<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Jobs\DeleteConversationJob;
use Namu\WireChat\Livewire\Chat\Info;
use Namu\WireChat\Livewire\Chats\Chats;
use Namu\WireChat\Models\Conversation;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

test('user must be authenticated', function () {

    $conversation = Conversation::factory()->create();
    Livewire::test(Info::class, ['conversation' => $conversation])
        ->assertStatus(401);
});

test('aborts if user doest not belog to conversation', function () {

    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->create();
    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->assertStatus(403);
});

test('aborts if conversation is group ', function () {

    $auth = User::factory()->create(['id' => '34567833']);

    $conversation = $auth->createGroup(name: 'My Group', description: 'This is a good group');

    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->assertStatus(403, __('wirechat::chat.info.messages.invalid_conversation_type_error'));
});

test('authenticaed user can access info ', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->withParticipants([$auth])->create();

    Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
        ->assertStatus(200);
});

describe('presence test', function () {

    test('it shows receiver name if conversaton is private', function () {
        $auth = User::factory()->create(['id' => '345678']);
        $receiver = User::factory()->create(['name' => 'Musa']);

        $conversation = $auth->createConversationWith($receiver, 'hello');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Musa');
    });

    test('it shows receiver name if conversaton is private and Mixed Model', function () {
        $auth = User::factory()->create(['id' => '345678']);
        $receiver = Admin::factory()->create(['name' => 'Musa']);

        $conversation = $auth->createConversationWith($receiver, 'hello');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Musa');
    });

    test('it shows receiver name if conversaton is self', function () {
        $auth = User::factory()->create(['id' => '345678', 'name' => 'John']);

        $conversation = $auth->createConversationWith($auth, 'hello');

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('John');
    });

    test('it shows "Delete Chat" if is not group', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->assertSee('Delete Chat')
            ->assertMethodWired('deleteChat');
    });

});

describe('Deleting Chat', function () {

    test('it redirects to index route and Does NOT dispatch  "close-chat"  & "chat-deleted" events after deleting Private conversation when isNotWidget', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation, 'widget' => false])
            ->call('deleteChat')
            ->assertStatus(200)
            ->assertRedirect(route(WireChat::indexRouteName()))
            ->assertNotDispatched('close-chat')
            ->assertNotDispatched('chat-deleted');

    });

    test('it does not push DeleteConversationJob', function () {
        Queue::fake();

        Carbon::setTestNow(now()->subMinutes(4));

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver, 'hello');

        Carbon::setTestNow();

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteChat')
            ->assertStatus(200);

        Queue::assertNotPushed(DeleteConversationJob::class, function ($job) use ($conversation) {

            return $job->conversation->id == $conversation->id;
        });

    });

    test('Deleted chat should no longer appear in Chats component when after deleteing Chat', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver, 'hello');

        // Load Chats component
        $CHATLIST = Livewire::actingAs($auth)->test(Chats::class);

        // Assert conversation is visible
        $CHATLIST->assertViewHas('conversations', function ($conversation) {
            return count($conversation) == 1;
        });

        // Load Info component
        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation])
            ->call('deleteChat')
            ->assertStatus(200);

        // Assert conversation no longer visible
        $CHATLIST->dispatch('chat-deleted', $conversation->id)->assertViewHas('conversations', function ($conversation) {
            return count($conversation) == 0;
        });
    });

    test('when isWidget it dispatches  "close-chat" & "chat-deleted" events  and Does NOT redirects to index route after deleting Private conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation, 'widget' => true])
            ->call('deleteChat')
            ->assertStatus(200)
            ->assertNoRedirect()
            ->assertDispatched('close-chat')
            ->assertDispatched('chat-deleted');

    });

    test('it redirects to index route and Does NOT dispatch "close-chat"  & "chat-deleted" events after deleting Self conversation  when isNotWidget', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation, 'widget' => false])
            ->call('deleteChat')
            ->assertStatus(200)
            ->assertNotDispatched('close-chat')
            ->assertNotDispatched('chat-deleted')
            ->assertRedirect(route(WireChat::indexRouteName()));
    });

    test('when isWidget it dispatches "close-chat"  & "chat-deleted" events and Does NOT redirects to index route   after deleting Self conversation', function () {

        $auth = User::factory()->create();

        $conversation = $auth->createConversationWith($auth);

        Livewire::actingAs($auth)->test(Info::class, ['conversation' => $conversation, 'widget' => 'true'])
            ->call('deleteChat')
            ->assertStatus(200)
            ->assertDispatched('close-chat')
            ->assertDispatched('chat-deleted')
            ->assertNoRedirect(route(WireChat::indexRouteName()));
    });

});
