<?php

use Illuminate\Support\Facades\Broadcast;
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
