


@php

   $isSameAsNext = ($message?->sendable_id === $nextMessage?->sendable_id) && ($message?->sendable_type === $nextMessage?->sendable_type);
   $isNotSameAsNext = !$isSameAsNext;
   $isSameAsPrevious = ($message?->sendable_id === $previousMessage?->sendable_id) && ($message?->sendable_type === $previousMessage?->sendable_type);
   $isNotSameAsPrevious = !$isSameAsPrevious;
@endphp



<img @class([ 

        'max-w-max  h-[200px] min-h-[210px] bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)]   object-scale-down  grow-0 shrink  overflow-hidden  rounded-3xl',

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
        
        loading="lazy" src="{{$attachment?->url}}" alt="{{  __('wirechat::chat.labels.attachment') }}">
