@use('Namu\WireChat\Facades\WireChat')

@php
    $group = $conversation->group;
@endphp

<header
    class="w-full  sticky inset-x-0 flex pb-[5px] pt-[7px] top-0 z-10 dark:bg-[var(--wc-dark-secondary)] bg-[var(--wc-light-secondary)] border-[var(--wc-light-primary)] dark:border-[var(--wc-dark-secondary)]   border-b">

    <div class="  flex  w-full items-center   px-2 py-2   lg:px-4 gap-2 md:gap-5 ">

        {{-- Return --}}
        <a @if ($this->isWidget()) @click="$dispatch('close-chat',{conversation: {{json_encode($conversation->id)}} })"
            dusk="return_to_home_button_dispatch"
        @else
            href="{{ route(WireChat::indexRouteName(), $conversation->id) }}"
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
                    <x-wirechat::actions.show-group-info conversation="{{ $conversation->id }}"
                        widget="{{ $this->isWidget() }}">
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
                    <x-wirechat::actions.show-chat-info conversation="{{ $conversation->id }}"
                        widget="{{ $this->isWidget() }}">
                        <div class="flex items-center gap-2 cursor-pointer ">
                            <x-wirechat::avatar disappearing="{{ $conversation->hasDisappearingTurnedOn() }}"
                                :group="false" :src="$receiver?->cover_url ?? null"
                                class="h-8 w-8 lg:w-10 lg:h-10 " />
                            <h6 class="font-bold text-base text-gray-800 dark:text-white w-full truncate">
                                {{ $receiver?->display_name }} @if ($conversation->isSelfConversation())
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
                            <x-wirechat::dropdown-link href="{{ route(WireChat::indexRouteName()) }}" class="shrink-0">
                                @lang('wirechat::chat.actions.close_chat.label')
                            </x-wirechat::dropdown-link>
                        @endif


                        {{-- Only show delete and clear if conversation is NOT group --}}
                        @if (!$conversation->isGroup())
                            <button class="w-full" wire:click="clearConversation"
                                wire:confirm="{{ __('wirechat::chat.actions.clear_chat.confirmation_message') }}">

                                <x-wirechat::dropdown-link>
                                    @lang('wirechat::chat.actions.clear_chat.label')
                                </x-wirechat::dropdown-link>
                            </button>

                            <button wire:click="deleteConversation"
                                wire:confirm="{{ __('wirechat::chat.actions.delete_chat.confirmation_message') }}"
                                class="w-full text-start">

                                <x-wirechat::dropdown-link class="text-red-500 dark:text-red-500">
                                    @lang('wirechat::chat.actions.delete_chat.label')
                                </x-wirechat::dropdown-link>

                            </button>
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

</header>
