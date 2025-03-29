
@use('Namu\WireChat\Facades\WireChat')

<header class="px-3 z-10 sticky top-0 w-full py-2 " dusk="header">


    {{-- Title/name and Icon --}}
    <section class=" justify-between flex items-center   pb-2">

        @if (isset($title))
            <div class="flex items-center gap-2 truncate  " wire:ignore>
                <h2 class=" text-2xl font-bold dark:text-white"  dusk="title">{{$title}}</h2> 
            </div>
        @endif



        <div class="flex gap-x-3 items-center  ">

            @if ($showNewChatModalButton)
            
            <x-wirechat::actions.new-chat widget="{{$this->isWidget()}}">
                <button id="open-new-chat-modal-button" class=" flex items-center focus:outline-hidden">
                    <svg class="w-8 h-8 -mb-1 text-gray-500 hover:text-gray-900 dark:hover:text-gray-200 dark:text-gray-300"
                        xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                        <g fill="none" stroke="currentColor">
                            <path
                                d="M12.875 5C9.225 5 7.4 5 6.242 6.103a4 4 0 0 0-.139.139C5 7.4 5 9.225 5 12.875V17c0 .943 0 1.414.293 1.707S6.057 19 7 19h4.125c3.65 0 5.475 0 6.633-1.103a4 4 0 0 0 .139-.139C19 16.6 19 14.775 19 11.125" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 10h6m-6 4h3m7-6V2m-3 3h6" />
                        </g>
                    </svg>
                    {{-- <svg  class="w-7 h-7 -mb-1 stroke-[0.1]  stroke-none text-gray-500 hover:text-gray-900 dark:hover:text-gray-200 dark:text-gray-300"  stroke="currentColor" viewBox="0 0 24 24" height="24" width="24" preserveAspectRatio="xMidYMid meet" fill="none"> <path d="M9.53277 12.9911H11.5086V14.9671C11.5086 15.3999 11.7634 15.8175 12.1762 15.9488C12.8608 16.1661 13.4909 15.6613 13.4909 15.009V12.9911H15.4672C15.9005 12.9911 16.3181 12.7358 16.449 12.3226C16.6659 11.6381 16.1606 11.0089 15.5086 11.0089H13.4909V9.03332C13.4909 8.60007 13.2361 8.18252 12.8233 8.05119C12.1391 7.83391 11.5086 8.33872 11.5086 8.991V11.0089H9.49088C8.83941 11.0089 8.33411 11.6381 8.55097 12.3226C8.68144 12.7358 9.09947 12.9911 9.53277 12.9911Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M0.944298 5.52617L2.99998 8.84848V17.3333C2.99998 18.8061 4.19389 20 5.66665 20H19.3333C20.8061 20 22 18.8061 22 17.3333V6.66667C22 5.19391 20.8061 4 19.3333 4H1.79468C1.01126 4 0.532088 4.85997 0.944298 5.52617ZM4.99998 8.27977V17.3333C4.99998 17.7015 5.29845 18 5.66665 18H19.3333C19.7015 18 20 17.7015 20 17.3333V6.66667C20 6.29848 19.7015 6 19.3333 6H3.58937L4.99998 8.27977Z" fill="currentColor" stroke="currentColor"></path></svg> --}}
                    {{-- <svg  class="w-7 h-7 -mb-1 text-gray-500 hover:text-gray-900 dark:hover:text-gray-200 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" class="ai ai-ChatAdd"><path d="M12 8v3m0 0v3m0-3h3m-3 0H9"/><path d="M14 19c3.771 0 5.657 0 6.828-1.172C22 16.657 22 14.771 22 11c0-3.771 0-5.657-1.172-6.828C19.657 3 17.771 3 14 3h-4C6.229 3 4.343 3 3.172 4.172 2 5.343 2 7.229 2 11c0 3.771 0 5.657 1.172 6.828.653.654 1.528.943 2.828 1.07"/><path d="M14 19c-1.236 0-2.598.5-3.841 1.145-1.998 1.037-2.997 1.556-3.489 1.225-.492-.33-.399-1.355-.212-3.404L6.5 17.5"/></svg> --}}
                </button>
            </x-wirechat::actions.new-chat>
            @endif


            {{-- Only show if is not widget --}}
            @if ($showHomeRouteButton)
            <a id="redirect-button" href="{{ config('wirechat.home_route', '/') }}" class="flex items-center">
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
            @endif


        </div>



    </section>

    {{-- Search input --}}
    @if ($allowChatsSearch)
        <section class="mt-4">
            <div class="px-2 rounded-lg dark:bg-gray-800 bg-gray-100  grid grid-cols-12 items-center">

                <label for="chats-search-field" class="col-span-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-5 w-5 h-5 dark:text-gray-300">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </label>

                <input id="chats-search-field" name="chats_search" maxlength="100" type="search" wire:model.live.debounce='search'
                    placeholder="{{ __('wirechat::chats.inputs.search.placeholder')  }}" autocomplete="off"
                    class=" col-span-11 border-0  bg-inherit dark:text-white outline-hidden w-full focus:outline-hidden  focus:ring-0 hover:ring-0">
          
                </div>

        </section>
    @endif

</header>
