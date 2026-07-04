<?php

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Wirechat\Wirechat\Enums\ColorTone;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Enums\MessageRequestStatus;
use Wirechat\Wirechat\Enums\MessageType;
use Wirechat\Wirechat\Enums\ParticipantRole;
use Wirechat\Wirechat\Events\MessageCreated;
use Wirechat\Wirechat\Events\MessageDeleted;
use Wirechat\Wirechat\Events\NotifyParticipant;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Helpers\Helper;
use Wirechat\Wirechat\Jobs\BroadcastMessage;
use Wirechat\Wirechat\Jobs\NotifyParticipants;
use Wirechat\Wirechat\Livewire\Chat\Chat as ChatBox;
use Wirechat\Wirechat\Livewire\Chats\Chats as Chatlist;
use Wirechat\Wirechat\Models\Attachment;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Models\Invite;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\MessageRequest;
use Wirechat\Wirechat\Support\Enums\EmojiPickerPosition;
use Workbench\App\Models\Admin;
use Workbench\App\Models\User;

// /Auth checks
it('checks if users is authenticated before loading chatbox', function () {
    Livewire::test(ChatBox::class, ['conversation' => 1])
        ->assertStatus(401);
});

test('authenticaed user can access chatbox ', function () {
    $auth = User::factory()->create(['id' => '345678']);

    $conversation = Conversation::factory()->withParticipants([$auth])->create();

    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
        ->assertStatus(200);
});

test('it shows peer sender names above group messages but not auth sender names', function () {
    $auth = User::factory()->create(['name' => 'Owner Sender']);
    $member = User::factory()->create(['name' => 'Group Member']);

    $conversation = $auth->createGroup('My Group');
    $conversation->addParticipant($member);

    Message::create([
        'conversation_id' => $conversation->id,
        'participant_id' => $conversation->participant($auth)?->id,
        'body' => 'hello from owner',
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'participant_id' => $conversation->participant($member)?->id,
        'body' => 'hello from member',
    ]);

    $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

    expect($html)
        ->not->toMatch('/dusk="message-sender-name"[^>]*>\s*Owner Sender\s*</')
        ->toMatch('/dusk="message-sender-name"[^>]*class="(?![^"]*\bhidden\b)[^"]*"[^>]*>\s*Group Member\s*</');
});

test('it applies ui classes and styles to the chat shell only', function () {
    $auth = User::factory()->create(['name' => 'Test']);
    $conversation = $auth->createConversationWith(User::factory()->create(), 'hello');

    $response = Livewire::actingAs($auth)->test(ChatBox::class, [
        'conversation' => $conversation->id,
        'class' => 'chat-shell-test',
        'styles' => [
            'min-height' => '24rem',
        ],
    ]);

    $html = $response->html();

    preg_match_all('/class="[^"]*chat-shell-test[^"]*"/', $html, $classMatches);
    preg_match_all('/style="[^"]*min-height: 24rem;[^"]*"/', $html, $styleMatches);

    expect($classMatches[0])->toHaveCount(1)
        ->and($styleMatches[0])->toHaveCount(1);
});

test('it renders the chat header with a single divider and aligned padding', function () {
    $auth = User::factory()->create(['name' => 'Test']);
    $conversation = $auth->createConversationWith(User::factory()->create(), 'hello');

    $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

    preg_match_all('/border-b border-zinc-200\/80 dark:border-zinc-700\/60/', $html, $dividerMatches);

    expect($html)
        ->toContain('class="w-full sticky inset-x-0 top-0 z-10 flex flex-col bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-secondary)]"')
        ->toContain('px-4 py-3')
        ->not->toContain('dark:border-[var(--wc-dark-secondary)] border-b')
        ->and($dividerMatches[0])->toHaveCount(1);
});

test('it renders stable message anchors for scroll restoration', function () {
    $auth = User::factory()->create(['name' => 'Test']);
    $conversation = $auth->createConversationWith(User::factory()->create(), 'hello');
    $message = $conversation->messages()->firstOrFail();

    $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

    expect($html)
        ->toContain('x-ref="main-chat-body"')
        ->toContain('ResizeObserver')
        ->toContain('observePrependedMessageResizes')
        ->toContain('data-message-id="'.$message->id.'"')
        ->toContain('id="message-'.$message->id.'"')
        ->toContain('wire:key="msg-'.$message->id.'"')
        ->not->toContain('x-on:load.capture="$data.onAnyMediaLoad()"')
        ->not->toContain('x-on:error.capture="$data.onAnyMediaLoad()"');
});

test('it loads older messages from the top using the pro-style older window', function () {
    $auth = User::factory()->create(['name' => 'Test']);
    $receiver = User::factory()->create(['name' => 'John']);
    $conversation = $auth->createConversationWith($receiver, 'Message 1');

    foreach (range(2, 15) as $index) {
        $auth->sendMessageTo($conversation, "Message {$index}");
    }

    $component = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

    $initialMessages = collect($component->instance()->loadedMessages)->flatten(1);

    expect($initialMessages)->toHaveCount(10)
        ->and($component->instance()->canLoadOlder)->toBeTrue();

    $component->call('loadOlder');

    $loadedMessages = collect($component->instance()->loadedMessages)->flatten(1);

    expect($loadedMessages)->toHaveCount(15)
        ->and($component->instance()->canLoadOlder)->toBeFalse();
});

test('it dispatches older-loaded when a stale older cursor returns no messages', function () {
    $auth = User::factory()->create(['name' => 'Test']);
    $receiver = User::factory()->create(['name' => 'John']);
    $conversation = $auth->createConversationWith($receiver, 'Message 1');

    foreach (range(2, 15) as $index) {
        $auth->sendMessageTo($conversation, "Message {$index}");
    }

    $oldestMessage = $conversation->messages()
        ->orderBy('created_at', 'asc')
        ->orderBy('id', 'asc')
        ->firstOrFail();

    $component = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

    $component
        ->set('canLoadOlder', true)
        ->set('olderCreatedAt', $oldestMessage->created_at->toDateTimeString())
        ->set('olderId', $oldestMessage->id)
        ->call('loadOlder')
        ->assertDispatched('older-loaded');

    expect($component->instance()->canLoadOlder)->toBeFalse()
        ->and($component->instance()->canLoadMore)->toBeFalse();
});

test('it rebuilds the loaded window around the requested message when jumping', function () {
    $auth = User::factory()->create(['name' => 'Test']);
    $receiver = User::factory()->create(['name' => 'John']);
    $conversation = $auth->createConversationWith($receiver, 'Message 1');

    foreach (range(2, 30) as $index) {
        $auth->sendMessageTo($conversation, "Message {$index}");
    }

    $orderedMessages = $conversation->messages()
        ->orderBy('created_at', 'asc')
        ->orderBy('id', 'asc')
        ->get()
        ->values();

    $targetMessage = $orderedMessages->get(11);
    $expectedWindowIds = $orderedMessages->slice(1, 21)->pluck('id')->all();

    $component = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

    expect(collect($component->instance()->loadedMessages)->flatten(1)->pluck('id')->all())
        ->not->toContain($targetMessage->id);

    $component
        ->call('jumpToMessage', $targetMessage->id)
        ->assertDispatched('scroll-to-message');

    $loadedIds = collect($component->instance()->loadedMessages)->flatten(1)->pluck('id')->all();

    expect($component->instance()->anchorId)->toBe($targetMessage->id)
        ->and($loadedIds)->toBe($expectedWindowIds)
        ->and($component->instance()->canLoadOlder)->toBeTrue()
        ->and($component->instance()->canLoadNewer)->toBeTrue();
});

test('it loads newer messages after jumping to an older window', function () {
    $auth = User::factory()->create(['name' => 'Test']);
    $receiver = User::factory()->create(['name' => 'John']);
    $conversation = $auth->createConversationWith($receiver, 'Message 1');

    foreach (range(2, 30) as $index) {
        $auth->sendMessageTo($conversation, "Message {$index}");
    }

    $orderedMessages = $conversation->messages()
        ->orderBy('created_at', 'asc')
        ->orderBy('id', 'asc')
        ->get()
        ->values();

    $targetMessage = $orderedMessages->get(11);
    $expectedIdsAfterLoadNewer = $orderedMessages->slice(1)->pluck('id')->all();

    $component = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

    $component->call('jumpToMessage', $targetMessage->id);

    expect($component->instance()->canLoadNewer)->toBeTrue();

    $component->call('loadNewer');

    $loadedIds = collect($component->instance()->loadedMessages)->flatten(1)->pluck('id')->all();

    expect($loadedIds)->toBe($expectedIdsAfterLoadNewer)
        ->and($component->instance()->canLoadNewer)->toBeFalse()
        ->and($component->instance()->canLoadOlder)->toBeTrue();
});

test('returns 404 if conversation is not found', function () {
    $auth = User::factory()->create();

    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => 1])
        ->assertStatus(404);
});

test('returns 403(Forbidden) if user doesnt not bleong to conversation', function () {
    $auth = User::factory()->create();

    $conversation = Conversation::factory()->create();

    Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
        ->assertStatus(403);
});

describe('Message requests', function () {
    test('pending recipient can review the thread and sees accept and reject actions', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->sendMessageRequestTo($receiver);
        $participant = $conversation->participant($auth);

        Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'body' => 'Hello from a request',
        ]);

        Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Hello from a request')
            ->assertSee(__('wirechat::chat.message_request.actions.accept.label'))
            ->assertSee(__('wirechat::chat.message_request.actions.dismiss.label'))
            ->assertDontSee(__('wirechat::chat.inputs.message.placeholder'));
    });

    test('sender sees the outgoing pending notice while keeping the composer', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->sendMessageRequestTo($receiver);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee(__('wirechat::chat.message_request.labels.outgoing_notice'))
            ->assertSee(__('wirechat::chat.inputs.message.placeholder'))
            ->assertDontSee(__('wirechat::chat.message_request.actions.accept.label'))
            ->assertDontSee(__('wirechat::chat.message_request.actions.dismiss.label'));
    });

    test('pending request messages broadcast in real-time but do not trigger notifications', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->sendMessageRequestTo($receiver);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'Hello from a pending request')
            ->call('sendMessage');

        $message = Message::query()->latest('id')->first();

        expect($message)->not->toBeNull()
            ->and($message?->body)->toBe('Hello from a pending request');

        // Message is broadcast so the recipient's chat view updates in real-time
        Event::assertDispatched(MessageCreated::class);
        // Recipient is not a participant yet, so the job is skipped;
        // NotifyParticipant is broadcast directly to the request recipient instead
        Queue::assertNotPushed(NotifyParticipants::class);
        Event::assertDispatched(NotifyParticipant::class);
    });

    test('deleting a message in a pending request notifies the recipient directly', function () {
        Event::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->sendMessageRequestTo($receiver);
        $message = $auth->sendMessageTo($conversation, 'Hello');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt($message->id));

        // MessageDeleted broadcast fires on the conversation channel
        Event::assertDispatched(MessageDeleted::class);
        // NotifyParticipant is also broadcast directly so the recipient's
        // Requests list refreshes (recipient is not a participant yet)
        Event::assertDispatched(NotifyParticipant::class, function ($event) use ($receiver) {
            return (string) $event->participantId === (string) $receiver->getKey();
        });
    });

    test('pending recipient can accept a message request', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->sendMessageRequestTo($receiver);
        $request = MessageRequest::query()->pending()->where('conversation_id', $conversation->id)->firstOrFail();

        Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('acceptMessageRequest');

        expect($conversation->fresh()->participant($receiver))->not->toBeNull()
            ->and(MessageRequest::query()->whereKey($request->id)->exists())->toBeFalse();
    });

    test('rejecting a message request dismisses it and removes the pending conversation', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create();

        $conversation = $auth->sendMessageRequestTo($receiver);
        $request = MessageRequest::query()->pending()->where('conversation_id', $conversation->id)->firstOrFail();

        Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('dismissMessageRequest')
            ->assertRedirect(testPanelProvider()->chatsRoute());

        expect(Conversation::find($conversation->id))->toBeNull()
            ->and($request->fresh()->status)->toBe(MessageRequestStatus::DISMISSED)
            ->and($request->fresh()->conversation_id)->toBeNull();
    });
});

