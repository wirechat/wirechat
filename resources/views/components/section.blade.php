@props([
    'title' => null,
    'description' => null,
    'titleClass' => 'text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400',
    'descriptionClass' => 'mt-1 text-sm text-gray-500 dark:text-gray-400',
])

<section {{ $attributes->class(['space-y-4 rounded-2xl border p-3']) }}>
    @if (filled($title) || filled($description) || isset($actions))
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                @if (filled($title))
                    <p class="{{ $titleClass }}">{{ $title }}</p>
                @endif

                @if (filled($description))
                    <p class="{{ $descriptionClass }}">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div>
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>
