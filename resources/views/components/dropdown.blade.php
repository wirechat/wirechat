@props(['align' => 'right', 'width' => '48', 'contentClasses' => ''])

@php
switch ($align) {
    case 'left':
        $alignmentClasses = 'ltr:origin-top-left rtl:origin-top-right start-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-top';
        break;
    case 'right':
    default:
        $alignmentClasses = 'ltr:origin-top-right rtl:origin-top-left end-0';
        break;
}

switch ($width) {
    case '48':
        $width = 'w-48';
        break;
}
@endphp

<div x-ref="button" class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-anchor="$refs.button"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"

            {{-- class="absolute z-50 mt-2 shadow-lg {{ $alignmentClasses }}" --}}
            {{$attributes->merge(['class'=>"rounded-lg absolute z-50 mt-2 shadow-lg w-48 bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] rounded-md border border-[var(--wc-light-secondary)] dark:border-[var(--wc-dark-secondary)] shadow-sm overflow-hidden"])}}
            style="display: none;"
            @click="open = false">
        <div>
            {{ $content }}
        </div>
    </div>
</div>