describe('Presense', function () {

    describe('header', function () {

        test('it_shows_suffix_you_in_user_name_if_user_has_self_conversation', function () {

            $auth = User::factory()->create(['name' => 'Test']);

            // Create-conversation with user1
            $conversation = $auth->createConversationWith($auth, 'hello');

            // dd($conversation);

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

            // Assert-both-conversations visible before typing
            $request
                ->assertSee('Test')
                ->assertSee('(You)');
        });

        test('it shows "show_chat_info" and doesnt show "show_group_info"  if is private conversation', function () {

            $auth = User::factory()->create(['name' => 'Test']);

            // create conversation with user1
            $conversation = $auth->createConversationWith(User::factory()->create(), 'hello');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

            // Assert both conversations visible before typing
            $request->assertSeeHtml('dusk="show_chat_info"');
            $request->assertDontSeeHtml('dusk="show_group_info"');
        });

        test('it shows "show_chat_info" and doesnt show "show_group_info"  if is self conversation', function () {

            $auth = User::factory()->create(['name' => 'Test']);

            // create conversation with user1
            $conversation = $auth->createConversationWith($auth, 'hello');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

            // Assert both conversations visible before typing
            $request->assertSeeHtml('dusk="show_chat_info"');
            $request->assertDontSeeHtml('dusk="show_group_info"');
        });

        test('it  shows "show_group_info" and doesnt show "show_chat_info"  if is group', function () {

            $auth = User::factory()->create(['name' => 'Test']);

            // create conversation with user1
            $conversation = $auth->createGroup('My Group');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

            // Assert both conversations visible before typing
            $request->assertDontSeeHtml('dusk="show_chat_info"');
            $request->assertSeeHtml('dusk="show_group_info"');
        });
    });

    test('it_can_show_correctly_formatted_time', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // Create a conversation with participants
        $conversation = $auth->createConversationWith($receiver);
        $participant = $conversation->participant($auth);

        // Set specific times for testing purposes
        Carbon::setTestNow(now()->today());
        // Create messages with different timestamps
        $todayMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'body' => 'Message from today',
        ]);

        Carbon::setTestNow(now()->subDay());
        $yesterdayMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'body' => 'Message from yesterday',
        ]);

        Carbon::setTestNow(now()->subDay(2));
        $thisWeekMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'body' => 'Message from this week',
        ]);

        Carbon::setTestNow(now()->subWeeks(2));
        $olderMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'body' => 'Older message',
        ]);

        // Expected outputs based on the message created_at timestamps
        $todayExpected = Helper::formatChatDate($todayMessage->created_at);
        $yesterdayExpected = Helper::formatChatDate($yesterdayMessage->created_at);
        $thisWeekExpected = Helper::formatChatDate($thisWeekMessage->created_at);
        $olderExpected = Helper::formatChatDate($olderMessage->created_at);

        // Run the test
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee($todayExpected)        // Assert "1:00 PM"
            ->assertSee($yesterdayExpected)    // Assert "Yesterday 3:00 PM"
            ->assertSee($thisWeekExpected)     // Assert "Mon 9:00 AM" (or whatever day it is)
            ->assertSee($olderExpected)        // Assert "08/31/24"
            ->assertSeeHtml('dusk="message-date-separator"')
            ->assertSeeHtml('text-[11px]')
            ->assertSeeHtml('rounded-full')
            ->assertSeeHtml('h-6 w-24')
            ->assertDontSeeHtml('sticky top-0 uppercase')
            ->assertSee('Message from today')
            ->assertSee('Message from yesterday')
            ->assertSee('Message from this week')
            ->assertSee('Older message');
    });

    test('it renders a group invite preview card from a wirechat invite link in the message body', function () {
        $sender = User::factory()->create(['name' => 'Sender']);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        $groupConversation = $sender->createGroup('Yodah', 'A place to share ideas');
        $invite = $groupConversation->group->inviteLinks()->create([
            'panel_id' => testPanelProvider()->getId(),
            'created_by_id' => $sender->getKey(),
            'created_by_type' => $sender->getMorphClass(),
            'token' => Invite::generateToken(),
            'is_primary' => true,
        ]);

        $conversation = $sender->sendMessageTo(
            $receiver,
            'Follow this link to join my group: '.$invite->url(testPanelProvider())
        )->conversation;

        Livewire::actingAs($receiver)->test(ChatBox::class, [
            'conversation' => $conversation->id,
            'panel' => testPanelProvider()->getId(),
        ])
            ->assertSee('Yodah')
            ->assertSee(__('wirechat::chat.group.invite_message.labels.type'))
            ->assertDontSee('A place to share ideas')
            ->assertSee('Follow this link to join my group:')
            ->assertSee(__('wirechat::chat.group.invite_message.actions.view_group.label'))
            ->assertSee($invite->url(testPanelProvider()));
    });

    test('it renders outgoing group invite surfaces as white for solid color tone', function () {
        testPanelProvider()->colorTone(ColorTone::Solid);

        $sender = User::factory()->create(['name' => 'Sender']);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        $groupConversation = $sender->createGroup('Yodah', 'A place to share ideas');
        $invite = $groupConversation->group->inviteLinks()->create([
            'panel_id' => testPanelProvider()->getId(),
            'created_by_id' => $sender->getKey(),
            'created_by_type' => $sender->getMorphClass(),
            'token' => Invite::generateToken(),
            'is_primary' => true,
        ]);

        $conversation = $sender->sendMessageTo(
            $receiver,
            'Follow this link to join my group: '.$invite->url(testPanelProvider())
        )->conversation;

        $html = Livewire::actingAs($sender)->test(ChatBox::class, [
            'conversation' => $conversation->id,
            'panel' => testPanelProvider()->getId(),
        ])->html();

        expect($html)
            ->toContain(__('wirechat::chat.group.invite_message.actions.view_group.label'))
            ->toMatch('/dusk="group-invite-preview"[^>]*class="[^"]*bg-white\/10 text-white/')
            ->toMatch('/<button[^>]*dusk="group-invite-action"[^>]*data-invite-link="true"[^>]*class="[^"]*border-white\/20 text-white\/90/')
            ->not->toContain('href="'.$invite->url(testPanelProvider()).'"');
    });

    test('it keeps group invite previews below visible group sender names', function () {
        $owner = User::factory()->create(['name' => 'Owner']);
        $member = User::factory()->create(['name' => 'Group Member']);

        $conversation = $owner->createGroup('Host Group');
        $conversation->addParticipant($member);

        $targetConversation = $member->createGroup('Target Group');
        $invite = $targetConversation->group->inviteLinks()->create([
            'panel_id' => testPanelProvider()->getId(),
            'created_by_id' => $member->getKey(),
            'created_by_type' => $member->getMorphClass(),
            'token' => Invite::generateToken(),
            'is_primary' => true,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $conversation->participant($member)?->id,
            'body' => 'Join here: '.$invite->url(testPanelProvider()),
        ]);

        $html = Livewire::actingAs($owner)->test(ChatBox::class, [
            'conversation' => $conversation->id,
            'panel' => testPanelProvider()->getId(),
        ])->html();

        expect($html)
            ->toMatch('/dusk="message-sender-name"[^>]*class="(?![^"]*\bhidden\b)[^"]*"[^>]*>\s*Group Member\s*</')
            ->toContain('dusk="group-invite-preview"')
            ->toContain('Target Group')
            ->toContain('max-w-full')
            ->not->toMatch('/dusk="group-invite-preview"[^>]*class="[^"]*(?:^|\s)-mt-1\.5(?:\s|")/');
    });

    test('it renders an inline invite link as an in-chat action while external links stay anchors', function () {
        testPanelProvider()->parseMessageUrls(true);

        $sender = User::factory()->create(['name' => 'Sender']);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        $groupConversation = $sender->createGroup('Yodah');
        $invite = $groupConversation->group->inviteLinks()->create([
            'panel_id' => testPanelProvider()->getId(),
            'created_by_id' => $sender->getKey(),
            'created_by_type' => $sender->getMorphClass(),
            'token' => Invite::generateToken(),
            'is_primary' => true,
        ]);

        $inviteUrl = preg_replace('~^https?://[^/]+~', 'http://localhost:8001', $invite->url(testPanelProvider()));

        $conversation = $sender->sendMessageTo(
            $receiver,
            'Join here: '.$inviteUrl.' and also https://example.com'
        )->conversation;

        $rendered = Livewire::actingAs($receiver)->test(ChatBox::class, [
            'conversation' => $conversation->id,
            'panel' => testPanelProvider()->getId(),
        ])->html();

        preg_match_all('~<button[^>]*dusk="message-invite-action"[^>]*>[\s\S]*?</button>~', $rendered, $buttonMatches);
        $inviteActions = collect($buttonMatches[0])
            ->filter(fn (string $tag) => str_contains($tag, $inviteUrl))
            ->values();

        preg_match_all('~<a[^>]*dusk="message-link"[^>]*>~', $rendered, $matches);
        $linkTags = $matches[0];

        expect($inviteActions)->toHaveCount(1)
            ->and($linkTags)->toHaveCount(1)
            ->and($rendered)->not->toContain('href="'.$inviteUrl.'"');

        $inviteAction = $inviteActions->first();
        $externalAnchor = collect($linkTags)
            ->first(fn (string $tag) => str_contains($tag, 'https://example.com'));

        expect($inviteAction)->toContain('type="button"')
            ->and($inviteAction)->toContain('data-invite-link="true"')
            ->and($inviteAction)->toContain('wire:click="handleOpenChat(')
            ->and($inviteAction)->not->toContain('href=')
            ->and($inviteAction)->not->toContain('target="_blank"')
            ->and($inviteAction)->not->toContain('rel=');

        // The wire:click param must be encrypted, while the visible text still
        // shows the copied/shareable invite URL.
        preg_match("~wire:click=\"handleOpenChat\\('([^']+)'\\)\"~", $inviteAction, $clickMatch);

        expect($clickMatch[1] ?? '')->not->toBe('')
            ->and($clickMatch[1])->not->toBe($inviteUrl)
            ->and($clickMatch[1])->not->toBe($invite->token);

        // And the encrypted payload must round-trip back to the canonical URL.
        expect(decrypt($clickMatch[1]))->toBe($inviteUrl);

        expect($externalAnchor)->toContain('target="_blank"')
            ->and($externalAnchor)->toContain('rel="noopener noreferrer"')
            ->and($externalAnchor)->not->toContain('data-invite-link="true"')
            ->and($externalAnchor)->not->toContain('handleOpenChat');
    });

    test('it renders missing invite route urls as in-chat actions instead of external links', function () {
        testPanelProvider()->parseMessageUrls(true);

        $sender = User::factory()->create(['name' => 'Sender']);
        $receiver = User::factory()->create(['name' => 'Receiver']);
        $missingInviteUrl = preg_replace(
            '~^https?://[^/]+~',
            'http://localhost:8001',
            testPanelProvider()->inviteRoute(Invite::generateToken())
        );
        $malformedInviteUrl = preg_replace(
            '~^https?://[^/]+~',
            'http://localhost:8001',
            testPanelProvider()->inviteRoute('not-a-wirechat-link')
        );

        $conversation = $sender->sendMessageTo(
            $receiver,
            'Old invite: '.$missingInviteUrl.' malformed '.$malformedInviteUrl.' and external https://example.com'
        )->conversation;

        $rendered = Livewire::actingAs($receiver)->test(ChatBox::class, [
            'conversation' => $conversation->id,
            'panel' => testPanelProvider()->getId(),
        ])->html();

        preg_match_all('~<button[^>]*dusk="message-invite-action"[^>]*>[\s\S]*?</button>~', $rendered, $buttonMatches);
        preg_match_all('~<a[^>]*dusk="message-link"[^>]*>~', $rendered, $anchorMatches);

        $inviteActions = collect($buttonMatches[0]);
        $inviteAction = $inviteActions
            ->first(fn (string $tag) => str_contains($tag, $missingInviteUrl));
        $malformedInviteAction = $inviteActions
            ->first(fn (string $tag) => str_contains($tag, $malformedInviteUrl));
        $externalAnchor = collect($anchorMatches[0])
            ->first(fn (string $tag) => str_contains($tag, 'https://example.com'));

        expect($rendered)
            ->not->toContain(__('wirechat::chat.group.invite_message.actions.view_group.label'))
            ->not->toContain('href="'.$missingInviteUrl.'"')
            ->not->toContain('href="'.$malformedInviteUrl.'"')
            ->and($inviteAction)->toContain('data-invite-link="true"')
            ->and($inviteAction)->toContain('wire:click="handleOpenChat(')
            ->and($inviteAction)->not->toContain('target="_blank"')
            ->and($malformedInviteAction)->toContain('data-invite-link="true"')
            ->and($malformedInviteAction)->toContain('wire:click="handleOpenChat(')
            ->and($malformedInviteAction)->not->toContain('target="_blank"')
            ->and($externalAnchor)->toContain('href="https://example.com"')
            ->and($externalAnchor)->toContain('target="_blank"');

        preg_match("~wire:click=\"handleOpenChat\\('([^']+)'\\)\"~", $inviteAction, $clickMatch);

        expect(decrypt($clickMatch[1] ?? ''))->toBe($missingInviteUrl);
    });

    test('it does not render a group invite preview card for foreign or edited text without a valid wirechat invite link', function () {
        $sender = User::factory()->create(['name' => 'Sender']);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        $conversation = $sender->sendMessageTo($receiver, 'Edited invite text https://example.com/chats/invites/not-a-wirechat-link')->conversation;

        Livewire::actingAs($receiver)->test(ChatBox::class, [
            'conversation' => $conversation->id,
            'panel' => testPanelProvider()->getId(),
        ])
            ->assertSee('Edited invite text')
            ->assertDontSee(__('wirechat::chat.group.invite_message.labels.type'))
            ->assertDontSee(__('wirechat::chat.group.invite_message.actions.view_group.label'));
    });

    test('it can render grouped messages when the app uses immutable dates', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);
        $participant = $conversation->participant($auth);

        try {
            Date::use(CarbonImmutable::class);
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-09 12:00:00'));

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'participant_id' => $participant->id,
                'body' => 'Immutable message',
            ]);

            expect($message->fresh()->created_at)->toBeInstanceOf(CarbonImmutable::class);

            $expectedGroup = Helper::formatChatDate($message->fresh()->created_at);

            Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
                ->assertSee($expectedGroup)
                ->assertSee('Immutable message');
        } finally {
            CarbonImmutable::setTestNow();
            Date::useDefault();
        }
    });

    test('it_doesnt_show_upload_trigger_if_attachments_not_enabled', function () {

        Config::set('wirechat.allow_media_attachments', false);
        Config::set('wirechat.allow_file_attachments', false);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertDontSeeHtml('dusk="upload-trigger-button"');
    });

    test('it_shows_upload_trigger_if_any_one_of_attachments_is_enabled', function () {

        testPanelProvider()->mediaAttachments();

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="upload-trigger-button"');
    });

    test('it_shows_file_upload_input_if_enabled', function () {

        testPanelProvider()->fileAttachments();

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="file-upload-input"');
    });

    test('it_doesnt_show_file_upload_input_if_not_enabled', function () {

        testPanelProvider()->fileAttachments(false);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertDontSeeHtml('dusk="file-upload-input"');
    });

    test('it_shows_media_upload_input_if_enabled', function () {

        testPanelProvider()->mediaAttachments();

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="media-upload-input"');
    });

    test('it_doesnt_show_media_upload_input_if_not_enabled', function () {

        testPanelProvider()->mediaAttachments(false);

        $auth = User::factory()->create(['name' => 'Test']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertDontSeeHtml('dusk="media-upload-input"');
    });

});

