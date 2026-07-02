@props([
    'component', 
    'conversation' => null,
    'widget' => false,
    'panel' => null
])

<div {{ $attributes }} x-data x-on:click="Livewire.dispatch('openChatDrawer', {
        component: @js($component),
        arguments: {
             conversation: @js($conversation),
            widget: @js($widget),
            panel: @js($panel)
        }
    })">

    {{ $slot }}
</div>
