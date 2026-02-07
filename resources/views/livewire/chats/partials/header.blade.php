@use("Wirechat\Wirechat\Facades\Wirechat")

<header class="px-3 z-10 sticky top-0 w-full py-2 " dusk="header">


    {{-- heading/name and Icon --}}
    <section class=" justify-between flex items-center   pb-2">

        @if (isset($heading))
            <div class="flex items-center gap-2 truncate  " wire:ignore>
                <h2 class="text-[1.4rem]  font-bold dark:text-white"  dusk="heading">{{$heading}}</h2>
            </div>
        @endif



        <div class="flex gap-x-4 items-center  ">

            {{-- Widget-Action:Redirect to home --}}
            @if ($redirectToHomeAction)
            <a id="redirect-button" href="{{ $this->panel()->getHomeUrl() }}" class="flex items-center">
                     <svg class="size-6  text-zinc-700 dark:hover:text-gray-200 dark:text-gray-100" xmlns="http://www.w3.org/2000/svg"  stroke="currentColor" stroke-width="0.3" width="16" height="16" fill="currentColor"  viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z"/>
                        <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
                    </svg>
            </a>
            @endif

            {{-- Panel-action:Creat Chat Action--}}
            @if ($createChatAction)
            <x-wirechat::actions.new-chat widget="{{$this->isWidget()}}" panel="{{$this->panel}}" >
                <button id="open-new-chat-modal-button" class=" flex items-center focus:outline-hidden">
                     <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-zinc-700  dark:hover:text-gray-200 stroke-1 dark:text-gray-100"   fill="none" data-icon="icon-messages-plus-stroke" viewBox="0 0 24 24" width="1em" height="1em" display="flex" role="img"><path fill="currentColor" d="M4.49805 19C4.22205 19 3.99805 18.776 3.99805 18.5V10.462L11.998 14.099L19.998 10.462V13H21.998V5.5C21.998 4.122 20.876 3 19.498 3H4.49805C3.12005 3 1.99805 4.122 1.99805 5.5V18.5C1.99805 19.878 3.12005 21 4.49805 21H11V19H4.49805ZM4.49805 5H19.498C19.774 5 19.998 5.224 19.998 5.5V8.265L11.998 11.902L3.99805 8.265V5.5C3.99805 5.224 4.22205 5 4.49805 5ZM14 18H17V15H19V18H22V20H19V23H17V20H14V18Z"></path></svg>
                </button>
            </x-wirechat::actions.new-chat>
            @endif


        </div>



    </section>

    {{-- Search input --}}
    @if ($chatsSearch)
        <section class="mt-4">
            <div class="px-2 rounded-lg dark:bg-[var(--wc-dark-secondary)]  bg-[var(--wc-light-secondary)]  grid grid-cols-12 items-center">

                <label for="chats-search-field" class="col-span-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-5 w-5 h-5 dark:text-gray-300">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </label>

                <input id="chats-search-field" name="chats_search" maxlength="100" type="search" wire:model.live.debounce='search'
                    placeholder="{{ __('wirechat::chats.inputs.search.placeholder')  }}" autocomplete="off"
                    class="wc-input col-span-11 border-0  bg-inherit dark:text-white outline-hidden w-full focus:outline-hidden  focus:ring-0 hover:ring-0">

                </div>

        </section>
    @endif

</header>