describe('mount()', function () {

    test('it renders component when conversation is passed as Id ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request->assertOk();
    });

    test('it renders component when conversation is passed as Conversation Model ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation]);

        $request->assertOk();
    });

    test('it aborts 422 if conversation is passsed as invalid input', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => []]);

        $request->assertStatus(422);
    });

    test('it aborts 422 if conversation is passsed as null', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => null]);

        $request->assertStatus(422, 'A conversation is required');
    });

    test('updates the auth particiapnt  last_active_at field when component is opened', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->addSeconds(3));
        $conversation = $auth->createConversationWith($receiver);

        Carbon::setTestNow(now()->addSeconds(4));
        // $this->actingAs($auth);

        $participant = $conversation->participant($auth);
        expect($participant->last_active_at)->toBe(null);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $participant->refresh();

        expect($participant->last_active_at)->not->toBe(null);
    });

    test('When NOT Widget it does not dispatches "refresh" event after succesfully loading chat', function () {
        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');
        $auth->sendMessageTo($conversation, 'hi');

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);
        $user->sendMessageTo($conversation, 'hi');

        // login as user not auth (Owner)
        $request = Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => false]);

        $request
            ->assertStatus(200)
            ->assertNotDispatched('refresh');
    });

    test('When Widget it dispatches "refresh" event after succesfully loading chat', function () {
        $auth = User::factory()->create();
        $user = User::factory()->create();

        $conversation = $auth->createConversationWith($user, 'hi');
        $user->sendMessageTo($auth, 'new unread');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

        $request
            ->assertStatus(200)
            ->assertDispatched('refresh');
    });

    // test('When Widget it dispatches "refresh" event after succesfully loading chat', function () {
    test('because event is fired in blade x-init so it\'s not testable so we just check it\'s presence ', function () {
        $auth = User::factory()->create();
        $user = User::factory()->create();

        // create group
        $conversation = $auth->createConversationWith($auth, 'hi');
        // login as user not auth (Owner)
        Carbon::setTestNow(now()->subSeconds(60));

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

        Carbon::setTestNow();

        $request
            ->assertOK()
            ->assertSeeHtml('$wire.dispatch(\'chat-opened\',{conversation:conversationId})');
    });
});

describe('Validation', function () {

    test('message body is required', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', null)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertHasErrors('body', 'required');
    });

    test('file attachment count must not exceed value specified in config && it dispatces wirechat-toast error', function () {

        // set config value;
        testPanelProvider()->maxUploads(13);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Add 12 files
        $files = [];
        for ($i = 0; $i < 15; $i++) {
            // code...
            $files[] = UploadedFile::fake()->create('document.pdf');
        }
        $this->withoutExceptionHandling();
        //  dd($files);
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('files', $files)
            ->call('sendMessage')
            ->assertHasErrors('files');

        $request->assertDispatched('wirechat-toast');
        //     dd($request->errors());

    });

    test('file  size must not exceed value specified in config && it dispatces wirechat-toast error', function () {

        // set config value

        $values = ['pdf'];
        testPanelProvider()->mediaMaxUploadSize(125)->fileMaxUploadSize(125)->fileMimes($values);
        //
        Config::set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:200']);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Add 12 files
        $files[] = UploadedFile::fake()->create('document.pdf', 140);

        //  dd($files);
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('files', $files)
            ->call('sendMessage')
            ->assertHasErrors('files.0');

        //    $request->assertDispatched('wirechat-toast');
        //  dd($request->errors());

    });
    test('media size(KB) must not exceed value specified in config && it dispatces wirechat-toast error', function () {

        // set config value

        testPanelProvider()->mediaMaxUploadSize(125)->fileMaxUploadSize(125);

        //
        Config::set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:150']);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Add 12 files
        $files[] = UploadedFile::fake()->create('document.png', 150);

        // dd($files);
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $files)
            ->call('sendMessage')
            ->assertHasErrors('media.0');

        // $request->assertDispatched('wirechat-toast');
        //  dd($request->errors());

    });

    test('media  Mimes must be the ones  specified in config && it dispatces wirechat-toast error', function () {

        // set config value

        $values = ['png'];
        testPanelProvider()->mediaMimes($values);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        // Add PDF
        $files[] = UploadedFile::fake()->create('document.jpg', 120);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $files)
            ->call('sendMessage')
            ->assertHasErrors('media.0');
        //  ->assertHasErrors(['media.0'=>__('wirechat::validation.mimes', ['attribute' => __('wirechat::chat.inputs.media.label'),'values'=>'png'])]);

    });
});

describe('Box presence test: ', function () {

    test('it shows receiver name when conversation is loaded in chatbox', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();
        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('John');
    });

    test('it still shows receiver name  when Conversation has Mixed Model Participants', function () {
        $auth = User::factory()->create();
        $receiver = Admin::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();
        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('John');
    });

    test('it shows group name if conversation is group', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('My Group');
    });

    test('It shows Clear Chat button and method  is wired if conversation is Private', function () {
        testPanelProvider()->clearChatAction();

        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($participant);
        //
        Livewire::actingAs($participant)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodWired('clearConversation')
            ->assertSeeText('Clear Chat');
    });

    test('It shows Clear Chat button and method  is wired if conversation is Self', function () {
        testPanelProvider()->clearChatAction();

        $auth = User::factory()->create();

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth);
        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodWired('clearConversation')
            ->assertSeeText('Clear Chat');
    });

    test('it shows Exit Group button and method  is wired if conversation is Group and auth is not Owner', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($participant)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodWired('exitConversation')
            ->assertSeeText('Exit Group');
    });

    test('It doesnt show Exit Group button and wire method if auth is Owner', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createConversationWith($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodNotWired('exitConversation')
            ->assertDontSeeText('Exit Group');
    });

    it('Doesn\'nt show Clear Chat History button and method  is wired if conversation is group', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodNotWired('clearConversation')
            ->assertDontSeeText('Clear Chat History');
    });

    it('Doesn\'nt show Delete chat button and method  is wired if conversation is group', function () {
        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodNotWired('deleteConversation')
            ->assertDontSeeText('Delete Group');
    });

    test('it shows "Delete Chat" button label if Conversation  is Private', function () {
        testPanelProvider()->deleteChatAction();

        $auth = User::factory()->create();

        $participant = User::factory()->create(['name' => 'John']);

        // add participant
        $conversation = $auth->createConversationWith($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        //
        Livewire::actingAs($participant)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertMethodWired('deleteConversation')
            ->assertSeeText('Delete Chat');
    });

    test('it loads messages if they Exists in the conversation', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $auth->sendMessageTo($receiver, message: 'How are you');
        $receiver->sendMessageTo($auth, message: 'i am good thanks');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('How are you')
            ->assertSee('i am good thanks');
    });

    test('it shows sendable names if conversation is group ', function () {

        $auth = User::factory()->create();
        $conversation = $auth->createGroup('My Group');

        // add participant

        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'Micheal']);
        User::factory()->withMessage($conversation, 'How can i repay you ')->create(['name' => 'Levo']);
        User::factory()->withMessage($conversation, 'Wonderful')->create(['name' => 'Luis']);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Micheal')
            ->assertSee('Levo')
            ->assertSee('Luis');
    });

    test('it doesnt show auth sender name if conversation is group', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // send message
        $auth->sendMessageTo($conversation, 'Message from owner');

        // add participant

        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'Micheal']);
        User::factory()->withMessage($conversation, 'How can i repay you ')->create(['name' => 'Levo']);
        User::factory()->withMessage($conversation, 'Wonderful')->create(['name' => 'Luis']);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Message from owner')
            ->assertDontSeeText('Namu');
    });

    test('it shows dusk="disappearing_messages_icon" if disappearingTurnedOn for conversation', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // turn on disappearing
        $conversation->turnOnDisappearing(3600);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSeeHtml('dusk="disappearing_messages_icon"');
    });

    test('it doesnt shows dusk="disappearing_messages_icon" if disappearingTurnedOFF for conversation', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // turn on disappearing
        $conversation->turnOffDisappearing();

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="disappearing_messages_icon"');
    });

    describe('IsWidget:', function () {

        test('it renders $dispatch("close-chat") BUT not redirect to chats index', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            // turn on disappearing
            $conversation->turnOffDisappearing();

            $indexRoute = testPanelProvider()->chatsRoute();

            // dd($conversation);
            Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true])
                ->assertDontSeeHtml('href="'.$indexRoute.'"')
                ->assertSeeHtml('dusk="return_to_home_button_dispatch"')
                ->assertDontSeeHtml('dusk="return_to_home_button_link"');
            //                ->assertMethodWired('$dispatch(\'close-chat\')');

        });

        test('it doesnt render $dispatch("close-chat") BUT Renders redirect to chats index', function () {

            $auth = User::factory()->create(['name' => 'Namu']);
            $conversation = $auth->createGroup('My Group');

            // turn on disappearing
            $conversation->turnOffDisappearing();
            $indexRoute = testPanelProvider()->chatsRoute();

            // dd($conversation);
            Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => false])
                ->assertSeeHtml('href="'.$indexRoute.'"')
                ->assertDontSeeHtml('dusk="return_to_home_button_dispatch"')
                ->assertSeeHtml('dusk="return_to_home_button_link"')
                ->assertDontSeeHtml('@click="$dispatch(\'close-chat\')"');
        });
    });

    // test('it shows message time', function () {
    //     $auth = User::factory()->create();

    //     $receiver = User::factory()->create(['name' => 'John']);
    //     $conversation = Conversation::factory()
    //                     ->withParticipants([$auth,$receiver])
    //         ->create();

    //     //send messages
    //     $auth->sendMessageTo($receiver, message: 'How are you');

    //      Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sendable_type' => get_class($auth), // Polymorphic sender type
    //         'sendable_id' =>$auth->id, // Polymorphic sender ID
    //         'body' => 'How are you',
    //         'created_at'=>now()->subDay()
    //     ]);

    //     Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
    //         ->assertSee('Yesterday');
    // })->skip();

});

