@props([
    'label' => null,
])

<div
    {{
        $attributes->class('grid grid-cols-2 gap-2 rounded-2xl bg-zinc-100 p-1 dark:bg-zinc-800')
    }}
    role="tablist"
    @if (filled($label))
        aria-label="{{ $label }}"
    @endif
>
    {{ $slot }}
</div>
