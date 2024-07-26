@props([
    'previousMessage'=>$previousMessage,
    'message'=>$message,
    'nextMessage'=>$nextMessage,
    'belongsToAuth'=>$belongsToAuth

])


<div @class(['flex transition ease-in flex-wrap max-w-fit text-[15px] border border-gray-200/40 dark:border-none rounded-xl p-2.5 flex
flex-col text-black bg-[#f6f6f8fb]',' bg-blue-500/80 text-white'=> $belongsToAuth,

//first message on RIGHT 
'rounded-br-md rounded-tr-2xl'=>($message?->sender_id==$nextMessage?->sender_id
&&$message?->sender_id!=$previousMessage?->sender_id) && $belongsToAuth,

//middle message on RIGHT
'rounded-r-md'=>$previousMessage?->sender_id==$message->sender_id && $belongsToAuth,

//Standalone message RIGHT
'rounded-br-xl rounded-r-xl'=>($previousMessage?->sender_id!=$message?->sender_id
&&$nextMessage?->sender_id!=$message?->sender_id) && $belongsToAuth,

//last Message on RIGHT
'rounded-br-2xl '=>$previousMessage?->sender_id!==$nextMessage?->sender_id
&&$belongsToAuth,

//**LEFT

//first message on LEFT
'rounded-bl-md rounded-tl-2xl'=>($message?->sender_id==$nextMessage?->sender_id
&&$message?->sender_id!=$previousMessage?->sender_id) && !$belongsToAuth,

//middle message on LEFT
'rounded-l-md'=>$previousMessage?->sender_id==$message->sender_id && !$belongsToAuth,

//Standalone message LEFT
'rounded-bl-xl rounded-l-xl
'=>($previousMessage?->sender_id!=$message?->sender_id&&$nextMessage?->sender_id!=$message?->sender_id)
&& !$belongsToAuth,

//last message on LEFT
'rounded-bl-2xl'=>($message?->sender_id!=$nextMessage?->sender_id ) && !$belongsToAuth,

])
>

<pre class="  whitespace-pre-line tracking-normal    text-sm md:text-base dark:text-white  lg:tracking-normal "
    style="font-family: inherit;">
{{$message->body}}
</pre>

</div>