describe('Heart', function () {

    test('it doesnt show heart if not enabled in chat', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="heart-button"');
    });

    test('it  shows heart if not enabled in chat', function () {

        testPanelProvider()->heart();
        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSeeHtml('dusk="heart-button"');
    });

});

describe('Chat Actions', function () {

    test('delete-chat-action and clear-chat-action are hidden by default for private chats', function () {
        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createConversationWith(User::factory()->create());

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="delete-chat-action"')
            ->assertDontSeeHtml('dusk="clear-chat-action"');
    });

    // Delete Chat
    test('it doesnt show delete-chat-action if not enabled in chat', function () {

        testPanelProvider()->deleteChatAction(fn () => false);

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createConversationWith(User::factory()->create());

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="delete-chat-action"');
    });

    test('delete conversation aborts when delete chat action is disabled', function () {
        testPanelProvider()->deleteChatAction(false);

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createConversationWith(User::factory()->create());

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteConversation')
            ->assertStatus(403);
    });

    test('it  shows delete-chat-action if  enabled in chat', function () {
        testPanelProvider()->deleteChatAction(fn () => true);

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createConversationWith(User::factory()->create());

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSeeHtml('dusk="delete-chat-action"');
    });

    // Clear Chat
    test('it doesnt show clear-chat-action if not enabled in chat', function () {
        testPanelProvider()->clearChatAction(false);

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createConversationWith(User::factory()->create());

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="clear-chat-action"');
    });

    test('clear conversation aborts when clear chat action is disabled', function () {
        testPanelProvider()->clearChatAction(false);

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createConversationWith(User::factory()->create());

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('clearConversation')
            ->assertStatus(403);
    });

    test('it  shows clear-chat-action if  enabled in chat', function () {
        testPanelProvider()->clearChatAction(true);

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createConversationWith(User::factory()->create());

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSeeHtml('dusk="clear-chat-action"');
    });

    test('private clear and delete actions are not shown for group chats', function () {
        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="delete-chat-action"')
            ->assertDontSeeHtml('dusk="clear-chat-action"');
    });

    test('private clear and delete actions abort for group conversations', function () {
        testPanelProvider()
            ->deleteChatAction()
            ->clearChatAction();

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteConversation')
            ->assertStatus(403);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('clearConversation')
            ->assertStatus(403);
    });

    // Delete Chat
    test('it doesnt show create-chat-action if not enabled in chat', function () {

        testPanelProvider()->deleteChatAction(false);

        $auth = User::factory()->create(['name' => 'Namu']);

        $conversation = $auth->createConversationWith(User::factory()->create());

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="create-chat-action"');
    });

    test('it  shows create-chat-action if not enabled in chat', function () {
        testPanelProvider()->createChatAction(fn () => true);

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createConversationWith(User::factory()->create());

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="create-chat-action"');
    });

});

describe('Emoji', function () {
    test('it doesnt show emoji picker if not enabled in chat', function () {

        $auth = User::factory()->create(['name' => 'Namu']);
        $conversation = $auth->createGroup('My Group');

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSeeHtml('dusk="emoji-trigger-button"');
    });

    test('it_shows_emoji_trigger_button', function () {

        $auth = User::factory()->create(['name' => 'Test']);

        testPanelProvider()->emojiPicker();

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="emoji-trigger-button"');
    });

    test('it show dusk="floating-emojipicker" if position floating and doesn show dusk="docked-emojipicker"', function () {

        $auth = User::factory()->create(['name' => 'Test']);

        testPanelProvider()->emojiPicker(position: EmojiPickerPosition::Floating);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="floating-emojipicker"')
            ->assertDontSeeHtml('dusk="docked-emojipicker"');
    });

    test('it show dusk="docked-emojipicker" if position floating and doesn show dusk="floating-emojipicker"', function () {

        $auth = User::factory()->create(['name' => 'Test']);

        testPanelProvider()->emojiPicker(position: EmojiPickerPosition::Docked);

        // create conversation with user1
        $conversation = $auth->createConversationWith($auth, 'hello');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // Assert both conversations visible before typing
        $request->assertSeeHtml('dusk="docked-emojipicker"')
            ->assertDontSeeHtml('dusk="floating-emojipicker"');
    });
});

describe('Message actions: Viewing Private Chat', function () {

    /**
     * Delete for me
     */
    test('it doest shows dusk selector : "delete_message_for_everyone"  if message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $receiver->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it shows dusk selector : "delete_message_for_everyone"  on auths own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $auth->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it shows dusk selector : "delete_message_for_everyone"  on auths own message if deleteMessageActions is on ', function () {

        testPanelProvider()->deleteMessageActions();
        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $auth->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it doesnt show dusk selector : "delete_message_for_everyone"  on auths own message if deleteMessageActions are off ', function () {

        testPanelProvider()->deleteMessageActions(false);
        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $auth->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_everyone"');
    });

    /**
     * Delete for me
     */
    test('it  shows dusk selector : "delete_message_for_me"  if message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $receiver->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_me"');
    });

    test('it shows dusk selector : "delete_message_for_me"  on auths own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $auth->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_me"');
    });

    test('it shows dusk selector : "delete_message_for_me"  when auths own\'s message  and deleteMessageActions if ON', function () {
        testPanelProvider()->deleteMessageActions();

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $auth->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_me"');
    });

    test('it dosnt show dusk selector : "delete_message_for_me"  when auths own\'s message  and deleteMessageActions if off', function () {
        testPanelProvider()->deleteMessageActions(false);

        $auth = User::factory()->create(['name' => 'test']);

        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        // add participant
        $auth->sendMessageTo($conversation, 'Nice things');

        //
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });

    test('it hides reply actions when messageReplyAction is off', function () {
        testPanelProvider()->messageReplyAction(false);

        $auth = User::factory()->create(['name' => 'test']);
        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        $auth->sendMessageTo($conversation, 'Nice things');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things')
            ->assertDontSeeHtml('dusk="reply_to_message_icon"')
            ->assertDontSeeHtml('dusk="reply_to_message_button"')
            ->assertSeeHtml('dusk="delete_message_for_me"');
    });

    test('it does not render message actions when reply and delete message actions are off', function () {
        testPanelProvider()
            ->deleteMessageActions(false)
            ->messageReplyAction(false);

        $auth = User::factory()->create(['name' => 'test']);
        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        $auth->sendMessageTo($conversation, 'Nice things');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things')
            ->assertDontSeeHtml('dusk="message_actions"')
            ->assertDontSeeHtml('dusk="message_actions_dropdown"');
    });

    test('it renders only reply actions when delete message actions are off', function () {
        testPanelProvider()->deleteMessageActions(false);

        $auth = User::factory()->create(['name' => 'test']);
        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        $auth->sendMessageTo($conversation, 'Nice things');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things')
            ->assertSeeHtml('dusk="message_actions"')
            ->assertSeeHtml('dusk="message_actions_dropdown"')
            ->assertSeeHtml('dusk="reply_to_message_icon"')
            ->assertSeeHtml('dusk="reply_to_message_button"')
            ->assertDontSeeHtml('dusk="delete_message_for_me"')
            ->assertDontSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it renders only delete actions when message reply action is off', function () {
        testPanelProvider()->messageReplyAction(false);

        $auth = User::factory()->create(['name' => 'test']);
        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);

        $auth->sendMessageTo($conversation, 'Nice things');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things')
            ->assertSeeHtml('dusk="message_actions"')
            ->assertSeeHtml('dusk="message_actions_dropdown"')
            ->assertSeeHtml('dusk="delete_message_for_me"')
            ->assertSeeHtml('dusk="delete_message_for_everyone"')
            ->assertDontSeeHtml('dusk="reply_to_message_icon"')
            ->assertDontSeeHtml('dusk="reply_to_message_button"');
    });

    test('delete message methods abort when delete message actions are off', function () {
        testPanelProvider()->deleteMessageActions(false);

        $auth = User::factory()->create(['name' => 'test']);
        $receiver = User::factory()->create(['name' => 'User']);
        $conversation = $auth->createConversationWith($receiver);
        $message = $auth->sendMessageTo($conversation, 'Nice things');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt($message->id))
            ->assertStatus(403);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($message->id))
            ->assertStatus(403);
    });
});

describe('Message actions:Viewing Group Chat', function () {

    test('it shows dusk selector : "delete_message_for_everyone"  if auth is OWNER & message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it shows dusk selector : "delete_message_for_everyone" if auth is ADMIN & message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $admin = User::factory()->create(['name' => 'User Admin']);

        $conversation = $auth->createGroup('My Group');

        // add admin
        $conversation->addParticipant($admin, ParticipantRole::ADMIN);

        // add participant and send messsage
        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($admin)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it shows dusk selector : "delete_message_for_everyone"  on auths own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'User']);
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);
        $user->sendMessageTo($conversation, 'Hi');

        // add participant

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Hi') // assert can see message
            ->assertSeeHtml('dusk="delete_message_for_everyone"');
    });

    test('it doesnt show dusk selectors : "delete_message_for_everyone"  if auth is PARTICIPANT and does not own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'User']);
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);

        // add participant and send message by random user
        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message  but not options
            ->assertDontSeeHtml('dusk="delete_message_for_everyone"');
    });

    /**
     * Delete for me
     */
    test('it doesnt show dusk selector : "delete_message_for_me"  if auth is OWNER & message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        // add participant
        $user = User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        // dd($conversation);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });

    test('it doesnt show dusk selector : "delete_message_for_me" if auth is ADMIN & message belongs to another user ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $admin = User::factory()->create(['name' => 'User Admin']);

        $conversation = $auth->createGroup('My Group');

        // add admin
        $conversation->addParticipant($admin, ParticipantRole::ADMIN);

        // add participant and send messsage
        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($admin)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });

    test('it doesnt show dusk selector : "delete_message_for_me"  on auths own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'User Admin']);
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);
        $user->sendMessageTo($conversation, 'Hi');

        // add participant

        // dd($conversation);
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Hi') // assert can see message
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });

    test('it doesnt show dusk selector : "delete_message_for_me"   if auth is PARTICIPANT and does not own message ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $conversation = $auth->createGroup('My Group');

        $user = User::factory()->create(['name' => 'User']);
        $conversation->addParticipant($user, ParticipantRole::PARTICIPANT);

        // add participant and send message by random user
        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Nice things') // assert can see message  but not options
            ->assertDontSeeHtml('dusk="delete_message_for_me"');
    });
});

