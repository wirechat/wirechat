@use('Wirechat\Wirechat\Facades\Wirechat')


@php

   $isSameAsNext = (bool) ($nextMessage && $message?->sendable?->is($nextMessage?->sendable));
   $isNotSameAsNext = !$isSameAsNext;
   $isSameAsPrevious = (bool) ($previousMessage && $message?->sendable?->is($previousMessage?->sendable));
   $isNotSameAsPrevious = !$isSameAsPrevious;
   $groupInvitePreview = $message?->groupInvitePreview($this->panel());
   $inviteToken = $groupInvitePreview['token'] ?? null;
   $encryptedInviteLink = $inviteToken !== null ? encrypt($inviteToken) : null;
   $canParseMessageUrls = $this->panel()->canParseMessageUrls();
   $body = (string) ($message?->body ?? '');
   $segments = ($canParseMessageUrls && Wirechat::containsLink($body))
        ? Wirechat::linkifyMessage($body)
        : [[
            'text' => $body,
            'href' => null,
            'is_link' => false,
        ]];
   $hasVisibleSenderName = ! $belongsToAuth && $isGroup && $isNotSameAsPrevious;
   $hasSolidColorTone = $this->panel()->hasSolidColorTone();
   $messageTextClasses = 'whitespace-pre-wrap tracking-normal wrap-anywhere font-normal text-base dark:text-white lg:tracking-normal';
@endphp

<div

@class([
    'flex flex-wrap shadow-xs max-w-fit text-[15px] border border-gray-200/40 dark:border-none rounded-xl p-2.5 flex flex-col',
    'wc-primary-tone-bg' => $belongsToAuth,
    'font-normal' => $belongsToAuth,
    'font-[420] text-black bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] dark:text-white' => !$belongsToAuth,

    // Message styles based on position and ownership

    // RIGHT
    // First message on RIGHT
    'rounded-br-md rounded-tr-2xl' => ($isSameAsNext && $isNotSameAsPrevious && $belongsToAuth),

    // Middle message on RIGHT
    'rounded-r-md' => ($isSameAsPrevious && $belongsToAuth),

    // Standalone message RIGHT
    'rounded-br-xl rounded-r-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && $belongsToAuth),

    // Last Message on RIGHT
    'rounded-br-2xl' => ($isNotSameAsNext && $belongsToAuth),

    // LEFT
    // First message on LEFT
    'rounded-bl-md rounded-tl-2xl' => ($isSameAsNext && $isNotSameAsPrevious && !$belongsToAuth),

    // Middle message on LEFT
    'rounded-l-md' => ($isSameAsPrevious && !$belongsToAuth),

    // Standalone message LEFT
    'rounded-bl-xl rounded-l-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && !$belongsToAuth),

    // Last message on LEFT
    'rounded-bl-2xl' => ($isNotSameAsNext && !$belongsToAuth),
])
>
@if (!$belongsToAuth && $isGroup)
<div
    dusk="message-sender-name"
    @class([
        'shrink-0 text-xs font-normal leading-none',
        'text-[var(--wc-brand-primary)] dark:text-[var(--primary-400)]',
        // Hide sender name if the previous message is from the same user
        'hidden' => $isSameAsPrevious
    ])>
    {{ $message?->user?->wirechat_name ?? __('wirechat::chat.labels.user') }}
</div>
@endif

@if ($groupInvitePreview)
    @include('wirechat::livewire.chat.partials.group-invite', [
        'preview' => $groupInvitePreview,
        'belongsToAuth' => $belongsToAuth,
        'hasVisibleSenderName' => $hasVisibleSenderName,
        'hasSolidColorTone' => $hasSolidColorTone,
    ])
@endif

<pre
    dusk="message-text"
    class="{{ $messageTextClasses }}"
    style="font-family: inherit;">@foreach ($segments as $segment)@if ($segment['is_link'])@php
                    $isInviteLink = $message?->isGroupInviteLink((string) $segment['href'], $this->panel()) === true;
                    $encryptedSegmentInviteLink = $isInviteLink ? encrypt($segment['href']) : null;
                @endphp@if ($isInviteLink)<button
                dusk="message-invite-action"
                type="button"
                data-invite-link="true"
                wire:click="handleOpenChat(@js($encryptedSegmentInviteLink))"
                @class([
                    'inline cursor-pointer appearance-none border-0 bg-transparent p-0 text-left font-[inherit] underline tracking-normal wrap-anywhere text-base lg:tracking-normal',
                    'text-white/90' => $belongsToAuth && $hasSolidColorTone,
                    'dark:text-white' => ! ($belongsToAuth && $hasSolidColorTone),
                ])>{{ $segment['text'] }}</button>@else<a
                dusk="message-link"
                target="_blank"
                rel="noopener noreferrer"
                @class([
                    'underline tracking-normal wrap-anywhere text-base lg:tracking-normal',
                    'text-white/90' => $belongsToAuth && $hasSolidColorTone,
                    'dark:text-white' => ! ($belongsToAuth && $hasSolidColorTone),
                ])
                href="{{ $segment['href'] }}">{{ $segment['text'] }}</a>@endif@else{{ $segment['text'] }}@endif@endforeach</pre>

{{-- Display the created time based on different conditions --}}
<span
@class([
    'ml-auto text-[11px]',
    'text-gray-700 dark:text-gray-300' => !$belongsToAuth,
    'text-white/90' => $belongsToAuth && $hasSolidColorTone,
    'text-zinc-700 dark:text-white/90' => $belongsToAuth && ! $hasSolidColorTone,
])>
    @php
        // If the message was created today, show only the time (e.g., 1:00 AM)
        echo $message?->created_at?->format('H:i');
    @endphp
</span>

@if ($groupInvitePreview)
    <button
        type="button"
        dusk="group-invite-action"
        wire:click="handleOpenChat(@js($encryptedInviteLink))"
        data-invite-link="true"
        @class([
            'mt-2 -mx-2.5  block border-t px-4 py-2 text-center text-sm font-semibold transition hover:opacity-95',
            'border-white/20 text-white/90' => $belongsToAuth && $hasSolidColorTone,
            'border-zinc-900/10 text-zinc-800 dark:border-white/20 dark:text-white/90' => $belongsToAuth && ! $hasSolidColorTone,
            'border-[var(--wc-light-border)] text-[var(--primary-500)] dark:border-[var(--wc-dark-border)] dark:text-[var(--primary-300)]' => ! $belongsToAuth,
        ])>
        {{ __('wirechat::chat.group.invite_message.actions.view_group.label') }}
    </button>
@endif
</div>
