@use('Namu\WireChat\Facades\WireChat')

@props([
    'previousMessage' => $previousMessage,
    'message' => $message,
    'nextMessage' => $nextMessage,
    'belongsToAuth' => $belongsToAuth,
    'primaryColor'=> WireChat::getColor()

])

<div

{{-- We use style here to make it easy for dynamic and safe injection --}}
@style([
'background-color:'. $primaryColor .'' => $belongsToAuth==true
])

@class([
    'flex flex-wrap max-w-fit text-[15px] border border-gray-200/40 dark:border-none rounded-xl p-2.5 flex flex-col text-black bg-[#f6f6f8fb]',

    // Background color for messages sent by the authenticated user
    'text-white' => $belongsToAuth,//set backg
    'dark:bg-gray-700 dark:text-white' => !$belongsToAuth,

    // Message styles based on position and ownership

    // First message on RIGHT
    'rounded-br-md rounded-tr-2xl' => (
        $message->sendable_id == $nextMessage?->sendable_id
        && $message->sendable_type == $nextMessage?->sendable_type
        && $message->sendable_id != $previousMessage?->sendable_id
        && $message->sendable_type == $previousMessage?->sendable_type
        && $belongsToAuth
    ),

    // Middle message on RIGHT
    'rounded-r-md' => (
        $previousMessage?->sendable_id == $message->sendable_id
        && $previousMessage?->sendable_type == $message->sendable_type
        && $belongsToAuth
    ),

    // Standalone message RIGHT
    'rounded-br-xl rounded-r-xl' => (
        $previousMessage?->sendable_id != $message->sendable_id
        && $previousMessage?->sendable_type != $message->sendable_type
        && $nextMessage?->sendable_id != $message->sendable_id
        && $nextMessage?->sendable_type != $message->sendable_type
        && $belongsToAuth
    ),

    // Last Message on RIGHT
    'rounded-br-2xl' => (
        $previousMessage?->sendable_id != $nextMessage?->sendable_id && $previousMessage?->sendable_type == $nextMessage?->sendable_type && $belongsToAuth
    ),

    // First message on LEFT
    'rounded-bl-md rounded-tl-2xl' => (
        $message->sendable_id == $nextMessage?->sendable_id
        && $message->sendable_type == $nextMessage?->sendable_type
        && $message->sendable_id != $previousMessage?->sendable_id
        && $message->sendable_type == $previousMessage?->sendable_type
        && !$belongsToAuth
    ),

    // Middle message on LEFT
    'rounded-l-md' => (
        $previousMessage?->sendable_id == $message->sendable_id
        && $previousMessage?->sendable_type == $message->sendable_type
        && !$belongsToAuth
    ),

    // Standalone message LEFT
    'rounded-bl-xl rounded-l-xl' => (
        $previousMessage?->sendable_id != $message->sendable_id
        && $previousMessage?->sendable_type != $message->sendable_type
        && $nextMessage?->sendable_id != $message->sendable_id
        && $nextMessage?->sendable_type != $message->sendable_type
        && !$belongsToAuth
    ),

    // Last message on LEFT
    'rounded-bl-2xl' => (
        $message->sendable_id != $nextMessage?->sendable_id
        && $message->sendable_type == $nextMessage?->sendable_type && !$belongsToAuth
    ),

])>

{{-- @dd($primaryColor) --}}
<pre class="whitespace-pre-line tracking-normal text-sm md:text-base dark:text-white lg:tracking-normal"
    style="font-family: inherit;">
    {{$message->body}}
</pre>

{{-- Display the created time based on different conditions --}}
<span
@class(['text-[11px] ml-auto text-gray-700',     'text-gray-100' => $belongsToAuth])>
    @php
        $createdAt = $message->created_at;

        if ($createdAt->isToday()) {
            // If the message was created today, show only the time (e.g., 1:00 AM)
            echo $createdAt->format('g:i A');
        }elseif ($createdAt->isCurrentWeek()) {
            // If the message was created within this week, show day of the week + time (e.g., Wed 12:00 AM)
            echo $createdAt->format('D g:i A');
        } else {
            // If the message was created more than a week ago, show the full date (e.g., 12/05/24)
            echo $createdAt->format('m/d/y');
        }
    @endphp
</span>

</div>
