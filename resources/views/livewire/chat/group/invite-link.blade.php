<div class="bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] min-h-screen w-full">
    <section class="flex gap-4 z-10 items-center p-5 sticky top-0 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]">
        <button wire:click="$dispatch('closeChatDrawer')" class="focus:outline-hidden">
            <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3>{{ __('wirechat::chat.group.invite_link.heading.label') }}</h3>
    </section>

    <section class="px-6 py-6 flex gap-4 items-start">
        <x-wirechat::avatar :src="$group?->cover_url" class="w-16 h-16 shrink-0" />

        <div class="min-w-0">
            <h4 class="text-2xl font-semibold break-words">{{ $group?->name }}</h4>
            <a href="{{ $inviteUrl }}" target="_blank" class="text-sm sm:text-base break-all text-[var(--wc-brand-primary)]">{{ $inviteUrl }}</a>
        </div>
    </section>

    <section class="px-6 py-4">
        <div class="flex gap-4 items-start">
            <span class="w-10 pt-1 text-gray-500 dark:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632ZM18 8.25h.008v.008H18V8.25Zm0 3.75h.008v.008H18V12Zm0 3.75h.008v.008H18v-.008Z" />
                </svg>
            </span>

            <div class="space-y-2">
                <p class="text-sm sm:text-base leading-6">
                    @if ($requiresAdminApproval)
                        {{ __('wirechat::chat.group.invite_link.labels.admin_approval_enabled') }}
                    @else
                        {{ __('wirechat::chat.group.invite_link.labels.admin_approval_disabled') }}
                    @endif
                </p>

                @if ($canResetLink)
                    <button type="button"
                        onclick="Livewire.dispatch('openChatDrawer', { component: 'wirechat.chat.group.permissions', arguments: { conversation: @js($conversation->id), panel: @js($this->panel) } })"
                        class="text-sm text-[var(--wc-brand-primary)] hover:underline">
                        {{ __('wirechat::chat.group.invite_link.actions.edit_permissions.label') }}
                    </button>
                @endif
            </div>
        </div>
    </section>

    <x-wirechat::divider />

    <section class="py-2">
        <button type="button"
            onclick="Livewire.dispatch('openWirechatModal', { component: 'wirechat.chat.group.send-invite-link', arguments: { conversation: @js($conversation->id), invite: @js($invite->id), panel: @js($this->panel) } })"
            class="cursor-pointer w-full py-5 px-8 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] transition text-start flex gap-3 items-center">
            <span class="text-gray-500 dark:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3h5.25m-5.25 3h9M3.75 5.25A2.25 2.25 0 0 1 6 3h12a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V5.25Z" />
                </svg>
            </span>
            <span>{{ __('wirechat::chat.group.invite_link.actions.send_via_chat.label') }}</span>
        </button>

        <button type="button"
            x-data
            @click="if (navigator.clipboard) { navigator.clipboard.writeText(@js($inviteUrl)); $dispatch('wirechat-toast', { type: 'success', message: @js(__('wirechat::chat.group.invite_link.messages.copied_success')) }); } else { window.prompt('Copy this link', @js($inviteUrl)); }"
            class="cursor-pointer w-full py-5 px-8 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] transition text-start flex gap-3 items-center">
            <span class="text-gray-500 dark:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125H6.375a1.125 1.125 0 0 1-1.125-1.125V8.625c0-.621.504-1.125 1.125-1.125H9.75m6-4.5h2.625c.621 0 1.125.504 1.125 1.125v12c0 .621-.504 1.125-1.125 1.125h-9.75A1.125 1.125 0 0 1 7.5 16.125V6.75c0-.621.504-1.125 1.125-1.125H15.75Z" />
                </svg>
            </span>
            <span>{{ __('wirechat::chat.group.invite_link.actions.copy_link.label') }}</span>
        </button>

        @if ($canResetLink)
            <button type="button" wire:click="resetLink"
                class="cursor-pointer w-full py-5 px-8 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] transition text-start flex gap-3 items-center">
                <span class="text-gray-500 dark:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865A8.25 8.25 0 0 1 17.834 6.165l3.181 3.182" />
                    </svg>
                </span>
                <span>{{ __('wirechat::chat.group.invite_link.actions.reset_link.label') }}</span>
            </button>
        @endif
    </section>
</div>
