@props([
    'type' => 'button',
    'variant' => 'default',
    'size' => 'base',
    'outline' => true,
    'fullWidth' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-1 transition-all rounded-lg font-medium transition focus:outline-hidden  disabled:cursor-not-allowed disabled:opacity-60';

    $variantClasses = [
        'primary' => 'bg-[var(--primary-500)] text-white hover:opacity-90',
        'secondary' => 'text-gray-900 border border-zinc-200 dark:border-zinc-600/70 dark:text-white bg-white dark:bg-zinc-800/80 hover:bg-zinc-100/60 dark:hover:bg-zinc-800/90',
        'default' => 'text-gray-900 border  border-zinc-200 dark:border-zinc-600/70 dark:text-white shadow-xs dark:shadow-none dark:bg-zinc-800/80  hover:bg-zinc-100/60 dark:hover:bg-zinc-800/90',
        'filled' => 'text-gray-900   dark:text-white bg-zinc-200/60 dark:bg-zinc-800/60   hover:bg-zinc-100/60  dark:hover:bg-zinc-800/90',
        'outline' => 'border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-transparent text-gray-900 dark:text-white hover:bg-[var(--wc-light-secondary)]/60 dark:hover:bg-[var(--wc-dark-secondary)]/60',
        'link' => 'rounded-none p-0 font-medium text-[var(--wc-brand-primary)] hover:underline',
        'subtle' => ' bg-transparent text-gray-500 dark:text-gray-400 hover:bg-zinc-300/60 dark:hover:bg-zinc-800/60',
    ];

    $sizeClasses = [
        'sm' => 'px-4 py-2  text-sm',
        'base' => 'px-4 py-2.5 text-sm',
        'md' => 'px-4 py-3 text-sm',
        'link' => 'text-sm',
    ];

    $resolvedVariant = $variantClasses[$variant] ?? $variantClasses['default'];
    $classes = $variant === 'link'
        ? trim('inline-flex items-center '.$sizeClasses['link'].' '.$resolvedVariant.' '.($fullWidth ? 'w-full justify-center' : ''))
        : trim($baseClasses.' '.($sizeClasses[$size] ?? $sizeClasses['md']).' '.$resolvedVariant.' '.($fullWidth ? 'w-full' : ''));
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
