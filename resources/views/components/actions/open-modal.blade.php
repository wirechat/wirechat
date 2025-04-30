@props([
    'component', 
    'conversation' => null,
    'widget' => false
])

<div  onclick="Livewire.dispatch('openWireChatModal', { 
        component: '{{ $component }}', 
        arguments: { 
            conversation:`{{$conversation ?? null }}`, 
            widget: @js($widget)
        } 
    })">

    {{ $slot }}
</div>
