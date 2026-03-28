@props([
    'title' => null,
    'description' => null,
    'titleClass' => 'font-medium  text-gray-900 dark:text-gray-400',
    'descriptionClass' => 'mt-1 mr-auto text-sm text-gray-500 dark:text-gray-400',
])

<section {{ $attributes->class(['space-y-4 rounded-2xl border p-3']) }}>
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
