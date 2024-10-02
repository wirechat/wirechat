@props(['users'=>$users])
<header class="px-3 z-10  sticky top-0 w-full py-2  ">


    {{-- Title/name and Icon --}}
    <section class=" justify-between flex items-center mb-4  pb-2">

        <div class="flex items-center gap-2 truncate ">
            <h2 class=" text-2xl font-bold dark:text-white">Chats</h2>
        </div>


        <div class="flex gap-x-3 items-center  ">
            @if (config('wirechat.allow_new_chat_modal', false) == true)

                <div x-data="{ modalOpen: false }" @keydown.escape.window="modalOpen = false"
                    class="relative z-50 flex  max-h-fit">
                    {{-- open  new message modal button --}}
                    <button id="open-new-chat-modal-button" class=" flex items-center" @click="modalOpen=true">
                        <svg class="w-8 h-8 -mb-1 text-gray-500 hover:text-gray-900 dark:hover:text-gray-200 dark:text-gray-300"
                            xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                            <g fill="none" stroke="currentColor">
                                <path
                                    d="M12.875 5C9.225 5 7.4 5 6.242 6.103a4 4 0 0 0-.139.139C5 7.4 5 9.225 5 12.875V17c0 .943 0 1.414.293 1.707S6.057 19 7 19h4.125c3.65 0 5.475 0 6.633-1.103a4 4 0 0 0 .139-.139C19 16.6 19 14.775 19 11.125" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 10h6m-6 4h3m7-6V2m-3 3h6" />
                            </g>
                        </svg>


                        {{-- <svg  class="w-7 h-7 -mb-1 text-gray-500 hover:text-gray-900 dark:hover:text-gray-200 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" class="ai ai-ChatAdd"><path d="M12 8v3m0 0v3m0-3h3m-3 0H9"/><path d="M14 19c3.771 0 5.657 0 6.828-1.172C22 16.657 22 14.771 22 11c0-3.771 0-5.657-1.172-6.828C19.657 3 17.771 3 14 3h-4C6.229 3 4.343 3 3.172 4.172 2 5.343 2 7.229 2 11c0 3.771 0 5.657 1.172 6.828.653.654 1.528.943 2.828 1.07"/><path d="M14 19c-1.236 0-2.598.5-3.841 1.145-1.998 1.037-2.997 1.556-3.489 1.225-.492-.33-.399-1.355-.212-3.404L6.5 17.5"/></svg> --}}

                    </button>

                    <template x-teleport="body">
                        <div id="new-chat-modal" x-show="modalOpen"
                            class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen"
                            x-cloak>
                            <div x-show="modalOpen" x-transition:enter="ease-out duration-300"
                                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-300" x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0" @click="modalOpen=false"
                                class="absolute inset-0 w-full h-full bg-black bg-opacity-40"></div>
                            <div x-show="modalOpen" x-trap.inert.noscroll="modalOpen"
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
                                        <button @click="modalOpen=false"
                                            class="p-2  text-gray-600 hover:dark:bg-gray-700 hover:dark:text-white rounded-full hover:text-gray-800 hover:bg-gray-50">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>


                                    </div>
                                    <section class="flex flex-wrap items-center px-0 border-b dark:border-gray-700">
                                        <p>
                                            To:
                                        </p>
                                        <input type="search" id="users-search-field"
                                            wire:model.live.debounce='searchUsers' placeholder="Search"
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

                                                @foreach ($users as $key => $user)
                                                    <li wire:key="user-{{ $key }}"
                                                        wire:click="createConversation('{{ $user->id }}',{{ json_encode(get_class($user)) }})"
                                                        class="flex cursor-pointer group gap-2 items-center p-2">

                                                        <x-wirechat::avatar class="w-5 h-5" />

                                                        <p class="group-hover:underline transition-all">
                                                            {{ $user->display_name }}</p>

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


            <a id="redirect-button" href="{{ config('wirechat.redirect_route', '/') }}" class="flex items-center">
                {{-- <svg xmlns="http://www.w3.org/2000/svg"  fill="currentColor" class="bi bi-x-octagon-fill w-6 h-6 text-gray-500 dark:text-gray-400 transition-colors duration-300 dark:hover:text-gray-500 hover:text-gray-900" viewBox="0 0 16 16">
                    <path d="M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353zm-6.106 4.5L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708"/>
                </svg> --}}

                <svg class="bi bi-x-octagon-fill w-8 my-auto h-8 stroke-[0.9] text-gray-500 dark:text-gray-400 transition-colors duration-300 dark:hover:text-gray-500 hover:text-gray-900"
                    xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                    <g fill="none" stroke="currentColor">
                        <path
                            d="M5 12.76c0-1.358 0-2.037.274-2.634c.275-.597.79-1.038 1.821-1.922l1-.857C9.96 5.75 10.89 4.95 12 4.95s2.041.799 3.905 2.396l1 .857c1.03.884 1.546 1.325 1.82 1.922c.275.597.275 1.276.275 2.634V17c0 1.886 0 2.828-.586 3.414S16.886 21 15 21H9c-1.886 0-2.828 0-3.414-.586S5 18.886 5 17z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.5 21v-5a1 1 0 0 0-1-1h-3a1 1 0 0 0-1 1v5" />
                    </g>
                </svg>
                {{-- <svg class="bi bi-x-octagon-fill w-7 h-7 stroke-[0.5] text-gray-500 dark:text-gray-400 transition-colors duration-300 dark:hover:text-gray-500 hover:text-gray-900" xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 256 256"><path d="M219.31,108.68l-80-80a16,16,0,0,0-22.62,0l-80,80A15.87,15.87,0,0,0,32,120v96a8,8,0,0,0,8,8H216a8,8,0,0,0,8-8V120A15.87,15.87,0,0,0,219.31,108.68ZM208,208H48V120l80-80,80,80Z"></path></svg> --}}
            </a>

        </div>



    </section>

    {{-- Search input --}}
    @if (config('wirechat.allow_chats_search', false) == true)
        <section>

            <div class="px-2 rounded-lg dark:bg-gray-700 bg-gray-100  grid grid-cols-12 items-center">

                <label for="chats-search-field" class="col-span-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-5 w-5 h-5 dark:text-gray-300">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </label>

                <input id="chats-search-field" name="chats_search" type="search" wire:model.live.debounce='search'
                    placeholder="Search"
                    class=" col-span-11 border-0  bg-inherit dark:text-white outline-none w-full focus:outline-none  focus:ring-0 hover:ring-0">
            </div>

        </section>
    @endif

</header>