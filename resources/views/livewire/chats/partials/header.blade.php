@use("Wirechat\Wirechat\Facades\Wirechat")

@php
    $hasSettings = $this->panel()->hasSettings();
    $hasMessageRequests = $this->panel()->hasMessageRequests();
    $pendingMessageRequestsCount = $hasMessageRequests ? $this->pendingMessageRequestsCount() : 0;
    $canCreateGroups = $this->panel()->hasCreateGroupAction() && auth()->user()?->canCreateGroups();
    $hasHeaderMenuActions = $hasSettings || $hasMessageRequests || $canCreateGroups;
@endphp

<header class="sticky top-0 z-10 flex w-full flex-col gap-1.5 border-b border-zinc-100 bg-[var(--wc-light-primary)] px-4 py-3 dark:border-zinc-800 dark:bg-[var(--wc-dark-primary)]" dusk="header">


    {{-- heading/name and Icon --}}
    <section class="mb-1 flex items-center justify-between gap-3">

        @if (isset($heading))
            <div class="min-w-0 flex-1 text-left" wire:ignore>
                <h2 class="truncate text-[1.4rem] font-bold text-zinc-900 dark:text-white"  dusk="heading">{{$heading}}</h2>
            </div>
        @endif



        <div class="flex shrink-0 items-center gap-x-1">



            {{-- Widget-Action:Redirect to home --}}
            @php $homeUrl = $this->panel()->getHomeUrl(); @endphp
            @if ($redirectToHomeAction && $homeUrl)
            <a id="redirect-button" href="{{ $homeUrl }}" class="flex items-center hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-full p-2 px-2.5 transition-colors shrink-0">
                      <x-wirechat::icon
                                :icon="$this->panel()->redirectToHomeActionIcon()"
                                 default="wirechat::icons.home-01"
                                class="size-5.5"
                                :icon-attributes="$this->panel()->redirectToHomeActionIconAttributes()"
                            />
            </a>
            @endif

            {{-- Panel-action:Create Chat Action--}}
            @if ($createChatAction)
            <x-wirechat::actions.new-chat widget="{{$this->isWidget()}}" panel="{{$this->panel}}" >
                <button id="open-new-chat-modal-button" class="hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-full p-2 px-2.5 transition-colors shrink-0 flex  items-center focus:outline-hidden">
                         <x-wirechat::icon
                                :icon="$this->panel()->createChatActionIcon()"
                                default="wirechat::icons.message-plus-circle"
                                class="size-6"
                                :icon-attributes="$this->panel()->createChatActionIconAttributes()"
                            />
                    </button>
            </x-wirechat::actions.new-chat>
            @endif

            {{-- Header Actions --}}
            @if ($hasHeaderMenuActions)
            <div class="ml-auto my-auto flex items-center">
                <x-wirechat::dropdown align="right" width="48">
                    <x-slot name="trigger" class="size-8 mt-1 flex items-center justify-center hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-full p-2 px-2.5 transition-colors shrink-0">
                        <button type="button" >
                               <x-wirechat::icon icon="wirechat::icons.ellipsis-vertical" class="size-7 " />
                         </button>
                    </x-slot>
                    <x-slot name="content">
                        {{-- Settings --}}
                        @if ($hasSettings)
                            <x-wirechat::actions.open-chats-drawer component="wirechat.chats.settings" widget="{{$this->isWidget()}}" panel="{{$this->panel}}">
                                <x-wirechat::dropdown-item icon="wirechat::icons.cog-6-tooth" id="open-settings-drawer-button" dusk="open-settings-drawer-button">
                                    <span>{{ __('wirechat::chats.settings.actions.open.label') }}</span>
                                </x-wirechat::dropdown-item>
                            </x-wirechat::actions.open-chats-drawer>
                        @endif

                        {{-- Message Requests --}}
                       @if ($hasMessageRequests)
                        <x-wirechat::actions.open-chats-drawer component="wirechat.chats.requests" widget="{{$this->isWidget()}}" panel="{{$this->panel}}">
                                <x-wirechat::dropdown-item icon="wirechat::icons.user"  id="open-requests-drawer-button">
                                <span>{{ __('wirechat::chats.requests.actions.open.label') }}</span>
                                    @if ($pendingMessageRequestsCount > 0)
                                        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-900 px-1.5 py-0.5 text-xs font-semibold text-white dark:bg-zinc-100 dark:text-zinc-900">
                                            {{ $pendingMessageRequestsCount }}
                                        </span>
                                    @endif
                            </x-wirechat::dropdown-item>
                        </x-wirechat::actions.open-chats-drawer>
                        @endif

                        {{-- New Group Action --}}
                        @if ($canCreateGroups)
                            <x-wirechat::actions.new-group widget="{{$this->isWidget()}}" panel="{{$this->panel}}">
                                    <x-wirechat::dropdown-item  dusk="open_new_group_modal_button_in_header" icon="wirechat::icons.untitledui-users-plus"  id="open-new-group-modal-button">
                                            <span>{{ __('wirechat::chats.actions.new_group.label') }}</span>
                                    </x-wirechat::dropdown-item>
                            </x-wirechat::actions.new-group>
                        @endif
                    </x-slot>
                </x-wirechat::dropdown>

            </div>
            @endif


        </div>



    </section>

    {{-- Search input --}}
    @if ($chatsSearch)
        <section>
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
