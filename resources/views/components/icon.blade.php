@props([
    'icon' => null,
    'default' => null,
    'class' => null,
    'iconAttributes' => [], // array|ComponentAttributeBag
])

@php
    use Illuminate\Contracts\Support\Htmlable;
    use Illuminate\View\ComponentAttributeBag;

    // Ensure we always have a bag
    $iconBag = $iconAttributes instanceof ComponentAttributeBag
        ? $iconAttributes
        : new ComponentAttributeBag(is_array($iconAttributes) ? $iconAttributes : []);

    $iconClasses = filled($class)
        ? $class
        : 'size-6 text-zinc-700 dark:text-gray-100 dark:hover:text-gray-200';
@endphp

@if ($icon instanceof Htmlable)
    {{ $icon }}
@elseif (is_string($icon) && $icon !== '')
    <x-dynamic-component :component="$icon" {{ $attributes
        ->merge($iconBag->getAttributes())
        ->merge(['class' => $iconClasses]) }} />
@elseif ($default instanceof Htmlable)
    {{ $default }}
@elseif (is_string($default) && $default !== '')
    <x-dynamic-component :component="$default" {{ $attributes
        ->merge($iconBag->getAttributes())
        ->merge(['class' => $iconClasses]) }} />
@endif
