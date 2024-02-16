<div class="flex flex-col transition-all h-full overflow-hidden w-full sm:p-3">


    <header class="px-3 z-10 bg-white sticky top-0 w-full py-2 ">

        {{-- Title/name and Icon --}}
        <section class=" justify-between flex items-center pb-2">

            <div class="flex items-center gap-2 truncate">
                 <h3 class=" text-2xl font-bold">Chat</h2>
            </div>

             <a href="{{config('wirechat.home_route')}}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon-fill w-7 h-7 text-gray-500" viewBox="0 0 16 16">
                    <path d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353zm-6.106 4.5L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708"/>
                </svg>
            </a>

        </section>

        {{-- Filters --}}
        <section class="  gap-3 grid grid-cols-3 items-center mt-1 overflow-x-scroll p-2 bg-white">

            <button class="font-semibold flex justify-center text-black border-b-2 border-black pb-2">
                 All
            </button>
            <button class="font-semibold flex justify-center pb-2 text-gray-500">
                Archived
            </button>

            {{-- <button class="font-semibold flex justify-center pb-2 text-gray-500">
                Requests
            </button> --}}
          
        </section>

        <section class="py-2">

        <input type="text" placeholder="Search"
        class=" border-0 outline-none w-full focus:outline-none bg-gray-100 rounded-lg focus:ring-0 hover:ring-0">

        </section>
    </header>


    <main class=" overflow-y-scroll overflow-hidden grow  h-full relative " style="contain:content">

        @if ($conversations!=null)
        {{-- chatlist  --}}
        <ul class="p-2 grid w-full spacey-y-2">


            @foreach ($conversations as $conversation)
                
            @php
                $receiver= $conversation->getReceiver();
                $lastMessage=$conversation->messages()->latest()->first();
            @endphp

            {{-- Chat list item --}}
            <li class="py-3 {{request()?->chat==$conversation?->id?'bg-gray-100':''}} hover:bg-gray-50 rounded-2xl transition-colors duration-150 flex gap-4 relative w-full cursor-pointer px-2">
                
                <a href="{{route('wirechat.chat',$conversation->id)}}" class="shrink-0">
                    <x-wirechat::avatar
                     src="{{$receiver->wireChatCoverUrl()??null}}" wire:ignore 
                      class="w-12 h-12" />
                </a>
                <aside class="grid grid-cols-12 w-full">


                    <a wire:navigate href="{{route('wirechat.chat',$conversation->id)}}" class="col-span-10 border-b pb-2 border-gray-200 relative overflow-hidden truncate leading-5 w-full flex-nowrap p-1">

                        {{-- name--}}
                        <div class="flex justify-between mb-1 w-full items-center">
                            <h6 class="truncate   font-normal  text-gray-900">
                                {{$receiver->name}}
                            </h6>

                        </div>

                        {{-- Message body --}}
                        @if ($lastMessage!=null)
                        <div class="flex gap-x-2 items-center">
                            
                            {{-- Only show if AUTH is onwer of message --}}
                            @if ($lastMessage->sender_id==auth()->id())
                                <span class="font-bold text-xs">
                                    You:
                                </span>
                            @endif
                           

                             <p class="  truncate text-xs font-[100]">
                                {{$lastMessage->body!=''?$lastMessage->body:($lastMessage->hasAttachment()?'📎 Attachment':'')}}
                             </p>

                             <span class="font-medium px-1 text-xs shrink-0  text-gray-800   ">{{$lastMessage->created_at->shortAbsoluteDiffForHumans()}}</span>


                        </div>
                        @endif

                    </a>

                    {{-- Read status --}}
                    {{-- Only show if AUTH is NOT onwer of message --}}
                    <div class="{{ $lastMessage != null && $lastMessage->sender_id != auth()->id() &&  !$lastMessage->isRead()?'visible':'invisible'}} col-span-2 flex flex-col text-center my-auto">

                        {{-- Dots icon --}}
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dot w-10 h-10 text-blue-500" viewBox="0 0 16 16">
                            <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                          </svg>
                     
                    </div>


                </aside>

            </li>
            
            @endforeach

        </ul>
        @else

        no conversations 
            
        @endif
    </main>
</div>
