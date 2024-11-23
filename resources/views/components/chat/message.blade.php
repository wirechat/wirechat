@use('Namu\WireChat\Facades\WireChat')

@props([
    'previousMessage' => $previousMessage,
    'message' => $message,
    'nextMessage' => $nextMessage,
    'belongsToAuth' => $belongsToAuth,
    'primaryColor'=> WireChat::getColor(),
    'isGroup'=>false

])

<div


{{-- We use style here to make it easy for dynamic and safe injection --}}
@style([
'background-color:'. $primaryColor .'' => $belongsToAuth==true
])

@php
    $isSameAsNext = $message?->sendable?->is($nextMessage?->sendable);
    $isSameAsPrevious = $message?->sendable?->is($previousMessage?->sendable);
    $isNotSameAsNext = $message?->sendable?->isNot($nextMessage?->sendable);
    $isNotSameAsPrevious = $message?->sendable?->isNot($previousMessage?->sendable);
@endphp

@class([
    'flex flex-wrap max-w-fit text-[15px] border border-gray-200/40 dark:border-none rounded-xl p-2.5 flex flex-col text-black bg-[#f6f6f8fb]',
    'text-white' => $belongsToAuth, // Background color for messages sent by the authenticated user
    'dark:bg-gray-800 dark:text-white' => !$belongsToAuth,

    // Message styles based on position and ownership

    // First message on RIGHT
    'rounded-br-md rounded-tr-2xl' => ($isSameAsNext && $isNotSameAsPrevious && $belongsToAuth),

    // Middle message on RIGHT
    'rounded-r-md' => ($isSameAsPrevious && $belongsToAuth),

    // Standalone message RIGHT
    'rounded-br-xl rounded-r-xl' => ($isNotSameAsPrevious && $isNotSameAsNext && $belongsToAuth),

    // Last Message on RIGHT
    'rounded-br-2xl' => ($isNotSameAsPrevious && $belongsToAuth),

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
    {{-- style="color:  var(--primary-color);" --}}
    @class([
        'shrink-0 font-medium text-purple-500',
        // Hide avatar if the next message is from the same user
        'hidden' => $message?->sendable?->is($previousMessage?->sendable)
    ])>
    {{ $message->sendable?->display_name }}
</div>
@endif

<pre class="whitespace-pre-line tracking-normal text-sm md:text-base dark:text-white lg:tracking-normal"
    style="font-family: inherit;">
    {{$message->body}}
</pre>

{{-- Display the created time based on different conditions --}}
<span
@class(['text-[11px] ml-auto ',  'text-gray-700 dark:text-gray-300' => !$belongsToAuth,'text-gray-100' => $belongsToAuth])>
    @php
        // If the message was created today, show only the time (e.g., 1:00 AM)
        echo $message->created_at->format('H:i');
    @endphp
</span>

</div>
