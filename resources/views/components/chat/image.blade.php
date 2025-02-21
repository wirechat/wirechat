@props([
    'previousMessage'=>$previousMessage,
    'message'=>$message,
    'nextMessage'=>$nextMessage,
    'belongsToAuth'=>$belongsToAuth,
    'attachment'=>$attachment

])


<img @class([ 

        'max-w-max  h-[200px] min-h-[210px] bg-gray-50/60 dark:bg-gray-700/20   object-scale-down  grow-0 shrink  overflow-hidden  rounded-3xl',

        'rounded-br-md rounded-tr-2xl'=>($message?->sender_id==$nextMessage?->sender_id && $message?->sender_id!=$previousMessage?->sender_id) && $belongsToAuth,

        //middle message on RIGHT
        'rounded-r-md'=>$previousMessage?->sender_id==$message->sender_id && $belongsToAuth,

        //Standalone message RIGHT
        'rounded-br-xl rounded-r-xl'=>($previousMessage?->sender_id!=$message?->sender_id &&
        $nextMessage?->sender_id!=$message?->sender_id) && $belongsToAuth,


        //last Message on RIGHT
        'rounded-br-2xl '=>$previousMessage?->sender_id!==$nextMessage?->sender_id &&$belongsToAuth,

        //**LEFT

        //first message on LEFT
        'rounded-bl-md rounded-tl-2xl'=>($message?->sender_id==$nextMessage?->sender_id
        &&$message?->sender_id!=$previousMessage?->sender_id) && !$belongsToAuth,

        //middle message on LEFT
        'rounded-l-md'=>$previousMessage?->sender_id==$message->sender_id && !$belongsToAuth,

        //Standalone message LEFT
        'rounded-bl-xl rounded-l-xl '=>($previousMessage?->sender_id!=$message?->sender_id
        &&$nextMessage?->sender_id!=$message?->sender_id) && !$belongsToAuth,

        //last message on LEFT
        'rounded-bl-2xl'=>($message?->sender_id!=$nextMessage?->sender_id ) && !$belongsToAuth,
        ]) loading="lazy" src="{{$attachment?->url}}" alt="{{ __('attachment') }}">
