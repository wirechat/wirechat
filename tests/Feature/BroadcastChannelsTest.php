<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Wirechat\Wirechat\Events\MessageCreated;
use Wirechat\Wirechat\Events\NotifyParticipant;
use Wirechat\Wirechat\Jobs\NotifyParticipants;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelRegistry;
use Workbench\App\Models\User;

test('conversation channel authorization uses the channel panel settings', function () {
    testPanelProvider()->messageRequests();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = $sender->sendMessageRequestTo($recipient);

    $registry = app(PanelRegistry::class);
    $registry->register(
        Panel::make()
            ->id('admin')
            ->path('admin')
            ->messageRequests(false)
    );
    $registry->setCurrent('admin');

    $callback = Broadcast::driver()
        ->getChannels()
        ->get('test.conversation.{conversationId}');

    expect(is_callable($callback))->toBeTrue();

    /** @var callable $callback */
    expect($callback($recipient, $conversation->getKey()))->toBeTrue()
        ->and($registry->getCurrent()?->getId())->toBe('test');
});

test('sendMessageTo does not broadcast by default', function () {
    Event::fake([MessageCreated::class, NotifyParticipant::class]);
    Queue::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = $sender->createConversationWith($recipient);
    $sender->sendMessageTo($conversation, 'Hello there');

    Event::assertNotDispatched(MessageCreated::class);
    Event::assertNotDispatched(NotifyParticipant::class);
    Queue::assertNotPushed(NotifyParticipants::class);
});

test('sendMessageTo broadcasts the conversation message and notifies private participants when requested', function () {
    Event::fake([MessageCreated::class, NotifyParticipant::class]);
    Queue::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = $sender->createConversationWith($recipient);
    $message = $sender->sendMessageTo($conversation, 'Hello there', broadcast: true);

    Event::assertDispatched(MessageCreated::class, fn (MessageCreated $event) => $event->message->is($message));
    Event::assertNotDispatched(NotifyParticipant::class);
    Queue::assertPushed(
        NotifyParticipants::class,
        fn (NotifyParticipants $job) => $job->conversation->is($conversation) && $job->message->is($message)
    );
});

test('sendMessageTo notifies group participants when broadcasting is requested', function () {
    Event::fake([MessageCreated::class, NotifyParticipant::class]);
    Queue::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = $sender->createGroup(name: 'Support', description: 'Support room');
    $conversation->addParticipant($recipient);
    $message = $sender->sendMessageTo($conversation, 'Hello group', broadcast: true);

    Event::assertDispatched(MessageCreated::class, fn (MessageCreated $event) => $event->message->is($message));
    Queue::assertPushed(
        NotifyParticipants::class,
        fn (NotifyParticipants $job) => $job->conversation->is($conversation) && $job->message->is($message)
    );
});

test('sendMessageTo directly notifies pending message request recipients when broadcasting is requested', function () {
    Event::fake([MessageCreated::class, NotifyParticipant::class]);
    Queue::fake();

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = $sender->sendMessageRequestTo($recipient);
    $message = $sender->sendMessageTo($conversation, 'Hello request', broadcast: true);

    Event::assertDispatched(MessageCreated::class, fn (MessageCreated $event) => $event->message->is($message));
    Event::assertDispatched(
        NotifyParticipant::class,
        fn (NotifyParticipant $event) => (string) $event->participantId === (string) $recipient->getKey()
    );
    Queue::assertNotPushed(NotifyParticipants::class);
});

test('sendMessageTo does not broadcast self conversations when requested', function () {
    Event::fake([MessageCreated::class, NotifyParticipant::class]);
    Queue::fake();

    $sender = User::factory()->create();

    $conversation = $sender->createConversationWith($sender);
    $sender->sendMessageTo($conversation, 'Note to self', broadcast: true);

    Event::assertNotDispatched(MessageCreated::class);
    Event::assertNotDispatched(NotifyParticipant::class);
    Queue::assertNotPushed(NotifyParticipants::class);
});
