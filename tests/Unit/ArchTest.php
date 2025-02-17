<?php

arch('app')
    ->expect('Namu\WireChat')
    ->not->toUse(['die', 'dd', 'dump']);

arch('Traits test ')
    ->expect('Namu\WireChat\Traits')
    ->toBeTraits();

arch('Make sure Actor is only used in Chatable Trait')
    ->expect('Namu\WireChat\Traits\Actor')
    ->toOnlyBeUsedIn('Namu\WireChat\Traits\Chatable');

arch('Make sure Actionable is used in Conversation Model')
    ->expect('Namu\\WireChat\\Traits\\Actionable')
    ->toBeUsedIn('Namu\WireChat\Models\Conversation');

arch('Ensure Widget Trait is used in Components')
    ->expect('Namu\\WireChat\\Livewire\\Concerns\Widget')
    ->toBeUsedIn([
        'Namu\WireChat\Livewire\Chat\Chat',
        'Namu\WireChat\Livewire\Chats\Chats',
        'Namu\WireChat\Livewire\New\Chat',
        'Namu\WireChat\Livewire\New\Group',
        // 'Namu\WireChat\Livewire\Chat\Group\AddMembers',
        'Namu\WireChat\Livewire\Chat\Info',
        'Namu\WireChat\Livewire\Chat\Group\Members',
    ]);