describe('Testing permissions accssibility ', function () {

    test('it shows footer & message actions but NOT "Only admins can send messages" label if auth is Owner', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $participant = User::factory()->create(['name' => 'John']);
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        // test
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it still shows footer & message actions but does not show "Only admins can send messages" label if auth is Owner when send_messages permission is off', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant
        $participant = User::factory()->create(['name' => 'John']);
        $conversation->addParticipant($participant);

        // send message
        $participant->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = false;
        $group->save();

        // test
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it shows footer & message actions but NOT "Only admins can send messages" label if is Admin and send_messages permission is on', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant ADMIN
        $user = User::factory()->create(['name' => 'John']);
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        // send message
        $user->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = true;
        $group->save();

        // test
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it still shows chat-footer& message actions but NOT "Only admins can send messages" label if auth is Admin when send_messages permission is off', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant ADMIN
        $user = User::factory()->create(['name' => 'John']);
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::ADMIN;
        $participant->save();

        // send message
        $user->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = false;
        $group->save();

        // test
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it shows chat-footer & message actions but NOT "Only admins can send messages" label if auth is PARTICIPANT when send_messages permission is on', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant ADMIN
        $user = User::factory()->create(['name' => 'John']);
        $participant = $conversation->addParticipant($user);

        // send message
        $user->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = true;
        $group->save();

        // test
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertDontSee('Only admins can send messages')
            ->assertSeeHtml('id="chat-footer"')
            ->assertSeeHtml('dusk="message_actions"');
    });

    test('it does not shows chat-footer & message actions but show "Only admins can send messages" label if auth is PARTICIPANT when send_messages permission is off', function () {
        $auth = User::factory()->create();
        // create conversation with user1
        $conversation = $auth->createGroup('My Group');

        // add participant ADMIN
        $user = User::factory()->create(['name' => 'John']);
        $participant = $conversation->addParticipant($user);
        $participant->role = ParticipantRole::PARTICIPANT;
        $participant->save();

        // send message
        $user->sendMessageTo($conversation, 'Hello');

        // Turn off permission
        $group = $conversation->group;
        $group->allow_members_to_send_messages = false;
        $group->save();

        // test
        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Only admins can send messages')
            ->assertDontSeeHtml('id="chat-footer"')
            ->assertDontSeeHtml('dusk="message_actions"');
    });

    // todo: dispatch refresh event after updating permissions

});

