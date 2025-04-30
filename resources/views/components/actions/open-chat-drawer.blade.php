@props([
    'component', 
    'conversation' => null,
    'widget' => false
])

<div {{ $attributes }}  onclick="Livewire.dispatch('openChatDrawer', { 
        component: '{{ $component }}', 
        arguments: { 
            conversation: `{{$conversation ?? null }}`, 
            widget: @js($widget)
        } 
    })">

    {{ $slot }}
</div>
