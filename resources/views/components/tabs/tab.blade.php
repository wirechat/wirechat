@props([
    'active' => false,
    'badge' => null,
])

<button
    type="button"
    {{
        $attributes->class([
            'rounded-lg px-3 py-2 text-sm font-medium transition',
            'bg-white text-zinc-900  dark:bg-zinc-800 dark:text-zinc-200' => $active,
            'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ! $active,
        ])
    }}
    aria-pressed="{{ $active ? 'true' : 'false' }}"
>
    <span>{{ $slot }}</span>

    @if (! is_null($badge))
        <span class="ml-2 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-200 px-1.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-200">
            {{ $badge }}
        </span>
    @endif
</button>