describe('Sending messages ', function () {

    // message
    test('it renders new message to chatbox when it is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage')
            ->assertSee('New message')
            ->assertSeeHtml('wc-primary-tone-bg')
            ->assertSeeHtml('ml-auto text-[11px] text-zinc-700 dark:text-white/90')
            ->assertDontSeeHtml('bg-[#f6f6f8fb]');
    });

    test('it renders outgoing message time as white for solid color tone', function () {
        testPanelProvider()->colorTone(ColorTone::Solid);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage')
            ->assertSee('New message')
            ->assertSeeHtml('ml-auto text-[11px] text-white/90')
            ->assertDontSeeHtml('ml-auto text-[11px] text-zinc-700 dark:text-white/90');
    });

    test('it saves new message to database when it is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $messageExists = Message::where('body', 'New message')->exists();

        expect($messageExists)->toBe(true);
    });

    test('it saves text: message type as TEXT', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::where('body', 'New message')->first();

        expect($message->type)->toBe(MessageType::TEXT);
    });

    test('it linkifies message urls when linkify messages is enabled', function () {
        testPanelProvider()->parseMessageUrls(true);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $message = $auth->sendMessageTo($conversation, 'hello https://example.com world');
        $message->type = MessageType::TEXT;
        $message->body = 'hello https://example.com world';
        $message->save();

        $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

        expect($html)
            ->toContain('dusk="message-link"')
            ->toContain('href="https://example.com"')
            ->toMatch('/dusk="message-link"[^>]*class="[^"]*dark:text-white/');
    });

    test('it renders outgoing message links as white for solid color tone', function () {
        testPanelProvider()->parseMessageUrls(true);
        testPanelProvider()->colorTone(ColorTone::Solid);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $message = $auth->sendMessageTo($conversation, 'hello https://example.com world');
        $message->type = MessageType::TEXT;
        $message->body = 'hello https://example.com world';
        $message->save();

        $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

        expect($html)
            ->toContain('dusk="message-link"')
            ->toContain('href="https://example.com"')
            ->toMatch('/dusk="message-link"[^>]*class="[^"]*text-white\/90/')
            ->not->toMatch('/dusk="message-link"[^>]*class="[^"]*dark:text-white/');
    });

    test('it escapes html-like message bodies while rendering', function () {
        testPanelProvider()->parseMessageUrls(true);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $payload = '<img src=x onerror=alert(1)><script>alert(2)</script>';

        $conversation = $auth->createConversationWith($receiver, $payload);

        $html = Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

        expect($html)
            ->toContain(e($payload))
            ->not->toContain($payload)
            ->not->toContain('<img src=x onerror=alert(1)>')
            ->not->toContain('<script>alert(2)</script>');
    });

    test('it preserves whitespace formatting when rendering linkified messages', function () {
        testPanelProvider()->parseMessageUrls(true);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $message = $auth->sendMessageTo($conversation, "hello\nhttps://example.com\nworld");
        $message->type = MessageType::TEXT;
        $message->body = "hello\nhttps://example.com\nworld";
        $message->save();

        $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

        expect($html)
            ->toContain('dusk="message-text"')
            ->toContain('whitespace-pre-wrap')
            ->toContain('dusk="message-link"')
            ->toContain('href="https://example.com"');
    });
    test('it renders message urls as plain text when linkify messages is disabled', function () {
        testPanelProvider()->parseMessageUrls(false);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $message = $auth->sendMessageTo($conversation, 'hello https://example.com world');
        $message->type = MessageType::TEXT;
        $message->body = 'hello https://example.com world';
        $message->save();

        $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

        expect($html)
            ->not->toContain('dusk="message-link"')
            ->toContain('dusk="message-text"')
            ->toContain('https://example.com')
            ->toMatch('/dusk="message-text"[^>]*>[\\s\\S]*hello[\\s\\S]*https:\\/\\/example\\.com[\\s\\S]*world/');
    });

    test('it dispatches livewire event "refresh" & "scroll-bottom" when message is sent', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage')
            ->assertDispatched('refresh')
            ->assertDispatched('scroll-bottom');
    });

    test('it doesn not pushes job "BroadcastMessage" when message is sent', function () {
        Event::fake();
        Queue::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Queue::assertNotPushed(BroadcastMessage::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    });

    test('it broadcasts event "MessageCreated" when message is sent', function () {
        Event::fake();
        //   Queue::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    });

    test('it pushes job "NotifyParticipants" when conversation is private', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class, function ($event) use ($conversation, $message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });

    test('it does not push job "NotifyParticipants" when conversation is Self', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $conversation = $auth->createConversationWith($auth);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        Queue::assertNotPushed(NotifyParticipants::class);
    });

    test('it only pushes job "NotifyParticipants" when conversation is a Group ', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::GROUP]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class, function ($event) use ($conversation, $message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });

    test('it pushed job "NotifyParticipants" when message is sent to private conversation', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class, function ($event) use ($conversation, $message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });

    test('it does not broadcasts event "MessageCreated" if it is SelfConversation', function () {
        Event::fake();
        //   Queue::fake();
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($auth);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'New message')
            ->call('sendMessage');

        $message = Message::first();

        Event::assertNotDispatched(MessageCreated::class);
    });

    test('sending messages is rate limited by 60 in 60 seconds', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        Carbon::setTestNow(Carbon::now()); // Freeze the current time

        for ($i = 0; $i < 60; $i++) {
            RateLimiter::increment('send-message:'.$auth->id);
        }

        // Move the time forward slightly for the 61st message
        Carbon::setTestNow(Carbon::now()->addSeconds(4));
        // on 61 abort
        $request->set('body', 'New message')->call('sendMessage');

        $request->assertStatus(429);
    });

    // sending like
    test('it renders heart(❤️) to chatbox when it sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike')
            ->assertSee('❤️');
    });

    test('it saves the heart(❤️) to database when sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $messageExists = Message::where('body', '❤️')->exists();
        expect($messageExists)->toBe(true);
    });

    test('it saves textheart(❤️): message type as TEXT', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::where('body', '❤️')->first();

        expect($message->type)->toBe(MessageType::TEXT);
    });

    test('it dispatches livewire event "refresh" & "scroll-bottom" when sendLike is called', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike')
            ->assertDispatched('refresh')
            ->assertDispatched('scroll-bottom');
    });

    test('it Broadcaste event job "MessageCreted" when sendLike is called and conversation is Group', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::GROUP]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Event::assertDispatched(MessageCreated::class);
    });

    test('it Broadcaste event job "MessageCreted" when sendLike is called and conversation is PRIVATE', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::PRIVATE]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Event::assertDispatched(MessageCreated::class);
    });

    test('it does not Broadcaste event job "MessageCreted" when sendLike is called and conversation is SELF', function () {
        Event::fake();
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth])
            ->create(['type' => ConversationType::SELF]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Event::assertNotDispatched(MessageCreated::class);
    });

    test('it pushed job "NotifyParticipants" when sendLike is called and is GROUP', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::GROUP]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class, function ($event) use ($conversation, $message) {
            return $event->conversation->id === $message->id && $event->message->id === $conversation->id;
        });
    });

    test('it pushes job "NotifyParticipants" when sendLike is called and is PRIVATE', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::PRIVATE]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class);
    });

    test('it does not pushed job "NotifyParticipants" when sendLike is called and is SELF', function () {
        Event::fake();
        Queue::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth])
            ->create(['type' => ConversationType::SELF]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Queue::assertNotPushed(NotifyParticipants::class);
    });

    test('it pushed job "NotifyParticipants" when sendLike is called when conversation is PRIVATE', function () {
        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create(['type' => ConversationType::PRIVATE]);

        Livewire::actingAs($auth)
            ->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        Queue::assertPushed(NotifyParticipants::class, function ($job) use ($conversation) {
            return $job->conversation->id === $conversation->id;
        });
    });

    test('it does not broadcasts job "NotifyParticipants" when sendLike is called when conversation is SELF', function () {

        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth])
            ->create(['type' => ConversationType::SELF]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Queue::assertNotPushed(NotifyParticipants::class, function ($job) use ($conversation) {
            return $job->conversation->id === $conversation->id;
        });
    });

    test('it pushed  job "NotifyParticipants" when sendLike is called when conversation is GROUP', function () {

        Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth])
            ->create(['type' => ConversationType::GROUP]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('sendLike');

        $message = Message::first();

        Queue::assertPushed(NotifyParticipants::class, function ($job) use ($conversation) {
            return $job->conversation->id === $conversation->id;
        });
    });

    test('sending hearts(❤️) is rate limited by 50 in 60 seconds', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        for ($i = 0; $i < 60; $i++) {
            RateLimiter::increment('send-message:'.$auth->id);
        }

        // Test that the rate limit is hit
        $request->call('sendLike');
        $request->assertStatus(429);
    });

    // attchements
    test('it saves image record to databse when created & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png', 640, 480);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            ->assertDispatched('scroll-bottom')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $attachment = Attachment::first();

        expect(Attachment::count())->toBe(1)
            ->and($attachment->meta)
            ->toMatchArray([
                'image' => [
                    'width' => 640,
                    'height' => 480,
                    'orientation' => 'landscape',
                    'aspect_ratio' => 1.3333,
                ],
            ]);
    });

    test('it appends media uploaded one by one and preserves image metadata when sent', function () {
        Storage::fake('public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $firstImage = UploadedFile::fake()->image('first-photo.png', 1200, 800);
        $secondImage = UploadedFile::fake()->image('second-photo.jpg', 600, 900);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request->upload('media', [$firstImage]);
        $request->upload('media', [$secondImage]);

        $uploadedMedia = $request->get('media');

        expect($uploadedMedia)->toHaveCount(2)
            ->and(collect($uploadedMedia)->map->getClientOriginalName()->all())
            ->toBe(['first-photo.png', 'second-photo.jpg']);

        $request->call('sendMessage')
            ->assertSet('media', []);

        $attachments = Attachment::query()
            ->orderBy('id')
            ->get(['original_name', 'mime_type', 'file_path', 'meta']);

        expect($attachments)->toHaveCount(2)
            ->and($attachments->pluck('original_name')->all())
            ->toBe(['first-photo.png', 'second-photo.jpg'])
            ->and($attachments->pluck('mime_type')->all())
            ->toBe(['image/png', 'image/jpeg'])
            ->and($attachments[0]->meta)
            ->toMatchArray([
                'image' => [
                    'width' => 1200,
                    'height' => 800,
                    'orientation' => 'landscape',
                    'aspect_ratio' => 1.5,
                ],
            ])
            ->and($attachments[1]->meta)
            ->toMatchArray([
                'image' => [
                    'width' => 600,
                    'height' => 900,
                    'orientation' => 'portrait',
                    'aspect_ratio' => 0.6667,
                ],
            ]);

        foreach ($attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment->file_path);
        }
    });

    test('attachment downloads require access to the attachment conversation', function () {
        Storage::fake('public');
        Config::set('wirechat.storage.disk', 'public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create();
        $intruder = User::factory()->create();
        $intruderPeer = User::factory()->create();

        $conversation = $auth->createConversationWith($receiver);
        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'participant_id' => $conversation->participant($auth)?->id,
            'type' => MessageType::ATTACHMENT,
        ]);

        $path = Wirechat::storage()->attachmentsDirectory().'/secret.txt';
        Storage::disk('public')->put($path, 'secret content');

        $attachment = $message->attachment()->create([
            'file_path' => $path,
            'file_name' => 'secret.txt',
            'original_name' => 'secret.txt',
            'mime_type' => 'text/plain',
            'url' => Storage::disk('public')->url($path),
        ]);

        $intruderConversation = $intruder->createConversationWith($intruderPeer);

        Livewire::actingAs($intruder)->test(ChatBox::class, ['conversation' => $intruderConversation->id])
            ->call('download', encrypt($attachment->id))
            ->assertStatus(403);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('download', encrypt($attachment->id))
            ->assertFileDownloaded('secret.txt');
    });

    test('it renders stored generic image attachments as images using the original extension', function () {
        Storage::fake('public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();
        $participant = $conversation->participant($auth);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'type' => MessageType::ATTACHMENT,
        ]);

        $message->attachment()->create([
            'file_path' => Wirechat::storage()->attachmentsDirectory().'/legacy-photo.png',
            'file_name' => 'legacy-photo.png',
            'original_name' => 'legacy-photo.png',
            'mime_type' => 'application/octet-stream',
            'url' => '/storage/'.Wirechat::storage()->attachmentsDirectory().'/legacy-photo.png',
        ]);

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSeeHtml('<img');
    });

    test('it renders media attachments with bounded image and video sizing', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();
        $participant = $conversation->participant($auth);

        $imageMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'type' => MessageType::ATTACHMENT,
            'created_at' => Carbon::parse('2026-06-21 14:30:00'),
        ]);

        $imageMessage->attachment()->create([
            'file_path' => Wirechat::storage()->attachmentsDirectory().'/photo.png',
            'file_name' => 'photo.png',
            'original_name' => 'photo.png',
            'mime_type' => 'image/png',
            'url' => 'https://example.test/photo.png',
            'meta' => [
                'image' => [
                    'width' => 1024,
                    'height' => 768,
                    'orientation' => 'landscape',
                    'aspect_ratio' => 1.3333,
                ],
            ],
        ]);

        $videoMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'type' => MessageType::ATTACHMENT,
            'created_at' => Carbon::parse('2026-06-21 14:31:00'),
        ]);

        $videoMessage->attachment()->create([
            'file_path' => Wirechat::storage()->attachmentsDirectory().'/clip.mp4',
            'file_name' => 'clip.mp4',
            'original_name' => 'clip.mp4',
            'mime_type' => 'video/mp4',
            'url' => 'https://example.test/clip.mp4',
            'meta' => [
                'video' => [
                    'width' => 720,
                    'height' => 1280,
                    'orientation' => 'portrait',
                    'aspect_ratio' => 0.5625,
                ],
            ],
        ]);

        $landscapeVideoMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'type' => MessageType::ATTACHMENT,
            'created_at' => Carbon::parse('2026-06-21 14:32:00'),
        ]);

        $landscapeVideoMessage->attachment()->create([
            'file_path' => Wirechat::storage()->attachmentsDirectory().'/landscape-clip.mp4',
            'file_name' => 'landscape-clip.mp4',
            'original_name' => 'landscape-clip.mp4',
            'mime_type' => 'video/mp4',
            'url' => 'https://example.test/landscape-clip.mp4',
            'meta' => [
                'video' => [
                    'width' => 1920,
                    'height' => 1080,
                    'orientation' => 'landscape',
                    'aspect_ratio' => 1.7778,
                ],
            ],
        ]);

        $fileMessage = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'type' => MessageType::ATTACHMENT,
            'created_at' => Carbon::parse('2026-06-21 14:33:00'),
        ]);

        $fileMessage->attachment()->create([
            'file_path' => Wirechat::storage()->attachmentsDirectory().'/report.pdf',
            'file_name' => 'report.pdf',
            'original_name' => 'report.pdf',
            'mime_type' => 'application/pdf',
            'url' => 'https://example.test/report.pdf',
            'meta' => ['size' => 1048576],
        ]);

        $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

        expect($html)
            ->toContain('<img')
            ->toContain('<video')
            ->toContain('wire:ignore')
            ->toContain('preload="metadata"')
            ->toContain('playsinline')
            ->toContain('[overflow-anchor:none]')
            ->toContain('h-[24rem] w-[13.5rem]')
            ->toContain('h-[14.625rem] w-[26rem]')
            ->toContain('h-[14.625rem] sm:max-w-[26rem]')
            ->toContain('width="1024"')
            ->toContain('height="768"')
            ->toContain('width="720"')
            ->toContain('height="1280"')
            ->toContain('width="1920"')
            ->toContain('height="1080"')
            ->toContain('dusk="message-attachment-shell"')
            ->toContain('dusk="message-attachment-time"')
            ->toContain('dusk="message-file-attachment"')
            ->toContain('dusk="message-file-extension"')
            ->toContain('dusk="message-file-meta"')
            ->toContain('PDF')
            ->toContain('1 MB')
            ->toContain('truncate text-sm font-medium text-zinc-900 dark:text-zinc-100')
            ->toContain('mt-1 flex items-center gap-1.5 text-xs font-medium uppercase leading-none text-zinc-600 dark:text-zinc-300')
            ->toContain('wire:click="download')
            ->toContain('p-1')
            ->toContain('wc-primary-tone-bg')
            ->toContain('max-h-[24rem]')
            ->toContain('sm:max-w-[26rem]')
            ->toContain('h-full w-auto max-w-full')
            ->toContain('object-contain')
            ->toContain('rounded-xl')
            ->not->toContain('h-[200px]')
            ->not->toContain('min-h-[210px]')
            ->not->toContain('max-h-[400px]')
            ->not->toContain('rounded-3xl')
            ->not->toContain('href="https://example.test/report.pdf"')
            ->not->toContain('download="report.pdf"');

        expect(substr_count($html, 'dusk="message-attachment-shell"'))->toBe(4);
        expect(substr_count($html, 'dusk="message-attachment-time"'))->toBe(4);
        expect(preg_match_all('/dusk="message-attachment-time"[^>]*>\s*\d{2}:\d{2}\s*<\/span>/s', $html))->toBe(4);
    });

    test('it renders outgoing file attachment details with solid color tone contrast', function () {
        testPanelProvider()->colorTone(ColorTone::Solid);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();
        $participant = $conversation->participant($auth);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'participant_id' => $participant->id,
            'type' => MessageType::ATTACHMENT,
            'created_at' => Carbon::parse('2026-06-21 14:33:00'),
        ]);

        $message->attachment()->create([
            'file_path' => Wirechat::storage()->attachmentsDirectory().'/Archive.zip',
            'file_name' => 'Archive.zip',
            'original_name' => 'Archive.zip',
            'mime_type' => 'application/zip',
            'url' => 'https://example.test/Archive.zip',
            'meta' => ['size' => 9856614],
        ]);

        $html = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->html();

        expect($html)
            ->toContain('Archive.zip')
            ->toContain('ZIP')
            ->toContain('9.4 MB')
            ->toMatch('/class="[^"]*truncate text-sm font-medium text-white"[^>]*>\s*Archive\.zip\s*<\/p>/s')
            ->toMatch('/dusk="message-file-extension"[^>]*class="[^"]*text-zinc-700/')
            ->toMatch('/dusk="message-file-meta"[^>]*class="[^"]*text-white\/80/')
            ->toMatch('/dusk="message-attachment-time"[^>]*class="[^"]*text-white\/90/')
            ->toMatch('/wire:click="download[^>]*class="[^"]*border-white\/30 text-white\/85 hover:text-white/');
    });

    test('it saves image to storage when created & clears files properties when done', function () {
        Storage::fake('public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $attachment = Attachment::first();
        Storage::disk('public')->assertExists(Wirechat::storage()->attachmentsDirectory().'/'.$attachment->file_anme);
    });

    test('it saves file visibility as public when storage_disk is public', function () {
        Storage::fake('public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $attachment = Attachment::first();
        $visibility = Storage::disk('public')->getVisibility(Wirechat::storage()->attachmentsDirectory().'/'.$attachment->file_anme);

        expect($visibility)->toBe('public');
    });

    test('it saves file visibility as public when storage_disk is s3', function () {
        Storage::fake('s3');

        Config::set('wirechat.storage.disk', 's3');
        Config::set('wirechat.storage.visibility', 'private');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $attachment = Attachment::first();
        $visibility = Storage::disk('s3')->getVisibility(Wirechat::storage()->attachmentsDirectory().'/'.$attachment->file_anme);

        expect($visibility)->toBe('public');
    });

    test('it saves image: message type as attachemnt ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()->withParticipants([$auth, $receiver])->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            // now assert that media is back to empty
            ->assertSet('media', []);

        $message = $conversation->messages()->first();

        expect($message->type)->toBe(MessageType::ATTACHMENT);
    });

    test('it renders image  to chatbox when it attachement is sent & clears files properties when done', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file[] = UploadedFile::fake()->image('photo.png');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage')
            ->assertSeeHtml('<img')
            // now assert that media is back to empty
            ->assertSet('media', []);

        // $messageExists = Attachment::all();
        // dd($messageExists);

    });

    // video
    test('it saves video to databse when created', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file = UploadedFile::fake()->create('sample.mp4', '1000', 'video/mp4');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage');

        $messageExists = Attachment::all();
        expect(count($messageExists))->toBe(1);
    });

    test('it saves video to storage when created', function () {
        Storage::fake('public');

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file = UploadedFile::fake()->create('sample.mp4', '1000', 'video/mp4');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage');

        $attachment = Attachment::first();
        Storage::disk('public')->assertExists(Wirechat::storage()->attachmentsDirectory().'/'.$attachment->file_anme);
    });

    test('it saves video: message type as attachemnt', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file = UploadedFile::fake()->create('sample.mp4', '1000', 'video/mp4');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('media', $file)
            ->call('sendMessage');

        $message = $conversation->messages()->first();

        expect($message->type)->toBe(MessageType::ATTACHMENT);
    });

    test('it saves file to databse when created & clears files properties when done', function () {

        Config::set('wirechat.storage.disk', 'public');
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file[] = UploadedFile::fake()->create('photo.pdf', '400', 'application/pdf');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('files', $file)
            ->call('sendMessage')
            // now assert that file is back to empty
            ->assertSet('files', []);

        $messageExists = Attachment::all();

        expect(count($messageExists))->toBe(1);

        $attachment = Attachment::first();

        expect($attachment->meta)->toHaveKey('size')
            ->and($attachment->size)->toBeGreaterThan(0)
            ->and($attachment->extension)->toBe('pdf')
            ->and($attachment->formatted_size)->not->toBeNull();
    });

    test('it saves file to storage when created & clears files properties when done', function () {

        Storage::fake(Wirechat::storage()->disk());
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $file[] = UploadedFile::fake()->create('photo.pdf', '400', 'application/pdf');
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('files', $file)
            ->call('sendMessage')
            // now assert that file is back to empty
            ->assertSet('files', []);

        $attachment = Attachment::first();
        Storage::disk('public')->assertExists(Wirechat::storage()->attachmentsDirectory().'/'.$attachment->file_anme);
    });

    test('dispatched event is listened to in chatlist after message is created', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // assert no message yet
        $chatListComponet = Livewire::actingAs($auth)->test(Chatlist::class)->assertDontSee('new message');

        // send message
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->set('body', 'new message')
            ->call('sendMessage');

        // assert message created
        $chatListComponet->dispatch('refresh')->assertSee('new message');
    });
});

