@props([
    'title' => null,
    'description' => null,
    'titleClass' => 'text-black dark:text-zinc-400',
    'descriptionClass' => 'mt-1 mr-auto text-sm text-zinc-500 dark:text-zinc-400',
])

<section {{ $attributes->class(['space-y-4 rounded-xl border dark:border-zinc-700 p-3']) }}>
    @if (filled($title) || filled($description) || isset($actions))
        <div class="flex text-start flex-wrap items-center justify-between gap-3">
            <div class="mr-auto flex gap-2 flex-col">
                @if (filled($title))
                    <p  class="{{ $titleClass }}">{{ $title }}</p>
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
