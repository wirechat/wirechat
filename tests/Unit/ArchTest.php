<?php

arch('app')
    ->expect('Wirechat\Wirechat')
    ->not->toUse(['die', 'dd', 'dump']);

arch('Traits test ')
    ->expect('Wirechat\Wirechat\Traits')
    ->toBeTraits();

arch('Make sure Actor is only used in InteractsWithWirechat Trait')
    ->expect('Wirechat\Wirechat\Traits\Actor')
    ->toOnlyBeUsedIn('Wirechat\Wirechat\Traits\InteractsWithWirechat');

arch('Make sure Actionable is used in Conversation Model')
    ->expect('Wirechat\\Wirechat\\Traits\\Actionable')
    ->toBeUsedIn('Wirechat\Wirechat\Models\Conversation');

arch('Ensure Widget Trait is used in Components')
    ->expect('Wirechat\\Wirechat\\Livewire\\Concerns\Widget')
    ->toBeUsedIn([
        'Wirechat\Wirechat\Livewire\Chat\Chat',
        'Wirechat\Wirechat\Livewire\Chats\Chats',
        'Wirechat\Wirechat\Livewire\New\Chat',
        'Wirechat\Wirechat\Livewire\New\Group',
        // 'Wirechat\Wirechat\Livewire\Chat\Group\AddMembers',
        'Wirechat\Wirechat\Livewire\Chat\Info',
        'Wirechat\Wirechat\Livewire\Chat\Group\Members',
    ]);
