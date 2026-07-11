@props([
    'component',
    'widget' => false,
    'panel' => null,
])

<div {{ $attributes }} x-data x-on:click="Livewire.dispatch('openChatListDrawer', {
        component: @js($component),
        arguments: {
            widget: @js($widget),
            panel: @js($panel)
        }
    })">
    {{ $slot }}
</div>