describe('Sending reply', function () {

    // reply messages

    test('it throws Payload DecryptException error if id is not encrypted', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('setReply', $message->id)
            ->assertStatus(500);
    })->throws(DecryptException::class);

    test('it doesnt throw DecryptException invalid error if id is encrypted', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('setReply', encrypt($message->id));
    })->throwsNoExceptions();

    test('it returns abort(404) when replying if message does not belong to this conversation or is not owned by any participant', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // create random message not belonging to auth user
        $randomuser = User::factory()->create();
        $randomUSer2 = User::factory()->create();
        $randomMessage = $randomuser->sendMessageTo($randomUSer2, message: 'How are you');

        // send message
        $auth->sendMessageTo($receiver, message: 'How are you');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('setReply', encrypt($randomMessage->id))
            ->assertStatus(404);
    })->throws(ModelNotFoundException::class);

    test('it can set reply message when setReply is called', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('setReply', encrypt($message->id))
            ->assertSet('replyMessage.id', $message->id);

    });

    test('setReply aborts when message reply action is off', function () {
        testPanelProvider()->messageReplyAction(false);

        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('setReply', encrypt($message->id))
            ->assertStatus(403);
    });

    test('existing reply previews still render when message reply action is off', function () {
        testPanelProvider()->messageReplyAction(false);

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver, 'Original message');

        $parent = Message::query()->where('conversation_id', $conversation->id)->firstOrFail();
        $reply = $receiver->sendMessageTo($conversation, message: 'Reply message');
        $reply->forceFill(['reply_id' => $parent->id])->save();

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('Reply message')
            ->assertSee('Original message')
            ->assertDontSeeHtml('dusk="reply_to_message_button"');
    });

    test('it shows "replying to yourself" when auth is replying to own message ', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver);

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        // dd($conversation->id,$message->conversation_id);
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('setReply', encrypt($message->id))
            ->call('$refresh')
            // we test seprate because the text is not in same HTML tag
            ->assertSee('Replying to')
            ->assertSee('Yourself');
    });
    test('it dispatches "focus-input-field" when reply is set', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('setReply', encrypt($message->id))
            ->assertDispatched('focus-input-field');
    });

    test('it can remove reply message when removeReply is called ', function () {
        $auth = User::factory()->create();

        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = Conversation::factory()
            ->withParticipants([$auth, $receiver])
            ->create();

        // send messages
        $message = $auth->sendMessageTo($receiver, message: 'How are you');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('removeReply')
            ->assertSet('replyMessage', null);
    });
});

describe('Deleting Conversation', function () {
    beforeEach(function () {
        testPanelProvider()->deleteChatAction();
    });

    test('it redirects to chats route after deleting conversation', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '5');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request
            ->call('deleteConversation')
            ->assertStatus(200)
            ->assertRedirect(testPanelProvider()->chatsRoute());
    });

    test('Logged in user can still access deleted conversation in chat route or chatbox', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        //    dd($receiver->sendMessageTo($auth, message: '4')->conversation->id,$conversation->id);

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteConversation');

        // assert chatbox
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->assertStatus(200);

        // assert chat route
        $this->actingAs($auth)->get(testPanelProvider()->chatRoute($conversation->id))->assertStatus(200);
    });

    test('user can regain access to deleted conversation if receiver/other user send a new message', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('deleteConversation');

        Carbon::setTestNow(now()->addSeconds(4));

        // let receiver send a new message
        $receiver->sendMessageTo($auth, message: '5');

        // assert conversation will be null
        expect($auth->conversations()->first())->not->toBe(null);

        $route = testPanelProvider()->chatRoute($conversation->id);
        // dd($route);
        // also assert that user receives 403 forbidden
        $response = $this->actingAs($auth)->get($route)->assertStatus(200);

    });

    test('user can regain access to deleted conversation if they send a new message after deleting conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('deleteConversation');

        Carbon::setTestNow(now()->addSeconds(4));
        // let auth send a new message to conversation after deleting
        $auth->sendMessageTo($receiver, message: '5');

        // assert conversation will be null
        expect($auth->conversations()->first())->not->toBe(null);

        // also assert that user receives 403 forbidden
        $this->actingAs($auth)->get(testPanelProvider()->chatsRoute())->assertStatus(200);
    });

    test('deleted convesation should be available in database if only one user has deleted it', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // $conversation->deleteFor($auth);

        //  $conversation = Conversation::all();
        // dd($conversation);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3');
        $receiver->sendMessageTo($auth, message: '4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('deleteConversation');

        $conversation = Conversation::withoutGlobalScopes()->find($conversation->id);
        expect($conversation)->not->toBe(null);
    });

    test('user shold not be able to see previous messages present when conversation was deleted if they send a new message, but should be able to see new ones ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // begin
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        Carbon::setTestNow(now()->addMinute(4));
        $request->call('deleteConversation');

        Auth::logout();
        // send new message in order to gain access to converstion
        Carbon::setTestNow(now()->addMinute(20));
        $auth->sendMessageTo($receiver, message: '5 message');

        // open conversation again
        $request2 = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert user can't see previous messages
        $request2
            ->assertDontSee('1 message')
            ->assertDontSee('2 message')
            ->assertDontSee('3 message')
            ->assertDontSee('4 message');

        // assert user can see new messages
        $request2
            ->assertSee('5 message');
    });

    test('receiver in the conversation should be able to see all messages even when auth/other user deletes conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // /reqeust for $auth to delete conversation
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteConversation');

        Auth::logout();

        // //send after deleting conversation
        Carbon::setTestNow(now()->addMinute(20));
        $auth->sendMessageTo($receiver, message: '5 message');
        // dd($message,$conversation);

        // /request for $receiver to access conversation
        $request = Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert receiver can see previous messages
        $request
            ->assertSee('1 message')
            ->assertSee('2 message')
            ->assertSee('3 message')
            ->assertSee('4 message');

        // assert user can see new messages
        $request->assertSee('5 message');
    });

    test('it resets conversation_deleted_at value of auth-particiapant if new message is added to conversation by other user and user opens chat ', function () {

        $auth = User::factory()->create(['name' => 'Mike']);
        $receiver = User::factory()->create(['name' => 'John']);

        Carbon::setTestNow(now()->subMinutes(20));

        $conversation = $auth->createConversationWith($receiver, 'hi');

        Carbon::setTestNow(now()->addMinutes(4));

        // /load and delete conversation
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteConversation');

        // send message from receiver && reset TIME

        Carbon::setTestNow(now()->addMinutes(10));

        $message = $auth->sendMessageTo($conversation, message: '4 message');

        // load again
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->assertOk();

        // assert

        $authParticipant = $conversation->participant($auth);

        //  dd(['message'=>$message->created_at->toString(),'participant'=>$authParticipant->conversation_deleted_at->toString(),'conversation'=>$conversation->updated_at->toString()]);
        expect($authParticipant->conversation_deleted_at)->toBe(null);
    });

    describe('IsWidget:--', function () {

        test('it does not redirects to chats route after deleting conversation', function () {
            testPanelProvider()->deleteChatAction();

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('deleteConversation')
                ->assertStatus(200)
                ->assertNoRedirect();
        });

        test('it dispatches "close-chat" evnt after deleting conversation', function () {
            testPanelProvider()->deleteChatAction();

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('deleteConversation')
                ->assertDispatched('close-chat');
        });

        test('it dispatches "chat-deleted" event after Deleting conversation', function () {
            testPanelProvider()->deleteChatAction();

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('deleteConversation')
                ->assertDispatched('chat-deleted');
        });

        test('Deleted chat should no longer appea in Chats componnet when "chat-deleted" event is dispacted after Deleting conversation', function () {
            testPanelProvider()->deleteChatAction();

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver, 'Hello my message');

            // Open chats list
            $CHATLIST = Livewire::actingAs($auth)->test(Chatlist::class);

            // Assert conversation is visible
            $CHATLIST->assertViewHas('conversations', function ($conversation) {
                return count($conversation) == 1;
            });

            // login into chat component
            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('deleteConversation')
                ->assertDispatched('chat-deleted');

            // Assert conversation no longer visible in chats after claring chat
            $CHATLIST->dispatch('chat-deleted', $conversation->id)->assertViewHas('conversations', function ($conversation) {
                return count($conversation) == 0;
            });
        });
    });

    // test('it does not also resets conversation_deleted_at value of auth-particiapant they send new message from Chat within component to conversation themselves ', function () {

    //     $auth = User::factory()->create();
    //     $receiver = User::factory()->create(['name' => 'John']);

    //     $conversation = $auth->createConversationWith($receiver);

    //     $authParticipant = $conversation->participant($auth);

    //     //auth -> receiver
    //     $auth->sendMessageTo($receiver, message: '1 message');
    //     $auth->sendMessageTo($receiver, message: '2 message');

    //     //receiver -> auth
    //     $receiver->sendMessageTo($auth, message: '3 message');
    //     $receiver->sendMessageTo($auth, message: '4 message');

    //     ///load and delete conversation
    //     Carbon::setTestNow(now()->addSeconds(4));
    //     Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->call("deleteConversation");

    //     //assert not null
    //     $authParticipant->refresh();
    //     expect($authParticipant->conversation_deleted_at)->not->toBe(null);

    //     //load again
    //     Carbon::setTestNow(now()->addSeconds(20));
    //     Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
    //     ->set('body','hello')
    //     ->call('sendMessage');

    //     //assert
    //     $authParticipant->refresh();
    //     expect($authParticipant->conversation_deleted_at)->toBe(null);

    // });

});

describe('Clearing Conversation', function () {
    beforeEach(function () {
        testPanelProvider()->clearChatAction();
    });

    test('user should still have access after deleting conversation', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // begin
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('clearConversation');

        Auth::logout();
        // send new message in order to gain access to converstion
        // Carbon::setTestNow(now()->addMinute(20));
        $auth->sendMessageTo($receiver, message: '5 message');

        // open conversation again
        $request2 = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->assertOk();
    });

    test('user shold not be able to see previous messages present after conversation was clear if they send a new message, but should be able to see new ones ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1 message');
        $auth->sendMessageTo($receiver, message: '2 message');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '3 message');
        $receiver->sendMessageTo($auth, message: '4 message');

        // begin
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);
        $request->call('clearConversation');

        Auth::logout();
        // send new message in order to gain access to converstion
        Carbon::setTestNow(now()->addMinute(20));
        $auth->sendMessageTo($receiver, message: '5 message');

        // open conversation again
        $request2 = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert user can't see previous messages
        $request2
            ->assertDontSee('1 message')
            ->assertDontSee('2 message')
            ->assertDontSee('3 message')
            ->assertDontSee('4 message');

        // assert user can see new messages
        $request2
            ->assertSee('5 message');
    });

    test('it redirects to chats route after clearing conversation', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '5');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request
            ->call('clearConversation')
            ->assertStatus(200)

            ->assertRedirect(testPanelProvider()->chatsRoute());
    });

    test('user can still open conversatoin after clearing it ', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: '1');
        $auth->sendMessageTo($receiver, message: '2');
        $auth->sendMessageTo($receiver, message: '3');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: '4');
        $receiver->sendMessageTo($auth, message: '5');
        $receiver->sendMessageTo($auth, message: '5');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        $request
            ->call('clearConversation');

        // assert
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])->assertOk();
    });

    describe('IsWidget:', function () {

        test('it does not redirects to chats route after deleting conversation', function () {
            testPanelProvider()->clearChatAction();

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('clearConversation')
                ->assertStatus(200)
                ->assertNoRedirect();
        });

        test('it dispatches "close-chat" event after clearing conversation', function () {
            testPanelProvider()->clearChatAction();

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('clearConversation')
                ->assertDispatched('close-chat');
        });

        test('it dispatches "refresh" event after Clearing conversation', function () {
            testPanelProvider()->clearChatAction();

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver);

            // auth -> receiver
            $auth->sendMessageTo($receiver, message: '1');
            $auth->sendMessageTo($receiver, message: '2');
            $auth->sendMessageTo($receiver, message: '3');

            // receiver -> auth
            $receiver->sendMessageTo($auth, message: '4');
            $receiver->sendMessageTo($auth, message: '5');
            $receiver->sendMessageTo($auth, message: '5');

            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('clearConversation')
                ->assertDispatched('refresh');
        });

        test('message is cleared/updated in Chats componnet when refresh "refresh" event is dispacted after Clearing conversation', function () {
            testPanelProvider()->clearChatAction();

            $auth = User::factory()->create();
            $receiver = User::factory()->create(['name' => 'John']);

            $conversation = $auth->createConversationWith($receiver, 'Hello my message');

            // Open chats list
            $CHATLIST = Livewire::actingAs($auth)->test(Chatlist::class);
            // Assert messsage is visible
            $CHATLIST->assertSee('Hello my message');

            // login into chat component
            $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('clearConversation')
                ->assertDispatched('refresh');

            // Assert message no longer visible in chats after claring chat
            $CHATLIST->dispatch('refresh')->assertDontSee('Hello my message');
        });
    });
});

