@props([
    'component',
    'conversation' => null,
    'widget' => false,
    'panel' => null,
    'arguments' => [],
])

@php
    $modalArguments = array_merge([
        'conversation' => $conversation,
        'widget' => $widget,
        'panel' => $panel,
    ], $arguments);
@endphp

<div
    {{ $attributes }}
    x-data
    x-on:click="Livewire.dispatch('openWirechatModal', {
        component: @js($component),
        arguments: @js($modalArguments)
    })"
>
    {{ $slot }}
</div>
