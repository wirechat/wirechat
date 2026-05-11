@props([
    'component',
    'widget' => false,
    'panel' => null,
])

<div {{ $attributes }} onclick="Livewire.dispatch('openChatListDrawer', {
        component: '{{ $component }}',
        arguments: {
            widget: @js($widget),
            panel: @js($panel)
        }
    })">
    {{ $slot }}
</div>
