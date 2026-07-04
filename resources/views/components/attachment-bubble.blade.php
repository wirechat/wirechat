@props([
    'previousMessage' => null,
    'message' => null,
    'nextMessage' => null,
    'belongsToAuth' => false,
    'hasSolidColorTone' => false,
])

@php
    $isSameAsNext = $nextMessage
        && $message?->sendable_id === $nextMessage?->sendable_id
        && $message?->sendable_type === $nextMessage?->sendable_type;
    $isNotSameAsNext = ! $isSameAsNext;
    $isSameAsPrevious = $previousMessage
        && $message?->sendable_id === $previousMessage?->sendable_id
        && $message?->sendable_type === $previousMessage?->sendable_type;
    $isNotSameAsPrevious = ! $isSameAsPrevious;
    $hasCaption = trim((string) ($message?->body ?? '')) !== '';
@endphp

<div
    dusk="message-attachment-shell"
    @class([
        'inline-flex max-w-full flex-col overflow-hidden',
        'rounded-lg p-1',
        'wc-primary-tone-bg' => $belongsToAuth,
        'bg-zinc-200/70 dark:bg-zinc-700/70' => ! $belongsToAuth,

    ])
>
    {{ $slot }}

    @unless ($hasCaption)
        <span
            dusk="message-attachment-time"
            @class([
                'block px-1.5 my-2 text-right text-[11px] leading-none',
                'text-white/90' => $belongsToAuth && $hasSolidColorTone,
                'text-zinc-800 dark:text-white/90' => $belongsToAuth && ! $hasSolidColorTone,
                'text-zinc-600 dark:text-zinc-300' => ! $belongsToAuth,
            ])
        >
            {{ $message?->created_at?->format('H:i') }}
        </span>
    @endunless
</div>
