<div class="flex gap-1 mb-1 w-full items-center">
    <h6 class="truncate font-medium text-gray-900 dark:text-white">
        {{ $group ? $group?->name : $receiver?->wirechat_name }}
    </h6>

    @if ($conversation->isSelfConversation())
        <span class="font-medium dark:text-white">({{__('wirechat::chats.labels.you')  }})</span>
    @endif

</div>
