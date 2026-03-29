@use('Wirechat\Wirechat\Facades\Wirechat')

@php
    $group = $conversation->group;
    $pendingJoinRequestsCount = $conversation->isGroup() && $authParticipant?->isAdmin() && $this->panel()->hasGroupInvitations() ? $conversation->group?->pendingJoinRequests()->count(): 0;
@endphp

<header
    class="w-full sticky inset-x-0 top-0 z-10 flex flex-col  bg-[var(--wc-light-primary)]  dark:border-[var(--wc-dark-secondary)] dark:bg-[var(--wc-dark-secondary)]">

    <div class="  border-b border-zinc-200/80 flex  w-full items-center   px-2 py-2   lg:px-4 gap-2 md:gap-5 ">

        {{-- Return --}}
        <a wire:navigate @if ($this->isWidget()) @click="$dispatch('close-chat',{conversation: {{json_encode($conversation->id)}} })"
            dusk="return_to_home_button_dispatch"
        @else
            href="{{ $this->panel()->chatsRoute() }}"
            dusk="return_to_home_button_link" @endif
            @class([
                'shrink-0  cursor-pointer dark:text-white',
                'lg:hidden' => !$this->isWidget(),
            ]) id="chatReturn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>

        {{-- Receiver wirechat::Avatar --}}
        <section class="grid grid-cols-12 w-full">
            <div class="shrink-0 col-span-11 w-full truncate overflow-h-hidden relative">

                {{-- Group --}}
                @if ($conversation->isGroup())
                    <x-wirechat::actions.show-group-info 
                        conversation="{{ $conversation->id }}"
                        widget="{{ $this->isWidget() }}"
                         panel="{{$this->panel}}"
                        >
                        <div class="flex items-center gap-2 cursor-pointer ">
                            <x-wirechat::avatar disappearing="{{ $conversation->hasDisappearingTurnedOn() }}"
                                :group="true" :src="$group?->cover_url ?? null "
                                class="h-8 w-8 lg:w-10 lg:h-10 " />
                            <h6 class="font-bold text-base text-gray-800 dark:text-white w-full truncate">
                                {{ $group?->name }}
                            </h6>
                        </div>
                    </x-wirechat::actions.show-group-info>
                @else
                    {{-- Not Group --}}
                    <x-wirechat::actions.show-chat-info 
                    conversation="{{ $conversation->id }}"
                        widget="{{ $this->isWidget() }}"
                        panel="{{$this->panel}}">
                        <div class="flex items-center gap-2 cursor-pointer ">
                            <x-wirechat::avatar disappearing="{{ $conversation->hasDisappearingTurnedOn() }}"
                                :group="false" :src="$receiver?->wirechat_avatar_url ?? null"
                                class="h-8 w-8 lg:w-10 lg:h-10 " />
                            <h6 class="font-bold text-base text-gray-800 dark:text-white w-full truncate">
                                {{ $receiver?->wirechat_name }} @if ($conversation->isSelfConversation())
                                    ({{ __('wirechat::chat.labels.you') }})
                                @endif
                            </h6>
                        </div>
                    </x-wirechat::actions.show-chat-info>
                @endif


            </div>

            {{-- Header Actions --}}
            <div class="flex gap-2 items-center ml-auto col-span-1">
                <x-wirechat::dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="cursor-pointer inline-flex px-0 text-gray-700 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.9" stroke="currentColor" class="size-6 w-7 h-7">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                            </svg>

                        </button>
                    </x-slot>
                    <x-slot name="content">


                        @if ($conversation->isGroup())
                            {{-- Open group info button --}}
                            <x-wirechat::actions.show-group-info conversation="{{ $conversation->id }}"
                                widget="{{ $this->isWidget() }}">
                                <button class="w-full text-start">
                                    <x-wirechat::dropdown-link>
                                        {{ __('wirechat::chat.actions.open_group_info.label') }}
                                    </x-wirechat::dropdown-link>
                                </button>
                            </x-wirechat::actions.show-group-info>
                        @else
                            {{-- Open chat info button --}}
                            <x-wirechat::actions.show-chat-info conversation="{{ $conversation->id }}"
                                widget="{{ $this->isWidget() }}">
                                <button class="w-full text-start">
                                    <x-wirechat::dropdown-link>
                                        {{ __('wirechat::chat.actions.open_chat_info.label') }}
                                    </x-wirechat::dropdown-link>
                                </button>
                            </x-wirechat::actions.show-chat-info>
                        @endif


                        @if ($this->isWidget())
                            <x-wirechat::dropdown-link @click="$dispatch('close-chat',{conversation: {{json_encode($conversation->id)}} })">
                                @lang('wirechat::chat.actions.close_chat.label')
                            </x-wirechat::dropdown-link>
                        @else
                            <x-wirechat::dropdown-link href="{{ $this->panel()->chatsRoute()  }}" class="shrink-0">
                                @lang('wirechat::chat.actions.close_chat.label')
                            </x-wirechat::dropdown-link>
                        @endif


                        {{-- Only show delete and clear if conversation is NOT group --}}
                        @if (!$conversation->isGroup())
                            @if($this->panel()->hasClearChatAction())
                            <button dusk="clear-chat-action" class="w-full" wire:click="clearConversation"
                                wire:confirm="{{ __('wirechat::chat.actions.clear_chat.confirmation_message') }}">

                                <x-wirechat::dropdown-link>
                                    @lang('wirechat::chat.actions.clear_chat.label')
                                </x-wirechat::dropdown-link>
                            </button>
                            @endif

                           @if($this->panel()->hasDeleteChatAction())
                            <button dusk="delete-chat-action" wire:click="deleteConversation"
                                wire:confirm="{{ __('wirechat::chat.actions.delete_chat.confirmation_message') }}"
                                class="w-full text-start">

                                <x-wirechat::dropdown-link class="text-red-500 dark:text-red-500">
                                    @lang('wirechat::chat.actions.delete_chat.label')
                                </x-wirechat::dropdown-link>

                            </button>
                           @endif

                        @endif


                        @if ($conversation->isGroup() && !$this->auth->isOwnerOf($conversation))
                            <button wire:click="exitConversation"
                                wire:confirm="{{ __('wirechat::chat.actions.exit_group.confirmation_message') }}"
                                class="w-full text-start ">

                                <x-wirechat::dropdown-link class="text-red-500 dark:text-gray-500">
                                    @lang('wirechat::chat.actions.exit_group.label')
                                </x-wirechat::dropdown-link>

                            </button>
                        @endif

                    </x-slot>
                </x-wirechat::dropdown>

            </div>
        </section>


    </div>

    @if ($pendingJoinRequestsCount > 0)
        <div class="px-2 lg:px-4   border-zinc-100 shadow-sm bg-zinc-50 dark:border-zinc-600 py-3 dark:bg-zinc-800/60">
            <button type="button"
                onclick="Livewire.dispatch('openChatDrawer', { component: 'wirechat.chat.group.join.requests', arguments: { conversation: @js($conversation->id), panel: @js($this->panel) } })"
                class="mx-auto flex w-full items-center justify-between gap-3 rounded-2xl   px-1  text-sm">
               
              <div class="flex items-center gap-3">
                <x-wirechat::icons.user-group class="size-5 ml-1 dark:text-zinc-300" />
                  <span class="font-medium text-[var(--wc-brand-primary)]">
                    {{ __('wirechat::chat.group.join.requests.heading.label') }}
                </span>

              </div>

                <span class="flex items-center gap-3">
                    <span class="inline-flex w-8 h-6 bg-primary-500  items-center justify-center rounded-full shrink-0 px-3 py-1 text-xs font-semibold text-white">
                        {{ $pendingJoinRequestsCount }}
                    </span>
                </span>
            </button>
        </div>
    @endif

</header>
