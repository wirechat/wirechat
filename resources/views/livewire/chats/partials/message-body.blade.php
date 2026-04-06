<div class="flex gap-x-2 items-center">

    {{-- Only show if AUTH is onwer of message --}}
    @if ($belongsToAuth)
        <span class="font-bold text-xs dark:text-white/90 dark:font-normal">
            @lang('wirechat::chats.labels.you'):
        </span>
    @elseif(!$belongsToAuth && $group !== null)
        <span class="font-bold text-xs dark:text-white/80 dark:font-normal">
            {{ $lastMessage->sendable?->wirechat_name }}:
        </span>
    @endif

    <p
        dusk="messagePreviewBody"
        class="truncate text-sm dark:text-white gap-2 items-center"
        :class="showUnreadStatus && !@js($belongsToAuth)
            ? 'font-semibold text-black'
            : 'font-normal text-gray-600'"
    >
        {{ $lastMessage->body != '' ? $lastMessage->body : ($lastMessage->isAttachment() ? '📎 '.__('wirechat::chats.labels.attachment') : '') }}
    </p>

    <span
        dusk="messagePreviewTime"
        class="px-1 text-xs shrink-0 dark:text-gray-50"
        :class="showUnreadStatus && !@js($belongsToAuth)
            ? 'font-medium text-gray-800'
            : 'font-normal text-gray-500'"
    >
        @if ($lastMessage->created_at->diffInMinutes(now()) < 1)
          @lang('wirechat::chats.labels.now')
        @else
            {{ $lastMessage->created_at->shortAbsoluteDiffForHumans() }}
        @endif
    </span>


</div>
