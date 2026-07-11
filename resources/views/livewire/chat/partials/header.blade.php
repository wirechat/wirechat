@use('Wirechat\Wirechat\Facades\Wirechat')

@php
    $group = $conversation->group;
    $pendingJoinRequestsCount = $conversation->isGroup() && $authParticipant?->isAdmin() && $this->panel()->hasGroupInvitations() && $group?->requiresInviteApproval() ? $group->pendingJoinRequests()->count(): 0;
    $hasMessageRequests = $this->panel()->hasMessageRequests();
    $hasActiveMessageRequest = $hasMessageRequests && $conversation->isPrivate() && $conversation->hasActiveMessageRequest();
    $conversationActionId = (string) $conversation->id;
    $chatsRoute = $this->panel()->chatsRouteIfRegistered();
@endphp

<header
    class="w-full sticky inset-x-0 top-0 z-10 flex flex-col bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-secondary)]">

    <div class="border-b border-zinc-200/80 dark:border-zinc-700/60 flex w-full items-center px-4 py-3.5 gap-2 md:gap-5">

        {{-- Return --}}
        @if ($this->isWidget() || $chatsRoute === null)
            <button
                type="button"
                aria-label="{{ __('wirechat::chat.actions.close_chat.label') }}"
                @click="$dispatch('close-chat', { conversation: @js($conversation->id) })"
                dusk="return_to_home_button_dispatch"
                class="shrink-0 cursor-pointer dark:text-white"
                id="chatReturn">
                <x-wirechat::icons.chevron-left />
            </button>
        @else
            <a wire:navigate
                href="{{ $chatsRoute }}"
                aria-label="{{ __('wirechat::chat.actions.close_chat.label') }}"
                dusk="return_to_home_button_link"
                class="shrink-0 cursor-pointer dark:text-white lg:hidden"
                id="chatReturn">
                <x-wirechat::icons.chevron-left />
            </a>
        @endif

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
                    @if ($hasActiveMessageRequest)
                        <div class="flex items-center gap-2">
                            <x-wirechat::avatar disappearing="{{ $conversation->hasDisappearingTurnedOn() }}"
                                :group="false" :src="$receiver?->wirechat_avatar_url ?? null"
                                class="h-8 w-8 lg:w-10 lg:h-10 " />
                            <h6 class="font-bold text-base text-gray-800 dark:text-white w-full truncate">
                                {{ $receiver?->wirechat_name }} @if ($conversation->isSelfConversation())
                                    ({{ __('wirechat::chat.labels.you') }})
                                @endif
                            </h6>
                        </div>
                    @else
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
                        @elseif (! $hasActiveMessageRequest)
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


                        @if ($this->isWidget() || $chatsRoute === null)
                            <x-wirechat::dropdown-link
                                data-conversation-id="{{ $conversationActionId }}"
                                @click="$dispatch('close-chat', { conversation: $el.dataset.conversationId })">
                                @lang('wirechat::chat.actions.close_chat.label')
                            </x-wirechat::dropdown-link>
                        @else
                            <x-wirechat::dropdown-link href="{{ $chatsRoute }}" class="shrink-0">
                                @lang('wirechat::chat.actions.close_chat.label')
                            </x-wirechat::dropdown-link>
                        @endif


                        {{-- Only show delete and clear if conversation is NOT group --}}
                        @if (!$conversation->isGroup() && ! $hasActiveMessageRequest)
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

    @if ($conversation->isGroup() && $authParticipant?->isAdmin() && $this->panel()->hasGroupInvitations())
        <div
            x-data="{
                key: @js('wirechat.join-requests-banner.' . $this->panel()->getId() . '.' . $conversation->id),
                conversationId: @js($conversation->id),
                pendingCount: @js($pendingJoinRequestsCount),
                summary: @js(trans_choice('wirechat::chat.group.join.requests.labels.summary', $pendingJoinRequestsCount, ['count' => $pendingJoinRequestsCount])),
                latestRequestId: @js($pendingJoinRequestsCount > 0 ? $conversation->group?->pendingJoinRequests()->latest('id')->value('id') : 0),
                joinRequestsBannerDismissed: false,
                ensureStore() {
                    if (window.__wirechatJoinRequestsBannerStoreRegistered) {
                        return;
                    }

                    Alpine.store('wirechatJoinRequestsBanner', {
                        ttlMs: 3600000,
                        getExpiry(key) {
                            try {
                                return Number(window.localStorage.getItem(key)) || 0;
                            } catch (error) {
                                return 0;
                            }
                        },
                        isDismissed(key) {
                            const expiresAt = this.getExpiry(key);

                            if (expiresAt > Date.now()) {
                                return true;
                            }

                            this.clear(key);

                            return false;
                        },
                        dismiss(key) {
                            try {
                                window.localStorage.setItem(key, String(Date.now() + this.ttlMs));
                            } catch (error) {}
                        },
                        clear(key) {
                            try {
                                window.localStorage.removeItem(key);
                            } catch (error) {}
                        },
                    });

                    window.__wirechatJoinRequestsBannerStoreRegistered = true;
                },
                dismissKey() {
                    return `${this.key}.${this.latestRequestId || 0}`;
                },
                init() {
                    this.ensureStore();
                    this.joinRequestsBannerDismissed = Alpine.store('wirechatJoinRequestsBanner').isDismissed(this.dismissKey());
                },
                handleBannerUpdate(detail = {}) {
                    if (Number(detail.conversationId) !== Number(this.conversationId)) {
                        return;
                    }

                    this.pendingCount = Number(detail.count ?? 0);
                    this.summary = detail.summary ?? this.summary;
                    this.latestRequestId = Number(detail.latestRequestId ?? 0);

                    if (this.pendingCount <= 0) {
                        Alpine.store('wirechatJoinRequestsBanner').clear(this.dismissKey());
                        this.joinRequestsBannerDismissed = false;

                        return;
                    }

                    this.joinRequestsBannerDismissed = Alpine.store('wirechatJoinRequestsBanner').isDismissed(this.dismissKey());
                },
                dismissBanner() {
                    this.ensureStore();
                    Alpine.store('wirechatJoinRequestsBanner').dismiss(this.dismissKey());
                    this.joinRequestsBannerDismissed = true;
                },
            }"
            x-cloak x-show="pendingCount > 0 && !joinRequestsBannerDismissed"
            x-on:wirechat-join-requests-banner-updated.window="handleBannerUpdate($event.detail)"
            class="border-zinc-100 bg-zinc-50 px-2 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/60 lg:px-4">
            <div class="mx-auto flex w-full items-center justify-between gap-3 rounded-2xl px-1 text-sm">
                <x-wirechat::actions.open-chat-drawer
                    component="wirechat.chat.group.join.requests"
                    :conversation="$conversation->id"
                    :panel="$this->panel"
                    class="min-w-0 flex-1"
                >
                    <button type="button" class="w-full text-left">
                        <div class="flex items-center gap-2">
                            <x-wirechat::icons.user-clock class="ml-1 size-5 dark:text-zinc-300" />

                            <span class="font-bold text-[var(--primary-500)]">
                                {{ __('wirechat::chat.group.join.requests.labels.review') }}
                            </span>

                            <span class="font-medium text-[var(--primary-500)]" x-text="pendingCount">
                                {{ $pendingJoinRequestsCount }}
                            </span>

                            <span class="truncate font-medium text-gray-700 dark:text-white/80" x-text="summary">
                                {{ trans_choice('wirechat::chat.group.join.requests.labels.summary', $pendingJoinRequestsCount, ['count' => $pendingJoinRequestsCount]) }}
                            </span>
                        </div>
                    </button>
                </x-wirechat::actions.open-chat-drawer>

                <button type="button" @click.stop="dismissBanner()"
                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-200/70 hover:text-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-700/70 dark:hover:text-white"
                    aria-label="{{ __('wirechat::chat.group.join.requests.actions.dismiss_banner.label') }}">
                    <x-wirechat::icons.x class="size-4" />
                </button>
            </div>
        </div>
    @endif

</header>
