@props([
    'label' => null,
])

<div
    {{
        $attributes->class('grid grid-cols-2 gap-2 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-900')
    }}
    role="tablist"
    @if (filled($label))
        aria-label="{{ $label }}"
    @endif
>
    {{ $slot }}
</div>
