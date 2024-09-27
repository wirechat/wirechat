@props(
[
'receiver'=>$receiver,
'conversation'=>$conversation]

)


<header class="w-full  sticky inset-x-0 flex pb-[5px] pt-[7px] top-0 z-10  dark:border-gray-700 border-b">

    <div class="  flex  w-full items-center   px-2   lg:px-4 gap-2 md:gap-5 ">
        {{-- Return --}}
        <a href="{{route('wirechat')}}" class=" shrink-0 lg:hidden  dark:text-white" id="chatReturn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>

        {{--Receiver wirechat::Avatar --}}
        <div class="shrink-0">
            <a class="flex items-center gap-2 " href="{{$receiver?->profile_url??'#'}}">
                <x-wirechat::avatar src="{{$receiver?->cover_url??null}}" wire:ignore
                    class="h-8 w-8 lg:w-10 lg:h-10 " />
                <h6 class="font-bold text-lg text-gray-800 dark:text-white truncate"> {{$receiver?->display_name}}  @if ($conversation->isSelfConversation()) (You) @endif  </h6>
            </a>
        </div>


        {{-- Header Actions --}}
        <div class="flex gap-2 items-center ml-auto">
            <x-wirechat::dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex px-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" class="w-6 h-6 dark:text-white stroke-[1.9] dark:stroke-[1.3]">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <button wire:click="deleteConversation" wire:confirm="Are you sure" class="w-full text-start">

                        <x-wirechat::dropdown-link>
                            Delete Conversation
                        </x-wirechat::dropdown-link>

                    </button>

                </x-slot>
            </x-wirechat::dropdown>

        </div>

    </div>

</header>
