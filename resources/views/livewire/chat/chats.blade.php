@use('Namu\WireChat\Facades\WireChat')

@php

$primaryColor= WireChat::getColor();

@endphp

@assets
    <style>
        :root {
            --primary-color: {{ $primaryColor }}
        }

        .custom-scrollbar {
            overflow-y: auto;
            /* Make sure the div is scrollable */

            scrollbar-width: 7px;

            &::-webkit-scrollbar {
                width: 8px;
                background-color: transparent;
            }

            &::-webkit-scrollbar-thumb {
                border-radius: 15px;
                visibility: hidden;
                background-color: #d1d5db;
            }

            /* Show scrollbar on hover */
            &:hover {
                &::-webkit-scrollbar-thumb {
                    visibility: visible;
                }
            }

            @media (prefers-color-scheme: dark) {
                &::-webkit-scrollbar-thumb {
                    background-color: #374151;
                }
            }

            &::-webkit-scrollbar-track {
                background-color: transparent;
            }



        }
    </style>
@endassets


<div x-init=" setTimeout(() => {
     conversationElement = document.getElementById('conversation-' + {{ $selectedConversationId }});

     // Scroll to the conversation element
     if (conversationElement) {
         conversationElement.scrollIntoView({ behavior: 'smooth' });
     }
 }, 200);"
    class="flex flex-col transition-all h-full overflow-hidden w-full sm:p-3 border-r dark:border-gray-700  ">

    @php
        $authUser=auth()->user();
        $authId = $authUser->id;
        $primaryColor = WireChat::getColor();

    @endphp

    {{-- Import header --}}
    <x-wirechat::chats.header />


    <main x-data="{ canLoadMore: @entangle('canLoadMore') }" {{-- Detect when scrolled to the bottom --}}
        @scroll.self.debounce="
        scrollTop = $el.scrollTop;
        scrollHeight = $el.scrollHeight;
        clientHeight = $el.clientHeight;
        
        if (scrollTop + clientHeight >= scrollHeight && canLoadMore) {
            await $nextTick();
            $wire.loadMore();
        }
        "
         class=" overflow-y-auto py-2   grow  h-full relative " style="contain:content">


        @if (config('wirechat.allow_chats_search', false) == true)
            <div x-cloak wire:loading.delay.shorter.class.remove="hidden"
                wire:target="search"class="hidden transition-all duration-300 ">
                <x-wirechat::loading-spin />
            </div>
        @endif

        @if (count($conversations) > 0)
            {{-- chatlist  --}}
            <ul wire:loading.delay.shorter.remove wire:target="search" class="p-2 grid w-full spacey-y-2">

                @foreach ($conversations as $conversation)
                    @php
                        $receiver = $conversation->getReceiver();
                        $lastMessage = $conversation->lastMessage;
                        $isReadByAuth = $conversation?->readBy(auth()?->user());
                        $group=$conversation->group;

                    @endphp

                    {{-- Chat list item --}}
                    {{-- We use style here to make it easy for dynamic and safe injection --}}
                    <li id="conversation-{{ $conversation->id }}" wire:key="conversation-{{ $conversation->id }}"
                        @style([
                            'border-color:' . $primaryColor . '20' => $selectedConversationId == $conversation?->id,
                        ]) @class([
                            'py-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-sm transition-colors duration-150 flex gap-4 relative w-full cursor-pointer px-2',
                            'bg-gray-50 dark:bg-gray-700   border-r-4' =>
                                $selectedConversationId == $conversation?->id,
                        ])>

                        <a href="{{ route('wirechat.chat', $conversation->id) }}" class="shrink-0">
                            <x-wirechat::avatar group="{{$conversation->isGroup()}}" src="{{ $group?$group?->cover_url: $receiver?->cover_url ?? null }}"  class="w-12 h-12" />
                        </a>

                        <aside class="grid  grid-cols-12 w-full">


                            <a wire:navigate href="{{ route('wirechat.chat', $conversation->id) }}"
                                class="col-span-10 border-b pb-2 border-gray-100 dark:border-gray-700 relative overflow-hidden truncate leading-5 w-full flex-nowrap p-1">

                                {{-- name --}}
                                <div class="flex gap-1 mb-1 w-full items-center">
                                    <h6 class="truncate   font-bold  text-gray-900 dark:text-white">
                                        {{ $group?$group?->name: $receiver?->display_name }}
                                    </h6>

                                    @if ($conversation->isSelfConversation())
                                        <span class="font-bold dark:text-white">(You)</span>
                                    @endif

                                </div>

                                {{-- Message body --}}
                                @if ($lastMessage != null)
                                    <div class="flex gap-x-2 items-center">

                                        {{-- Only show if AUTH is onwer of message --}}
                                        @if ($lastMessage->belongsToAuth())
                                            <span class="font-bold text-xs dark:text-white dark:font-normal">
                                                You:
                                            </span>

                                        @elseif(!$lastMessage->belongsToAuth() && $group!==null)
                                        <span class="font-bold text-xs dark:text-white dark:font-normal">
                                            {{$lastMessage->sendable?->display_name}}:
                                        </span>

                                        @endif


                                        <p @class([
                                            'truncate text-sm dark:text-white  gap-2 items-center',
                                            'font-semibold text-black' =>
                                                !$isReadByAuth &&
                                                $lastMessage?->sendable_id != $authUser?->id &&
                                                $lastMessage?->sendable_type == get_class($authUser),
                                            'font-normal text-gray-600' =>
                                                $isReadByAuth &&
                                                $lastMessage?->sendable_id != $authUser?->id &&
                                                $lastMessage?->sendable_type == get_class($authUser),
                                            'font-normal text-gray-600' =>
                                                $isReadByAuth &&
                                                $lastMessage?->sendable_id == $authUser?->id &&
                                                $lastMessage?->sendable_type == get_class($authUser),
                                        ])>
                                            {{ $lastMessage->body != '' ? $lastMessage->body : ($lastMessage->hasAttachment() ? '📎 Attachment' : '') }}
                                        </p>

                                        <span
                                            class="font-medium px-1 text-xs shrink-0  text-gray-800  dark:text-gray-50 ">{{ $lastMessage->created_at->shortAbsoluteDiffForHumans() }}</span>


                                    </div>
                                @endif

                            </a>

                            {{-- Read status --}}
                            {{-- Only show if AUTH is NOT onwer of message --}}
                            <div
                                class="{{ $lastMessage != null && ($lastMessage?->sendable_id != $authUser?->id && $lastMessage?->sendable_type == get_class($authUser)) && !$isReadByAuth ? 'visible' : 'invisible' }} col-span-2 flex flex-col text-center my-auto">

                                {{-- Dots icon --}}
                                <svg @style(['color:' . $primaryColor]) xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    fill="currentColor" class="bi bi-dot w-10 h-10 text-blue-500" viewBox="0 0 16 16">
                                    <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                                </svg>

                            </div>


                        </aside>

                    </li>
                @endforeach

            </ul>

            {{-- Load more button --}}
            @if ($canLoadMore)
                <section class="w-full justify-center flex my-3">
                    <button dusk="loadMoreButton" @click="$wire.loadMore()"
                        class=" text-sm dark:text-white hover:text-gray-700 transition-colors dark:hover:text-gray-500 dark:gray-200">
                        Load more
                    </button>
                </section>
            @endif
        @else
            <div class="w-full flex items-center h-full justify-center">
                <h6 class=" font-bold text-gray-700 dark:text-white">No conversations yet</h6>
            </div>

        @endif
    </main>
    


</div>
