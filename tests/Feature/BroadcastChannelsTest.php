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

test('channels are registered even when the application has cached its routes', function () {
    // Broadcast channels are not part of the route cache, so a cached application still has
    // to register them. loadRoutesFrom() skips its file in that state, which left every
    // deployment running route:cache — the common case in production — without a single
    // chat channel: /broadcasting/auth answered 403 and nothing arrived live.
    $broadcaster = Broadcast::driver();

    // getChannels() hands back a copy, so the registry is emptied through the property the
    // broadcaster actually reads. Without this the assertion passes on channels that the
    // ordinary, uncached boot had already registered.
    $registered = (new ReflectionClass($broadcaster))
        ->getParentClass()
        ->getProperty('channels');
    $registered->setValue($broadcaster, []);

    app()->instance('routes.cached', true);

    (new Wirechat\Wirechat\WirechatServiceProvider(app()))->boot();

    expect($broadcaster->getChannels())
        ->toHaveKey('test.conversation.{conversationId}')
        ->toHaveKey('test.participant.{encodedType}.{id}');
});