describe('Exiting Conversation', function () {

    test('user cannot access conversation after exiting', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);
        $user->sendMessageTo($conversation, 'hi');
        $user->exitConversation($conversation); // exit here

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertStatus(403);
    });

    test('it redirects after exiting conversation to chats', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);
        $user->sendMessageTo($conversation, 'hi');

        Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('exitConversation')
            ->assertRedirect(testPanelProvider()->chatsRoute());
    });

    test('owner cannot exit conversation', function () {
        //    Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();

        // create group
        $conversation = $auth->createGroup(name: 'New group', description: 'description');
        $auth->sendMessageTo($conversation, 'hi');

        // add user and exit conversation
        $user = User::factory()->create();
        $conversation->addParticipant($user);
        $user->sendMessageTo($conversation, 'hi');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('exitConversation')
            ->assertStatus(403, 'Owner cannot exit conversation');

        expect($auth->belongsToConversation($conversation))->toBe(true);
    });

    test('Throws error if user tries to exit priveat or self conversation', function () {
        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver, 'hello');

        // login as user not auth (Owner)
        Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('exitConversation')
            ->assertStatus(403, 'Cannot exit self or private conversation');

        expect($auth->belongsToConversation($conversation))->toBe(true);
    });

    describe('IsWidget:', function () {

        test('it does not redirects to chats route after Exiting Group conversation', function () {
            $auth = User::factory()->create();

            // create group
            $conversation = $auth->createGroup(name: 'New group', description: 'description');
            $auth->sendMessageTo($conversation, 'hi');

            // add user and exit conversation
            $user = User::factory()->create();
            $conversation->addParticipant($user);
            $user->sendMessageTo($conversation, 'hi');

            // login as user not auth (Owner)
            $request = Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('exitConversation')
                ->assertStatus(200)
                ->assertNoRedirect();
        });

        test('it dispatches "close-chat" evnt after Exiting Group conversation', function () {
            $auth = User::factory()->create();

            // create group
            $conversation = $auth->createGroup(name: 'New group', description: 'description');
            $auth->sendMessageTo($conversation, 'hi');

            // add user and exit conversation
            $user = User::factory()->create();
            $conversation->addParticipant($user);
            $user->sendMessageTo($conversation, 'hi');

            // login as user not auth (Owner)
            $request = Livewire::actingAs($user)->test(ChatBox::class, ['conversation' => $conversation->id, 'widget' => true]);

            $request
                ->call('exitConversation')
                ->assertStatus(200)
                ->assertDispatched('close-chat');
        });
    });
});

describe('deleteMessage ForEveryone', function () {

    test('user cannot delete message that does not belong to them ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage = $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt($otherUserMessage->id))
            ->assertStatus(403);

        $messageAvailable = Message::find($otherUserMessage->id);

        // /assert message no longer visible
        expect($messageAvailable)->not->toBe(null);
    });

    test('IN GROUP: Admin can delete message that does not belong to them ', function () {

        $auth = User::factory()->create(['name' => 'test']);
        $admin = User::factory()->create(['name' => 'User Admin']);

        $conversation = $auth->createGroup('My Group');

        // add admin
        $conversation->addParticipant($admin, ParticipantRole::ADMIN);

        // add participant and send messsage

        User::factory()->withMessage($conversation, 'Nice things')->create(['name' => 'user']);

        Livewire::actingAs($admin)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt('1'))
            ->assertStatus(200);

        $messageAvailable = Message::find('1');

        // /assert message no longer visible
        expect($messageAvailable)->toBe(null);
    });

    test('deleted message is removed from blade', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert count 4
        $request->assertSet('loadedMessages', function ($messages) {
            return collect($messages)->flatten(1)->count() == 4;
        });

        // call deleteForMe
        $request->call('deleteForEveryone', encrypt($authMessage->id));

        // assert count no 3
        $request->assertSet('loadedMessages', function ($messages) {
            return collect($messages)->flatten(1)->count() == 3;
        });
    });
    test('it throws DecryptException if id is not Encrypted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert count 4
        $request->assertSet('loadedMessages', function ($messages) {
            return collect($messages)->flatten(1)->count() == 4;
        });

        // call deleteForMe
        $request->call('deleteForEveryone', $authMessage->id);

        // assert count no 3
        $request->asssertStatus(500);
    })->throws(DecryptException::class);

    test('it does Not throws DecryptException if id is Encrypted', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // call deleteForMe
        $request->call('deleteForEveryone', encrypt($authMessage->id));

        // assert count no 3
    })->throwsNoExceptions();

    test('deleted message is removed database', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($authMessage->id));

        $messageAvailable = Message::find($authMessage->id);

        // /assert message no longer visible
        expect($messageAvailable)->toBe(null);
    });

    test('it deletes attachment from database when message is deleted ', function () {

        Storage::fake(Wirechat::storage()->disk());

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver);

        $file[] = UploadedFile::fake()->image('photo.png');

        // run
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            // add attachment
            ->set('media', $file)
            ->call('sendMessage');

        // /lets make sure atttachemnt is present in database

        expect(count(Attachment::all()))->toBe(1);

        // Now lets unsend message
        // here assuming that the message ID is 1 since it is the first one
        $request->call('deleteForEveryone', encrypt(1));

        // /assert attachment no longer avaible in database
        expect(count(Attachment::all()))->toBe(0);
    });

    test('it deletes attachment file from folder when message is deleted ', function () {

        Storage::fake(Wirechat::storage()->disk());

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver);

        $file[] = UploadedFile::fake()->image('photo.png');

        // run
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            // add attachment
            ->set('media', $file)
            ->call('sendMessage');

        $attachmentModel = Attachment::first();
        $messageModel = Message::first();

        // Now lets unsend message
        // here assuming that the message ID is 1 since it is the first one
        $request->call('deleteForMe', encrypt($messageModel->id));

        Storage::disk(Wirechat::storage()->disk())->assertMissing($attachmentModel->file_name);
    });

    test('it disptaches refresh event and removes deleted message from chatlist', function () {

        Storage::fake(Wirechat::storage()->disk());

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);
        $conversation = $auth->createConversationWith($receiver, 'This is message');

        $CHATLIST = Livewire::actingAs($auth)->test(Chatlist::class)->assertSee('This is message');

        // run
        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            // add attachment
            ->call('deleteForEveryone', encrypt(1))
            ->assertDispatched('refresh');

        // assert
        $CHATLIST->dispatch('refresh')->assertDontSee('This is message');
    });

    test('it will delete actual message but still show parent message when deleted ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver, 'This is message');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // send reply
        $request->call('setReply', encrypt(1))->set('body', 'This is reply')->call('sendMessage');

        // assert messsage visible
        $request->assertSee('This is reply');

        // call deleteForMe
        $request->call('deleteForEveryone', encrypt('1'));

        // now assert still see 'This is message' message
        $request->assertSee('This is message');
    });

    test('it broadcasts event "NotifyParticipant" when sendLike is called', function () {
        Event::fake();
        // Queue::fake();

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt($authMessage->id));

        Event::assertDispatched(MessageDeleted::class, function ($event) use ($authMessage) {
            return $event->message->id === $authMessage->id;
        });
    });

    test('other participant can refresh after delete for everyone without hitting a missing-model hydration error', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $deletedMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        $receiverChat = Livewire::actingAs($receiver)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->assertSee('message-2');

        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForEveryone', encrypt($deletedMessage->id));

        $receiverChat->call('$refresh')
            ->assertStatus(200)
            ->assertDontSee('message-2');
    });
});

describe('deletForMe', function () {

    test('it throws DecryptException if message id is not Encrypted  ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage = $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', $otherUserMessage->id)
            ->assertStatus(500);

    })->throws(DecryptException::class);

    test('it doesnt throws DecryptException if message id is Encrypted  ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage = $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($otherUserMessage->id))
            ->assertStatus(200);

    });

    test('user can delete-for-me message that does not belong to them ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        // auth -> receiver
        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $otherUserMessage = $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($otherUserMessage->id))
            ->assertStatus(200);

        $messageAvailable = Message::find($otherUserMessage->id);

        // /assert message no longer visible
        expect($messageAvailable)->toBe(null);
    });

    test('deleted message is removed from blade', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver);

        // auth -> receiver
        $auth->sendMessageTo($receiver, message: 'message-1')->conversation;
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // assert count 4
        $request->assertSet('loadedMessages', function ($messages) {
            return collect($messages)->flatten(1)->count() == 4;
        });

        // call deleteForMe
        $request->call('deleteForMe', encrypt($authMessage->id));

        // assert count no 3
        $request->assertSet('loadedMessages', function ($messages) {
            return collect($messages)->flatten(1)->count() == 3;
        });
    });

    test('deleted message is not removed database', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->sendMessageTo($receiver, message: 'message-1')->conversation;

        // dd($conversation);
        $authMessage = $auth->sendMessageTo($receiver, message: 'message-2');

        // receiver -> auth
        $receiver->sendMessageTo($auth, message: 'message-3');
        $receiver->sendMessageTo($auth, message: 'message-4');

        // run
        Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id])
            ->call('deleteForMe', encrypt($authMessage->id));

        $messageAvailable = Message::withoutGlobalScopes()->find($authMessage->id);

        // /assert message no longer visible
        expect($messageAvailable)->not->toBe(null);
    });

    test('it disptaches refresh event and removes deleted message from chatlist', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver, 'This is message');

        $CHATLIST = Livewire::actingAs($auth)->test(Chatlist::class)->assertSee('This is message');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // call deleteForMe
        $request->call('deleteForMe', encrypt('1'))
            ->assertDispatched('refresh');

        // assert
        $CHATLIST->dispatch('refresh')->assertDontSee('This is message');
    });

    test('it will delete actual message but still show parent message when deleted ', function () {

        $auth = User::factory()->create();
        $receiver = User::factory()->create(['name' => 'John']);

        $conversation = $auth->createConversationWith($receiver, 'This is message');

        $request = Livewire::actingAs($auth)->test(ChatBox::class, ['conversation' => $conversation->id]);

        // send reply
        $request->call('setReply', encrypt(1))->set('body', 'This is reply')->call('sendMessage');

        // assert messsage visible
        $request->assertSee('This is reply');

        // call deleteForMe
        $request->call('deleteForMe', encrypt('1'))->assertDispatched('refresh');

        // now assert still see 'This is message' message
        $request->assertSee('This is message');
    });
});
