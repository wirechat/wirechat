@use('Wirechat\Wirechat\Facades\Wirechat')


@php

   $isSameAsNext = ($message?->sendable_id === $nextMessage?->sendable_id) && ($message?->sendable_type === $nextMessage?->sendable_type);
   $isNotSameAsNext = !$isSameAsNext;
   $isSameAsPrevious = ($message?->sendable_id === $previousMessage?->sendable_id) && ($message?->sendable_type === $previousMessage?->sendable_type);
   $isNotSameAsPrevious = !$isSameAsPrevious;
   $groupInvitePreview = $message?->groupInvitePreview($this->panel());
   $inviteUrl = $groupInvitePreview['url'] ?? null;
   $canParseMessageUrls = $this->panel()->canParseMessageUrls();
   $body = (string) ($message?->body ?? '');
   $segments = ($canParseMessageUrls && $message?->isLink())
        ? Wirechat::linkifyMessage($body)
        : [[
            'text' => $body,
            'href' => null,
            'is_link' => false,
        ]];
   $messageTextClasses = 'whitespace-pre-wrap tracking-normal break-all text-sm md:text-base dark:text-white lg:tracking-normal';
@endphp

<div


{{-- We use style here to make it easy for dynamic and safe injection --}}
{{-- @style([
'background-color:var(--wc-brand-primary)' => $belongsToAuth==true
]) --}}

@class([
    'flex flex-wrap max-w-fit text-[15px] border border-gray-200/40 dark:border-none rounded-xl p-2.5 flex flex-col text-black bg-[#f6f6f8fb]',
    'text-white  bg-primary-500 opacity-90' => $belongsToAuth, // Background color for messages sent by the authenticated user
    'bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] dark:text-white' => !$belongsToAuth,

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
    @class([
        'shrink-0 font-medium text-purple-500',
        // Hide avatar if the next message is from the same user
        'hidden' => $isSameAsPrevious
    ])>
    {{ $message?->sendable?->wirechat_name }}
</div>
@endif

@if ($groupInvitePreview)
    @include('wirechat::livewire.chat.partials.group-invite', [
        'preview' => $groupInvitePreview,
        'belongsToAuth' => $belongsToAuth,
    ])
@endif

<pre
    dusk="message-text"
    class="{{ $messageTextClasses }}"
    style="font-family: inherit;">@foreach ($segments as $segment)@if ($segment['is_link'])@php $isInviteLink = $inviteUrl !== null && $segment['href'] === $inviteUrl; @endphp<a
                dusk="message-link"
                @if ($isInviteLink) data-invite-link="true" @else target="_blank" rel="noopener noreferrer" @endif
                class="underline tracking-normal break-all text-sm md:text-base dark:text-white lg:tracking-normal"
                href="{{ $segment['href'] }}">{{ $segment['text'] }}</a>@else{{ $segment['text'] }}@endif@endforeach</pre>

{{-- Display the created time based on different conditions --}}
<span
@class(['text-[11px] ml-auto ',  'text-gray-700 dark:text-gray-300' => !$belongsToAuth,'text-gray-100' => $belongsToAuth])>
    @php
        // If the message was created today, show only the time (e.g., 1:00 AM)
        echo $message?->created_at->format('H:i');
    @endphp
</span>

@if ($groupInvitePreview)
    <a  href="{{ $groupInvitePreview['url'] }}"
        @class([
            'mt-2 -mx-2.5  block border-t px-4 py-2 text-center text-sm font-semibold transition hover:opacity-95',
            'border-white/20 text-white/90' => $belongsToAuth,
            'border-[var(--wc-light-border)] text-primary-500 dark:border-[var(--wc-dark-border)] dark:text-primary-300' => ! $belongsToAuth,
        ])>
        {{ __('wirechat::chat.group.invite_message.actions.view_group.label') }}
    </a>
@endif
</div>
