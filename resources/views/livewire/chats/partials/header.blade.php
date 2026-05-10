@use("Wirechat\Wirechat\Facades\Wirechat")

@php
    $hasMessageRequests = $this->panel()->hasMessageRequests();
@endphp

<header class="px-3 z-10 sticky top-0 w-full py-2 " dusk="header">


    {{-- heading/name and Icon --}}
    <section class=" justify-between flex items-center   pb-2">

        @if (isset($heading))
            <div class="flex items-center gap-2 truncate  " wire:ignore>
                <h2 class="text-[1.4rem]  font-bold dark:text-white"  dusk="heading">{{$heading}}</h2>
            </div>
        @endif



        <div class="flex gap-x-4 items-center  border ">

         

            {{-- Widget-Action:Redirect to home --}}
            @php $homeUrl = $this->panel()->getHomeUrl(); @endphp
            @if ($redirectToHomeAction && $homeUrl)
            <a id="redirect-button" href="{{ $homeUrl }}" class="flex items-center">
                      <x-wirechat::icon
                                :icon="$this->panel()->redirectToHomeActionIcon()"
                                 default="wirechat::icons.logout"
                                class="size-6.5"
                                :icon-attributes="$this->panel()->redirectToHomeActionIconAttributes()" 
                            />
            </a>
            @endif

            {{-- Panel-action:Create Chat Action--}}
            @if ($createChatAction)
            <x-wirechat::actions.new-chat widget="{{$this->isWidget()}}" panel="{{$this->panel}}" >
                <button id="open-new-chat-modal-button" class="border flex items-center focus:outline-hidden">
                         <x-wirechat::icon
                                :icon="$this->panel()->createChatActionIcon()"
                                default="wirechat::icons.messages-plus"
                                class="size-6"
                                :icon-attributes="$this->panel()->createChatActionIconAttributes()"
                            />
                    </button>
            </x-wirechat::actions.new-chat>
            @endif

               {{-- Header Actions --}}
            <div class=" my-auto items-center ml-auto col-span-1">
                <x-wirechat::dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="my-auto border"> 
                               <x-wirechat::icon icon="wirechat::icons.ellipsis-vertical" class="size-6" />
                         </button>
                    </x-slot>
                    <x-slot name="content">

                       @if ($hasMessageRequests) 
                        <x-wirechat::actions.open-chats-drawer component="wirechat.chats.requests" widget="{{$this->isWidget()}}" panel="{{$this->panel}}">
                                <x-wirechat::dropdown-item icon="wirechat::icons.user"  id="open-requests-drawer-button">
                                <span>{{ __('wirechat::chats.requests.actions.open.label') }}</span>

                                    @if ($this->pendingMessageRequestsCount() > 0)
                                        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-900 px-1.5 py-0.5 text-xs font-semibold text-white dark:bg-zinc-100 dark:text-zinc-900">
                                            {{ $this->pendingMessageRequestsCount() }}
                                        </span>
                                    @endif
                            </x-wirechat::dropdown-item>
                        </x-wirechat::actions.open-chats-drawer>
                        @endif

                    </x-slot>
                </x-wirechat::dropdown>

            </div>


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
                    class="wc-input col-span-11 border-0 py-2  bg-inherit dark:text-white outline-hidden w-full focus:outline-hidden  focus:ring-0 hover:ring-0">

                </div>

        </section>
    @endif

</header>
