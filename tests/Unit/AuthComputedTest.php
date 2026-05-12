<?php

use Livewire\Attributes\Computed;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Chats\Requests;

test('chat components do not persist the authenticated user computed value', function () {
    $chatAuthComputed = (new ReflectionMethod(Chat::class, 'auth'))
        ->getAttributes(Computed::class)[0]
        ->newInstance();

    $chatsAuthComputed = (new ReflectionMethod(Chats::class, 'auth'))
        ->getAttributes(Computed::class)[0]
        ->newInstance();

    $requestsAuthComputed = (new ReflectionMethod(Requests::class, 'auth'))
        ->getAttributes(Computed::class)[0]
        ->newInstance();

    expect($chatAuthComputed->persist)->toBeFalse()
        ->and($chatsAuthComputed->persist)->toBeFalse()
        ->and($requestsAuthComputed->persist)->toBeFalse();
});
