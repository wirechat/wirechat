<?php

arch('app')
    ->expect('Wirechat\Wirechat')
    ->not->toUse(['die', 'dd', 'dump']);

arch('Traits test ')
    ->expect('Wirechat\Wirechat\Traits')
    ->toBeTraits();

arch('Make sure Actor is only used in Participant Model')
    ->expect('Wirechat\Wirechat\Traits\Actor')
    ->toOnlyBeUsedIn('Wirechat\Wirechat\Models\Participant');

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
        // 'Wirechat\Wirechat\Livewire\Chat\Group\Members\AddMembers',
        'Wirechat\Wirechat\Livewire\Chat\Info',
        'Wirechat\Wirechat\Livewire\Chat\Group\Members\Members',
    ]);

it('js encodes dynamic blade action arguments', function () {
    $viewsPath = dirname(__DIR__, 2).'/resources/views';
    $patterns = [
        'raw Blade expression in wire:click arguments' => '/wire:click(?:\.\w+)*="[^"]*\{\{/',
        'raw Blade expression in Alpine or DOM event arguments' => '/(?:@click|@change|x-on:[^=]+|onclick)="[^"]*\{\{/',
        'manual json_encode in Blade action arguments' => '/(?:@click|@change|x-on:[^=]+|onclick|wire:click(?:\.\w+)*)="[^"]*json_encode\(/',
        'PHP-built JavaScript action snippets' => '/\$[A-Za-z0-9_]+Action\s*=/',
    ];
    $violations = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewsPath, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($file->getPathname());
        // Blade compiles attributes on x-components before rendering them.
        $contents = preg_replace('/<x-[^>]*>/s', '', $contents) ?? $contents;

        foreach ($patterns as $description => $pattern) {
            if (preg_match($pattern, $contents) === 1) {
                $violations[] = str_replace($viewsPath.'/', '', $file->getPathname()).': '.$description;
            }
        }
    }

    expect($violations)->toBeEmpty();
});
