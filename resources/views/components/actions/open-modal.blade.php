@props([
    'component',
    'conversation' => null,
    'widget' => false,
    'panel'=>null,
])

<div x-data x-on:click="Livewire.dispatch('openWirechatModal', {
        component: @js($component),
        arguments: {
            conversation: @js($conversation),
            widget:@js($widget),
            panel:@js($panel)
        }
    })">

    {{ $slot }}
</div>
