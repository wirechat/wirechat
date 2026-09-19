<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Wirechat\Wirechat\Events\MessageCreated;
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

test('sendMessageTo broadcasts the message like the chat component does', function () {
    // The Livewire component broadcasts after creating a message, but sendMessageTo() did not.
    // Anything sent from application code — a greeting, a bot, a job — was stored and never
    // reached the other side until the page was reloaded, with nothing logged to show for it.
    Event::fake([MessageCreated::class]);

    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $conversation = $sender->createConversationWith($recipient);
    $message = $sender->sendMessageTo($conversation, 'Hello there');

    Event::assertDispatched(
        MessageCreated::class,
        fn (MessageCreated $event) => $event->message->is($message)
    );
});
