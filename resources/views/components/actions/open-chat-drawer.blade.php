@props([
    'component', 
    'conversation' => null,
    'widget' => false,
    'panel' => null
])

<div {{ $attributes }}  onclick="Livewire.dispatch('openChatDrawer', { 
        component: '{{ $component }}', 
        arguments: { 
             conversation: @js($conversation),
            widget: @js($widget),
            panel: @js($panel)
        } 
    })">

    {{ $slot }}
</div>
