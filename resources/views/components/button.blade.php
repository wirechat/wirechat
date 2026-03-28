@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'fullWidth' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center rounded-lg font-medium transition focus:outline-hidden';

    $variantClasses = [
        'primary' => 'bg-[var(--wc-brand-primary)] text-white hover:opacity-90',
        'secondary' => 'border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-transparent text-gray-900 dark:text-white hover:bg-[var(--wc-light-secondary)]/60 dark:hover:bg-[var(--wc-dark-secondary)]/60',
        'link' => 'rounded-none p-0 font-medium text-[var(--wc-brand-primary)] hover:underline',
    ];

    $sizeClasses = [
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-4 py-3 text-sm',
        'link' => 'text-sm',
    ];

    $classes = $variant === 'link'
        ? trim('inline-flex items-center '.$sizeClasses['link'].' '.$variantClasses[$variant].' '.($fullWidth ? 'w-full justify-center' : ''))
        : trim($baseClasses.' '.($sizeClasses[$size] ?? $sizeClasses['md']).' '.$variantClasses[$variant].' '.($fullWidth ? 'w-full' : ''));
@endphp

<button type="{{ $type }}" {{ $attributes->class([$classes]) }}>
    {{ $slot }}
</button>
