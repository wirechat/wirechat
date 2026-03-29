<div class="w-[92vw] max-w-lg rounded-3xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-6 text-gray-900 shadow-xl dark:text-white">
    <div class="flex items-center justify-between gap-4">
        <button type="button" wire:click="closeWirechatModal" class="rounded-full p-2 text-gray-500 transition hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-semibold">Invite Link</h3>
        <span class="w-10"></span>
    </div>

    <div class="mt-6 space-y-5">
        <div class="rounded-2xl border border-[var(--wc-light-border)] p-4 dark:border-[var(--wc-dark-border)]">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Link</p>
            <p class="mt-3 break-all text-sm">{{ $inviteUrl }}</p>
        </div>

        <div class="rounded-2xl border border-[var(--wc-light-border)] p-4 dark:border-[var(--wc-dark-border)]">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Link Created By</p>
            <div class="mt-3 flex items-center gap-3">
                <x-wirechat::avatar :src="$invite->createdBy?->wirechat_avatar_url" class="h-12 w-12" />
                <div>
                    <p class="font-medium">{{ $invite->createdBy?->wirechat_name ?: 'Unknown' }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $invite->created_at?->format('M j, Y g:i A') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-[var(--wc-light-secondary)] px-4 py-3 dark:bg-[var(--wc-dark-secondary)]">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Uses</p>
                <p class="mt-2 font-medium">{{ $invite->usages }}</p>
            </div>
            <div class="rounded-2xl bg-[var(--wc-light-secondary)] px-4 py-3 dark:bg-[var(--wc-dark-secondary)]">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Limit</p>
                <p class="mt-2 font-medium">{{ $invite->limit ?: 'Unlimited' }}</p>
            </div>
            <div class="rounded-2xl bg-[var(--wc-light-secondary)] px-4 py-3 dark:bg-[var(--wc-dark-secondary)]">
                <p class="text-xs uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Expires</p>
                <p class="mt-2 font-medium">{{ $invite->expires_at?->diffForHumans() ?: 'Never' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <button type="button"
                x-data
                @click="if (navigator.clipboard) { navigator.clipboard.writeText(@js($inviteUrl)); $dispatch('wirechat-toast', { type: 'success', message: 'Invite link copied.' }); } else { window.prompt('Copy this link', @js($inviteUrl)); }"
                class="inline-flex items-center justify-center rounded-2xl bg-[var(--wc-brand-primary)] px-4 py-3 text-sm font-medium text-white">
                Copy Link
            </button>

            <button type="button"
                onclick="Livewire.dispatch('openWirechatModal', { component: 'wirechat.chat.group.links.send', arguments: { conversation: @js($conversation->id), invite: @js($invite->id), panel: @js($this->panel) } })"
                class="inline-flex items-center justify-center rounded-2xl border border-[var(--wc-light-border)] px-4 py-3 text-sm font-medium dark:border-[var(--wc-dark-border)]">
                Share Link
            </button>
        </div>

        @if ($canRevokeLink)
            <button type="button" wire:click="revokeLink" wire:confirm="Are you sure you want to revoke this invite link?"
                class="inline-flex w-full items-center justify-center rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-500 dark:border-red-900/60">
                Revoke Link
            </button>
        @endif
    </div>
</div>
