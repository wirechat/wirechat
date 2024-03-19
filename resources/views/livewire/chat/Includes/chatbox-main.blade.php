<main 
    x-data="{
        canLoadMore:@entangle('canLoadMore')
    }"
    @scroll="
    scrollTop= $el.scrollTop;

    
    if((scrollTop<=0) && canLoadMore){
        await $nextTick();
        $wire.loadMore();
    }

    " @update-height.window="

        await $nextTick();
                newHeight=$el.scrollHeight;

                oldHeight= height;

                $el.scrollTop=newHeight- oldHeight;

                height=newHeight;


    " id="conversation"
    class="flex flex-col  gap-2 gap-y-4   p-2.5  overflow-y-auto flex-grow  overscroll-contain overflow-x-hidden w-full my-auto "
    style="contain: content">


    {{-- <div x-cloak wire:loading.flex wire:target="loadMore()"  class="hidden w-full  items-center py-2 ">
        <div  class="mx-auto ">
            <svg aria-hidden="true" class="w-5 h-5 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600"
                viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                    fill="currentColor" />
                <path
                    d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                    fill="currentFill" />
            </svg>
        </div>
    </div> --}}

    <div x-cloak 
            wire:loading.delay.class.remove="invisible"
            wire:target="loadMore" class="invisible transition-all duration-300 ">
              <x-wirechat::loading-spin/>
    </div>


    {{-- Define previous message outside the loop --}}
    @php
    $previousMessage=null;
    
    @endphp

    <!--Message-->
    @foreach ($loadedMessages as $key=> $message)

    @php
    $belongsToAuth= $message->sender_id==auth()->id();
    $parent =$message->parent??null;
    $attachment= $message->attachment??null;
    $isEmoji =mb_ereg('^(?:\X(?=\p{Emoji}))*\X$', $message->body??'');

    // keep track of previous message
    // The ($key -1 ) will get the previous message from loaded
    // messages since $key is directly linked to $message

    if ($key > 0){
    $previousMessage = $loadedMessages->get($key - 1) ;
    }

    // Get the next message
    $nextMessage = ($key < $loadedMessages->count() - 1) ? $loadedMessages->get($key + 1) : null;
        @endphp

        <div  @class([ 'max-w-[85%] md:max-w-[78%]  flex flex-col gap-y-2 ' , 'ml-auto '=>$belongsToAuth])>

            {{-- Show parent/reply message --}}
            @if ($belongsToAuth && $parent!=null)
            <div class="  w-full  flex flex-col gap-y-2    overflow-hidden  ">

                <h6 class="text-xs text-gray-500 px-2 ">You replied to
                    {{$parent?->sender_id== $receiver?->id? $receiver->name:" Yourself"}}
                </h6>

                <div class="border-r-4 px-1 ml-auto">
                    <p class=" bg-gray-100 text-black truncate rounded-full max-w-fit  text-sm px-3 py-1.5 ">
                        {{$parent?->body!=''?$parent?->body:($parent->hasAttachment()?'Attachment':'')}}
                    </p>
                </div>


            </div>
            @endif



            {{-- Body section --}}
            <div @class(['flex gap-1 md:gap-4 group transition-transform',' justify-end'=>$belongsToAuth])>

                {{-- Actions --}}
                <div @class([ 'my-auto flex invisible items-center gap-2 group-hover:visible' , 'order-1'=>!$belongsToAuth,

                    ])>

                    <button wire:click="setReply('{{$message->id}}')" class="hover:scale-110 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7"
                            stroke="currentColor" class="w-4 h-4 text-gray-600/80 ">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                    </button>

                    <x-wirechat::dropdown align="{{$belongsToAuth?'right':'left'}}" width="48">
                        <x-slot name="trigger">
                            {{-- Dots --}}
                            <button class="hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-three-dots h-3 w-3 text-gray-700" viewBox="0 0 16 16">
                                    <path
                                        d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <button wire:click="delete" wire:confirm="are you sure" class="w-full text-start">

                                <x-wirechat::dropdown-link>
                                    Unsend
                                </x-wirechat::dropdown-link>
                            </button>
                        </x-slot>
                    </x-wirechat::dropdown>

                </div>

                {{-- Avatar --}}
                <div @class([ 'shrink-0 mt-auto -mb-2 ' , 'hidden'=> $belongsToAuth,
                    'invisible'=> ($message?->sender_id === $nextMessage?->sender_id)
                    ])>
                    <x-wirechat::avatar src="{{$receiver->wireChatCoverUrl()??null}}" class="h-7 w-7" />
                </div>

                {{-- Message body --}}
                <div class=" flex flex-col  gap-2">

                    {{-- Attachment section --}}
                    @if ($attachment)


                    {{-- Attachemnt is Application/* --}}
                    {{-- Only show if mime type is --}}
                    @if (str()->startsWith($attachment->mime_type, 'application/'))
                   <div class="flex items-center group overflow-hidden border rounded-xl">
                    <span class=" p-2">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-pdf-fill w-11 h-11 text-gray-600" viewBox="0 0 16 16">
                            <path d="M5.523 10.424q.21-.124.459-.238a8 8 0 0 1-.45.606c-.28.337-.498.516-.635.572l-.035.012a.3.3 0 0 1-.026-.044c-.056-.11-.054-.216.04-.36.106-.165.319-.354.647-.548m2.455-1.647q-.178.037-.356.078a21 21 0 0 0 .5-1.05 12 12 0 0 0 .51.858q-.326.048-.654.114m2.525.939a4 4 0 0 1-.435-.41q.344.007.612.054c.317.057.466.147.518.209a.1.1 0 0 1 .026.064.44.44 0 0 1-.06.2.3.3 0 0 1-.094.124.1.1 0 0 1-.069.015c-.09-.003-.258-.066-.498-.256M8.278 4.97c-.04.244-.108.524-.2.829a5 5 0 0 1-.089-.346c-.076-.353-.087-.63-.046-.822.038-.177.11-.248.196-.283a.5.5 0 0 1 .145-.04c.013.03.028.092.032.198q.008.183-.038.465z"/>
                            <path fill-rule="evenodd" d="M4 0h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2m.165 11.668c.09.18.23.343.438.419.207.075.412.04.58-.03.318-.13.635-.436.926-.786.333-.401.683-.927 1.021-1.51a11.6 11.6 0 0 1 1.997-.406c.3.383.61.713.91.95.28.22.603.403.934.417a.86.86 0 0 0 .51-.138c.155-.101.27-.247.354-.416.09-.181.145-.37.138-.563a.84.84 0 0 0-.2-.518c-.226-.27-.596-.4-.96-.465a5.8 5.8 0 0 0-1.335-.05 11 11 0 0 1-.98-1.686c.25-.66.437-1.284.52-1.794.036-.218.055-.426.048-.614a1.24 1.24 0 0 0-.127-.538.7.7 0 0 0-.477-.365c-.202-.043-.41 0-.601.077-.377.15-.576.47-.651.823-.073.34-.04.736.046 1.136.088.406.238.848.43 1.295a20 20 0 0 1-1.062 2.227 7.7 7.7 0 0 0-1.482.645c-.37.22-.699.48-.897.787-.21.326-.275.714-.08 1.103"/>
                          </svg> --}}
                          {{-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill w-9 h-10 text-gray-500" viewBox="0 0 16 16">
                            <path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m.5 2.5a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1z"/>
                          </svg> --}}
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8 text-gray-500">
                            <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625Z" />
                            <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                          </svg>
                          
                          
                    </span>
                    <p class="mt-auto  p-2 text-gray-600 text-sm">
                        {{$attachment->original_name}}
                    </p>


                    <button class="px-3 bg-gray-50 group-hover:bg-gray-100 transition-colors ease-in-out hover:text-blue-500  p-1 mt-auto   h-full">
                        <a download="{{$attachment->original_name}}" href="{{url('storage/' . $attachment?->file_path)}}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download w-5 h-5" viewBox="0 0 16 16">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                          </svg>
                        </a>

                    </button>
                   </div>
                   @endif

                    @if (str()->startsWith($attachment->mime_type, 'video/'))

                    <x-wirechat::video source="{{url('storage/' . $attachment?->file_path) }}" />
                   @endif


                    {{-- Make sure mime type is image before renderiing  --}}
                   @if (str()->startsWith($attachment->mime_type, 'image/'))

                    <img @class([ 'max-w-max  h-[200px] min-h-[200px] bg-gray-200/60   object-scale-down  grow-0 shrink  overflow-hidden  rounded-3xl'
                        , 'rounded-br-md rounded-tr-2xl'=>($message?->sender_id==$nextMessage?->sender_id &&
                    $message?->sender_id!=$previousMessage?->sender_id) && $belongsToAuth,

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


                    ])
                    loading="lazy" src="{{ url('storage/' . $attachment?->file_path) }}" alt="attachment">

                    @endif
                    @endif


                    @if ($isEmoji)

                    <p class="text-5xl">
                        {{$message->body}}
                    </p>


                    
                    @endif

                    @if ($message->body && !$isEmoji)
                    {{-- message body --}}
                    <div  
                      @class(['flex flex-wrap max-w-fit text-[15px] border border-gray-200/40 rounded-xl p-2.5 flex
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

                        <pre class="  whitespace-pre-line tracking-normal    text-sm md:text-base  lg:tracking-normal "
                            style="font-family: inherit;">
                        {{$message->body}}
                      </pre>

                    </div>
                    @endif
                </div>

            </div>





        </div>

        @endforeach

</main>