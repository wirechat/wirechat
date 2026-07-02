<div class="min-h-screen w-full bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] text-gray-900 dark:text-white">
    <section class="sticky top-0 z-10 flex items-center gap-4 border-b dark:border-zinc-700 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] px-5 py-4">
        <button wire:click="$dispatch('closeChatDrawer')" class="focus:outline-hidden">
            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-medium">{{ __('wirechat::chat.group.invite_link.heading.label') }}</h3>
    </section>

    <section class="mx-auto flex max-w-3xl flex-col gap-6 px-5 py-8 sm:px-8">
        <div class="space-y-3 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)]">
                <x-wirechat::icons.link class="size-7" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.labels.description') }}</p>
        </div>

        <x-wirechat::section
            :title="__('wirechat::chat.group.invite_link.labels.primary_link')"
            class="dark:border-zinc-700 b p-5"
        >
            <div class="rounded-xl border border-dashed dark:border-zinc-700 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-3">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1 rounded-xl bg-[var(--wc-light-secondary)] px-4 py-3 text-sm break-all dark:bg-[var(--wc-dark-secondary)]">{{ $primaryInviteUrl }}</div>

                    @if ($canManageInvites)
                        <button type="button"
                            x-data
                            x-on:click="Livewire.dispatch('openWirechatModal', { component: 'wirechat.chat.group.links.show', arguments: { conversation: @js($conversation->id), invite: @js($primaryInvite->id), panel: @js($this->panel) } })"
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-[var(--wc-light-secondary)] text-gray-500 transition hover:text-[var(--wc-brand-primary)] dark:bg-[var(--wc-dark-secondary)] dark:text-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                                <path d="M12 7a1.75 1.75 0 1 0 0-3.5A1.75 1.75 0 0 0 12 7Zm0 7a1.75 1.75 0 1 0 0-3.5A1.75 1.75 0 0 0 12 14Zm0 7a1.75 1.75 0 1 0 0-3.5A1.75 1.75 0 0 0 12 21Z" />
                            </svg>
                        </button>
                    @endif
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <x-wirechat::button
                        x-data="{
                            inviteUrl: @js($primaryInviteUrl),
                            copySuccessMessage: @js(__('wirechat::chat.group.invite_link.messages.copied_success')),
                            copyPrompt: @js(__('wirechat::chat.group.invite_link.messages.copy_prompt')),
                            copyInviteLink() {
                                if (navigator.clipboard) {
                                    navigator.clipboard.writeText(this.inviteUrl);
                                    this.$dispatch('wirechat-toast', { type: 'success', message: this.copySuccessMessage });

                                    return;
                                }

                                window.prompt(this.copyPrompt, this.inviteUrl);
                            },
                        }"
                        x-on:click="copyInviteLink()"
                        class="items-center flex gap-2"
                    >
                        {{ __('wirechat::chat.group.invite_link.actions.copy_link.label') }}

                        
                      <x-wirechat::icons.clipboard-document class="size-4" />


                    </x-wirechat::button>

                    <x-wirechat::button
                        variant="outline"
                        x-data
                        x-on:click="Livewire.dispatch('openWirechatModal', { component: 'wirechat.chat.group.links.send', arguments: { conversation: @js($conversation->id), invite: @js($primaryInvite->id), panel: @js($this->panel) } })"
                    >
                        {{ __('wirechat::chat.group.invite_link.actions.send_via_chat.label') }}
                    </x-wirechat::button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span>
                        @if ($primaryInvite->usages === 0)
                            {{ __('wirechat::chat.group.invite_link.labels.primary_link_usage_empty') }}
                        @elseif ($primaryInvite->limit)
                            {{ __('wirechat::chat.group.invite_link.labels.primary_link_usage_limited', ['usages' => $primaryInvite->usages, 'limit' => $primaryInvite->limit]) }}
                        @else
                            {{ __('wirechat::chat.group.invite_link.labels.primary_link_usage_total', ['usages' => $primaryInvite->usages]) }}
                        @endif
                    </span>

                    @if ($canResetLink)
                        <x-wirechat::button variant="link" wire:click="resetLink">
                            {{ __('wirechat::chat.group.invite_link.actions.reset_link.label') }}
                        </x-wirechat::button>
                    @endif
                </div>
            </div>
        </x-wirechat::section>
        {{------------------}}
        {{-- Group Access --}}
        {{------------------}}
        <x-wirechat::section
            :title="__('wirechat::chat.group.invite_link.labels.group_access')"
            class=" dark:border-zinc-700 p-5"
        >

            @if ($canManageJoinRequests)
                <div class="rounded-xl bg-[var(--wc-light-secondary)] px-4 py-3 dark:bg-[var(--wc-dark-secondary)]">
                    <button type="button"
                        x-data
                        x-on:click="Livewire.dispatch('openChatDrawer', { component: 'wirechat.chat.group.join.requests', arguments: { conversation: @js($conversation->id), panel: @js($this->panel) } })"
                        class="flex w-full items-center justify-between gap-3 text-left">
                        <div>
                            <p class="font-medium">{{ __('wirechat::chat.group.invite_link.labels.join_requests') }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.labels.join_requests_helper') }}</p>
                        </div>

                        <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-[var(--wc-brand-primary)] px-3 py-1 text-sm font-semibold text-white">
                            {{ $pendingJoinRequestsCount }}
                        </span>
                    </button>
                </div>
            @endif

            <div class="flex gap-2 text-sm text-start items-center">
             <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                @if ($requiresAdminApproval)
                    {{ __('wirechat::chat.group.invite_link.labels.group_access_requires_approval') }}
                @else
                    {{ __('wirechat::chat.group.invite_link.labels.group_access_open') }}
                @endif
            </p>

            @if ($canEditGroupAccess)
                <x-wirechat::button
                    x-data
                    x-on:click="Livewire.dispatch('openChatDrawer', { component: 'wirechat.chat.group.permissions', arguments: { conversation: @js($conversation->id), panel: @js($this->panel) } })"
                    variant="link">
                                {{ __('wirechat::chat.group.invite_link.actions.edit_permissions.label') }}
                </x-wirechat::button>
            @endif
            </div>

        </x-wirechat::section>

        {{------------------}}
        {{-- Group links --}}
        {{------------------}}
        @if ($canManageInvites)
            <livewire:wirechat.chat.group.links.list
                :conversation="$conversation"
                :panel="$this->panel"
                :key="'group-additional-links-'.$conversation->getKey().'-'.$this->panel"
            />
        @endif
    </section>
</div>
