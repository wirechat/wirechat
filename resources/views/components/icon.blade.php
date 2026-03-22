@props([
    'icon' => null,
    'default' => null,
    'iconAttributes' => [], // array|ComponentAttributeBag
])

@php
    use Illuminate\View\ComponentAttributeBag;
    use Illuminate\Contracts\Support\Htmlable;

    // Ensure we always have a bag
    $iconBag = $iconAttributes instanceof ComponentAttributeBag
        ? $iconAttributes
        : new ComponentAttributeBag(is_array($iconAttributes) ? $iconAttributes : []);

    // Merge: caller attrs + icon attrs + defaults
    $final = $attributes
        ->merge($iconBag->getAttributes())
        ->merge(['class' => 'size-6 text-zinc-700 dark:text-gray-100 dark:hover:text-gray-200']);
@endphp

@if ($icon instanceof Htmlable)
    {{ $icon }}
@elseif (is_string($icon) && $icon !== '')
    <x-dynamic-component :component="$icon" {{ $final }} />
@elseif ($default instanceof Htmlable)
    {{ $default }}
@elseif (is_string($default) && $default !== '')
    <x-dynamic-component :component="$default" {{ $final }} />
@endif