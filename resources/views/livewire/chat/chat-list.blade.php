@use('Namu\WireChat\Facades\WireChat')
<div
x-init=" 
setTimeout(()=>{
    conversationElement = document.getElementById('conversation-'+{{$selectedConversationId}});

    // Scroll to the conversation element
    if (conversationElement) {
    conversationElement.scrollIntoView({behavior:'smooth'});
    }
},200);
   
        "
 class="flex flex-col transition-all h-full overflow-hidden w-full sm:p-3 border-r dark:border-gray-700  ">

 @php
     $authId=$authUser->id;
     $primaryColor =  WireChat::getColor()

 @endphp

    <header class="px-3 z-10 bg-white dark:bg-gray-800 sticky top-0 w-full py-2  ">

   
        {{-- Title/name and Icon --}}
        <section class=" justify-between flex items-center  pb-2">

            <div class="flex items-center gap-2 truncate ">
                 <h3 class=" text-2xl font-bold dark:text-white">Chats</h2>
            </div>


            <div class="flex gap-x-3 items-center  ">
                {{-- todo:reserved for future updates --}}

                @if  (config('wirechat.allow_new_chat_modal',false)==true)
                    
                <div x-data="{ modalOpen: false }"
                    @keydown.escape.window="modalOpen = false"
                    class="relative z-50 flex  max-h-fit">

                    {{-- open  new message modal --}}
                    <button id="open-new-chat-modal-button" class="my-auto"   @click="modalOpen=true">
               
                          {{-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 dark:stroke-[1.3] dark:text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                          </svg>
                           --}}
 
                           <svg  class="w-7 h-7 text-gray-700 hover:text-gray-900 dark:hover:text-gray-200 stroke-[0.4] dark:stroke-[1] dark:text-white" fill="currentColor" width="800px" height="800px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M14,9H13V8a1,1,0,0,0-2,0V9H10a1,1,0,0,0,0,2h1v1a1,1,0,0,0,2,0V11h1a1,1,0,0,0,0-2Zm5-7H5A3,3,0,0,0,2,5V15a3,3,0,0,0,3,3H16.59l3.7,3.71A1,1,0,0,0,21,22a.84.84,0,0,0,.38-.08A1,1,0,0,0,22,21V5A3,3,0,0,0,19,2Zm1,16.59-2.29-2.3A1,1,0,0,0,17,16H5a1,1,0,0,1-1-1V5A1,1,0,0,1,5,4H19a1,1,0,0,1,1,1Z"/></svg>
                  
                        </button>
    
                    <template x-teleport="body">
                        <div id="new-chat-modal" x-show="modalOpen" class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen" x-cloak>
                            <div x-show="modalOpen" 
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-300"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @click="modalOpen=false" class="absolute inset-0 w-full h-full bg-black bg-opacity-40"></div>
                            <div x-show="modalOpen"
                                x-trap.inert.noscroll="modalOpen"
                                x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave="ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                class="relative w-full h-96  border  dark:border-gray-700 overflow-auto bg-white dark:bg-gray-800 dark:text-white px-7 sm:max-w-lg sm:rounded-lg">
                                
                                <header class=" sticky top-0 bg-white dark:bg-gray-800 z-10 py-2">
                                    <div class="flex justify-between items-center justify-between pb-2">
                                        <h3 class="text-lg font-semibold">Send message</h3>
                                        <button @click="modalOpen=false" class="p-2  text-gray-600 hover:dark:bg-gray-700 hover:dark:text-white rounded-full hover:text-gray-800 hover:bg-gray-50">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>  
                                        </button>

                                        
                                    </div>
                                    <section class="flex flex-wrap items-center px-0 border-b dark:border-gray-700">
                                        <p>
                                         To:
                                        </p>
                                         <input type="search" id="users-search-field"  wire:model.live.debounce='searchUsers' placeholder="Search"
                                         class=" border-0 w-auto dark:bg-gray-800 outline-none focus:outline-none bg-none rounded-lg focus:ring-0 hover:ring-0">

                                        </section>
 
                                </header>
                             
                                <div class="relative w-full">
                                     {{-- <button

                                       @style([
                                        'color:'. $primaryColor,
                                        ])
                                     
                                       class="flex items-center gap-3 my-4 rounded-lg  w-full p-2">
                                        <span class="p-1 bg-gray-100  rounded-full ">
                              
                                              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class=" w-5 h-5">
                                                <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 0 1-.372.568A12.696 12.696 0 0 1 12 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 0 1-.372-.568 6.787 6.787 0 0 1 1.019-4.38Z" clip-rule="evenodd" />
                                                <path d="M5.082 14.254a8.287 8.287 0 0 0-1.308 5.135 9.687 9.687 0 0 1-1.764-.44l-.115-.04a.563.563 0 0 1-.373-.487l-.01-.121a3.75 3.75 0 0 1 3.57-4.047ZM20.226 19.389a8.287 8.287 0 0 0-1.308-5.135 3.75 3.75 0 0 1 3.57 4.047l-.01.121a.563.563 0 0 1-.373.486l-.115.04c-.567.2-1.156.349-1.764.441Z" />
                                              </svg>

                                        </span>

                                        <p>
                                            New group 
                                        </p>
                                     </button> --}}

                                     {{-- <h5 class="text font-semibold text-gray-800 dark:text-gray-100">Recent Chats</h5> --}}
                                     
                                     <section class="my-4">
                                        @if ($users)

                                        <ul class="overflow-auto flex flex-col">

                                            @foreach ($users as $key=> $user)
                                            <li wire:key="user-{{$key}}" wire:click="createConversation('{{$user->id}}',{{json_encode(get_class($user))}})" class="flex cursor-pointer group gap-2 items-center p-2">

                                                <x-wirechat::avatar    class="w-5 h-5" />

                                                <p class="group-hover:underline transition-all">{{$user->display_name}}</p>

                                            </li>
                                                
                                            @endforeach
                                           
                                          
                                        </ul>

                                        @else

                                        No accounts found

                                        @endif

                                     </section>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                @endif

              
                <a id="redirect-button" href="{{config('wirechat.redirect_route','/')}}">
                    <svg xmlns="http://www.w3.org/2000/svg"  fill="currentColor" class="bi bi-x-octagon-fill w-6 h-6 text-gray-500 transition-colors duration-300 dark:hover:text-gray-400 hover:text-gray-900" viewBox="0 0 16 16">
                        <path d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353zm-6.106 4.5L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708"/>
                    </svg>
                </a>
            
            </div>

             

        </section>

        {{-- Filters --}}
        <section class="gap-3 grid grid-cols-3 items-center mt-1 overflow-x-scroll p-2 bg-white dark:bg-gray-800">

            {{-- <button class="font-semibold flex gap-2 justify-center text-black dark:text-white dark:border-gray-700  border-b-2 border-black pb-2">
                 All

                 @if ($unReadMessagesCount>0)
                 <span class="rounded-full text-xs p-1 px-2 scale-95  tracking-wide font-bold bg-blue-500 text-white ">
                     {{$unReadMessagesCount}}
                 </span>
                 @endif

            </button> --}}
            {{-- <button class="font-semibold flex justify-center pb-2 text-gray-500">
                Archived
            </button> --}}

            {{-- <button class="font-semibold flex justify-center pb-2 text-gray-500">
                Requests
            </button> --}}
          
        </section>
  
        {{-- Search input --}}
        @if (config('wirechat.allow_chats_search',false)==true)
        <section class="p  ">

            <div class="px-2 rounded-lg dark:bg-gray-700 bg-gray-100  grid grid-cols-12 items-center">


            <label for="chats-search-field" class="col-span-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                  </svg>
                  
                </label>

            <input id="chats-search-field" name="chats_search" type="search" wire:model.live.debounce='search' placeholder="Search"
            class=" col-span-11 border-0  bg-inherit dark:text-white outline-none w-full focus:outline-none  focus:ring-0 hover:ring-0">
        </div>
       
        </section>
        @endif
      
    </header>


    <main class="   overflow-y-scroll py-2 overflow-hidden grow  h-full relative " style="contain:content">
    
    
        @if (config('wirechat.allow_chats_search',false)==true)
        <div x-cloak 
            wire:loading.delay.shorter.class.remove="hidden" 
            wire:target="search" class="hidden transition-all duration-300 ">
        <x-wirechat::loading-spin/>
        </div>
        @endif
    
        @if (count($conversations)>0)
        {{-- chatlist  --}}
        
        <ul wire:loading.delay.shorter.remove  wire:target="search"  class="p-2 grid w-full spacey-y-2">

            @foreach ($conversations as $conversation)
                
            @php
                $receiver= $conversation->getReceiver();

                $lastMessage=$conversation->messages()->latest()->first();
            @endphp

            {{-- Chat list item --}}

            <li  id="conversation-{{$conversation->id}}"  wire:key="conversation-{{$conversation->id}}"

                {{-- We use style here to make it easy for dynamic and safe injection --}}
                @style([
                    'border-color:'. $primaryColor .'20' => $selectedConversationId==$conversation?->id,
                    ])
                @class([
                    'py-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm transition-colors duration-150 flex gap-4 relative w-full cursor-pointer px-2',
                    'bg-gray-50 dark:bg-gray-700   border-r-4'=>$selectedConversationId==$conversation?->id,
                    ])>


                <a href="{{route('wirechat.chat',$conversation->id)}}" class="shrink-0">
                    <x-wirechat::avatar src="{{$receiver?->cover_url??null}}" wire:ignore  class="w-12 h-12" />
                </a>
                <aside class="grid grid-cols-12 w-full">


                    <a wire:navigate href="{{route('wirechat.chat',$conversation->id)}}" class="col-span-10 border-b pb-2 border-gray-200 dark:border-gray-600 relative overflow-hidden truncate leading-5 w-full flex-nowrap p-1">

                        {{-- name--}}
                        <div class="flex gap-1 mb-1 w-full items-center">
                            <h6 class="truncate   font-bold  text-gray-900 dark:text-white">
                                {{$receiver?->display_name}}
                            </h6>

                            @if ($conversation->isSelfConversation())
                            <span class="font-bold">(You)</span>
                                
                            @endif

                        </div>

                        {{-- Message body --}}
                        @if ($lastMessage!=null)
                        <div class="flex gap-x-2 items-center">
                            
                            {{-- Only show if AUTH is onwer of message --}}
                            @if ($lastMessage->sendable_id==$authUser->id && $lastMessage->sendable_type== get_class($authUser))
                                <span class="font-bold text-xs dark:text-white dark:font-normal">
                                    You:
                                </span>
                            @endif
                           

                             <p 
                             @class([
                                'truncate text-sm dark:text-white  gap-2 items-center',
                                'font-semibold text-black' => !$lastMessage?->readBy(auth()?->user()) && $lastMessage?->sendable_id != $authUser?->id && $lastMessage?->sendable_type== get_class($authUser),
                                'font-normal text-gray-600' => $lastMessage?->readBy(auth()?->user()) && $lastMessage?->sendable_id != $authUser?->id && $lastMessage?->sendable_type== get_class($authUser),
                                'font-normal text-gray-600' => $lastMessage?->readBy(auth()?->user()) && $lastMessage?->sendable_id == $authUser?->id && $lastMessage?->sendable_type== get_class($authUser),
                            ])
                             >
                                {{$lastMessage->body!=''?$lastMessage->body:($lastMessage->hasAttachment()?'📎 Attachment':'')}}
                             </p>

                             <span class="font-medium px-1 text-xs shrink-0  text-gray-800  dark:text-gray-50 ">{{$lastMessage->created_at->shortAbsoluteDiffForHumans()}}</span>


                        </div>
                        @endif

                    </a>

                    {{-- Read status --}}
                    {{-- Only show if AUTH is NOT onwer of message --}}
                    <div class="{{ $lastMessage != null && ( $lastMessage?->sendable_id != $authUser?->id && $lastMessage?->sendable_type== get_class($authUser)) &&  !$lastMessage->readBy(auth()?->user())?'visible':'invisible'}} col-span-2 flex flex-col text-center my-auto">

                        {{-- Dots icon --}}
                        <svg
                        @style([
                            'color:'. $primaryColor,
                            ])
                         xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dot w-10 h-10 text-blue-500" viewBox="0 0 16 16">
                            <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                          </svg>
                     
                    </div>


                </aside>

            </li>
            
            @endforeach

        </ul>
        @else

        <div class="w-full flex items-center h-full justify-center">
            <h6 class=" font-bold text-gray-700 dark:text-white">No conversations yet</h6>
        </div>
            
        @endif
    </main>
</div>
