@props([
    'receiver' => $receiver,
    'conversation' => $conversation
])

@php
    $group = $conversation->group;
@endphp

<header class="w-full  sticky inset-x-0 flex pb-[5px] pt-[7px] top-0 z-10 bg-gray-50 dark:bg-gray-800  dark:border-slate-700 border-b">

    <div class="  flex  w-full items-center   px-2 py-2   lg:px-4 gap-2 md:gap-5 ">
        {{-- Return --}}
        <a href="{{ route('wirechat') }}" class=" shrink-0 lg:hidden  dark:text-white" id="chatReturn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>

        {{-- Receiver wirechat::Avatar --}}
        <section class="grid grid-cols-12 w-full">
            <div class="shrink-0 col-span-11 w-full truncate overflow-h-hidden">

                <div wire:click="$dispatch('openChatModal', {component: 'info',arguments: { conversation: {{ $conversation->id }} }})"
                
                class="flex items-center gap-2 cursor-pointer ">
                   <x-wirechat::avatar group="{{$conversation->isGroup()}}" src="{{ $group ? $group?->cover_url : $receiver?->cover_url ?? null }}" class="h-8 w-8 lg:w-10 lg:h-10 " />
                   <h6 class="font-bold text-base text-gray-800 dark:text-white w-full truncate">
                       {{ $group ? $group?->name : $receiver?->display_name }} @if ($conversation->isSelfConversation())
                           (You)
                       @endif
                   </h6>
               </div>
                {{-- <div x-data="{ modalOpen: false }"  class="relative z-50 w-full h-auto ">
                        <div 
                         @click="modalOpen=true" 
                         class="flex items-center gap-2 cursor-pointer ">
                            <x-wirechat::avatar group="{{$conversation->isGroup()}}" src="{{ $group ? $group?->cover_url : $receiver?->cover_url ?? null }}" class="h-8 w-8 lg:w-10 lg:h-10 " />
                            <h6 class="font-bold text-base text-gray-800 dark:text-white w-full truncate">
                                {{ $group ? $group?->name : $receiver?->display_name }} @if ($conversation->isSelfConversation())
                                    (You)
                                @endif
                            </h6>
                        </div>

                        <div  wire:ignore  x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 -translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-full"
                
                            class="fixed  inset-0 z-[99] h-full  bg-white dark:bg-gray-900 dark:text-white" x-cloak>
                            <div x-trap.inert.noscroll="modalOpen" class="relative w-full space-y-4 ">

                                <section class="flex gap-4  items-center p-5 sticky top-0 bg-white dark:bg-gray-900 ">
                                    <button class="focus:outline-none" @click="modalOpen=false"class=""> <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /> </svg> </button>
                                    <h3>{{$group?'Group':'Chat'}}  Info</h3>
                                </section>
                                <livewire:info :$conversation lazy />

                            </div>
                        </div>
                </div> --}}
            </div>

            {{-- Header Actions --}}
            <div class="flex gap-2 items-center ml-auto col-span-1">
                <x-wirechat::dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex px-0 text-gray-700 dark:text-gray-400">
                            {{-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                class="w-6 h-6 text-gray-600 dark:text-white/90 stroke-[1.4] dark:stroke-[1.3]">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg> --}}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor" class="size-6 w-7 h-7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                              </svg>
                              
                        </button>
                    </x-slot>
                    <x-slot name="content">

                        <button wire:click="$dispatch('openChatModal', {component: 'info',arguments: { conversation: {{ $conversation->id }} }})" class="w-full text-start">

                            <x-wirechat::dropdown-link>
                                {{$conversation->isGroup()?'Group':'Chat'}} Info
                            </x-wirechat::dropdown-link>

                        </button>
                     

                            <x-wirechat::dropdown-link href="{{route('wirechat')}}">
                                Close {{$conversation->isGroup()?'Group':'Chat'}}
                            </x-wirechat::dropdown-link>

                            <button wire:click="deleteConversation" wire:confirm="Are you sure delete {{$conversation->isGroup()?'Group':'Chat'}}" class="w-full text-start">

                                <x-wirechat::dropdown-link class="text-red-500">
                                    Delete {{$conversation->isGroup()?'Group':'Chat'}}
                                </x-wirechat::dropdown-link>
    
                            </button>
    


                        @if ($conversation->isGroup() && !auth()->user()->isOwnerOfConversation($conversation))
                            
                        <button wire:click="exitConversation" wire:confirm="Are you sure want to exit Group?" class="w-full text-start ">

                            <x-wirechat::dropdown-link class="text-red-500 dark:text-gray-500">
                                Exit Group
                            </x-wirechat::dropdown-link>

                        </button>
                        @endif

                    </x-slot>
                </x-wirechat::dropdown>

            </div>
       </section>


    </div>

</header>
