<div class="min-h-screen w-full bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] text-gray-900 dark:text-white">
    <section class="sticky top-0 z-10 flex items-center gap-4 border-b border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] px-5 py-4">
        <button wire:click="$dispatch('closeChatDrawer')" class="focus:outline-hidden">
            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-medium">Invite Links</h3>
    </section>

    <section class="mx-auto flex max-w-3xl flex-col gap-6 px-5 py-8 sm:px-8">
        <div class="space-y-3 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)]">
                 <x-wirechat::icons.link class="size-7" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Anyone with an account will be able to open one of these links and join your group based on your access settings.</p>
        </div>

        <div class="rounded-2xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-secondary)]/40 dark:bg-[var(--wc-dark-secondary)]/40 p-5 shadow-sm">
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Primary Link</p>

            <div class="rounded-2xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-3">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1 rounded-2xl bg-[var(--wc-light-secondary)] px-4 py-3 text-sm break-all dark:bg-[var(--wc-dark-secondary)]">{{ $primaryInviteUrl }}</div>

                    <button type="button"
                        onclick="Livewire.dispatch('openWirechatModal', { component: 'wirechat.chat.group.invite-link-details', arguments: { conversation: @js($conversation->id), invite: @js($primaryInvite->id), panel: @js($this->panel) } })"
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-[var(--wc-light-secondary)] text-gray-500 transition hover:text-[var(--wc-brand-primary)] dark:bg-[var(--wc-dark-secondary)] dark:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-5 w-5">
                            <path d="M12 7a1.75 1.75 0 1 0 0-3.5A1.75 1.75 0 0 0 12 7Zm0 7a1.75 1.75 0 1 0 0-3.5A1.75 1.75 0 0 0 12 14Zm0 7a1.75 1.75 0 1 0 0-3.5A1.75 1.75 0 0 0 12 21Z" />
                        </svg>
                    </button>
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <button type="button"
                        x-data
                        @click="if (navigator.clipboard) { navigator.clipboard.writeText(@js($primaryInviteUrl)); $dispatch('wirechat-toast', { type: 'success', message: 'Invite link copied.' }); } else { window.prompt('Copy this link', @js($primaryInviteUrl)); }"
                        class="inline-flex items-center justify-center rounded-2xl bg-[var(--wc-brand-primary)] px-4 py-3 text-sm font-medium text-white">
                        Copy Link
                    </button>

                    <button type="button"
                        onclick="Livewire.dispatch('openWirechatModal', { component: 'wirechat.chat.group.send-invite-link', arguments: { conversation: @js($conversation->id), invite: @js($primaryInvite->id), panel: @js($this->panel) } })"
                        class="inline-flex items-center justify-center rounded-2xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] px-4 py-3 text-sm font-medium">
                        Share Link
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span>
                        @if ($primaryInvite->usages === 0)
                            Nobody joined yet
                        @elseif ($primaryInvite->limit)
                            {{ $primaryInvite->usages }} / {{ $primaryInvite->limit }} uses
                        @else
                            {{ $primaryInvite->usages }} joins so far
                        @endif
                    </span>

                    @if ($canResetLink)
                        <button type="button" wire:click="resetLink" class="font-medium text-[var(--wc-brand-primary)] hover:underline">
                            Reset Link
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Group Access</p>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        @if ($requiresAdminApproval)
                            People who open these links will need admin approval before they join.
                        @else
                            People who open these links can join immediately.
                        @endif
                    </p>
                </div>

                @if ($canEditGroupAccess)
                    <button type="button"
                        onclick="Livewire.dispatch('openChatDrawer', { component: 'wirechat.chat.group.permissions', arguments: { conversation: @js($conversation->id), panel: @js($this->panel) } })"
                        class="inline-flex items-center rounded-2xl border border-[var(--wc-light-border)] px-4 py-2 text-sm font-medium dark:border-[var(--wc-dark-border)]">
                        {{ __('wirechat::chat.group.invite_link.actions.edit_permissions.label') }}
                    </button>
                @endif
            </div>

            @if ($canManageJoinRequests)
                <div class="mt-4 rounded-2xl bg-[var(--wc-light-secondary)] px-4 py-3 dark:bg-[var(--wc-dark-secondary)]">
                    <button type="button"
                        onclick="Livewire.dispatch('openChatDrawer', { component: 'wirechat.chat.group.join-requests', arguments: { conversation: @js($conversation->id), panel: @js($this->panel) } })"
                        class="flex w-full items-center justify-between gap-3 text-left">
                        <div>
                            <p class="font-medium">Join Requests</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Review who asked to join this group.</p>
                        </div>

                        <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-[var(--wc-brand-primary)] px-3 py-1 text-sm font-semibold text-white">
                            {{ $pendingJoinRequestsCount }}
                        </span>
                    </button>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Additional Links</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create extra invite links with their own expiry and usage limits.</p>
                </div>

                <button type="button"
                    onclick="Livewire.dispatch('openWirechatModal', { component: 'wirechat.chat.group.create-invite-link', arguments: { conversation: @js($conversation->id), panel: @js($this->panel) } })"
                    class="inline-flex items-center rounded-2xl bg-[var(--wc-brand-primary)] px-4 py-2 text-sm font-medium text-white">
                    Create New Link
                </button>
            </div>

            <div class="space-y-3">
                @forelse ($additionalInvites as $invite)
                    <button type="button"
                        onclick="Livewire.dispatch('openWirechatModal', { component: 'wirechat.chat.group.invite-link-details', arguments: { conversation: @js($conversation->id), invite: @js($invite->id), panel: @js($this->panel) } })"
                        wire:key="additional-invite-{{ $invite->id }}"
                        class="flex w-full items-center gap-4 rounded-2xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] px-4 py-4 text-left transition hover:bg-[var(--wc-light-secondary)]/60 dark:hover:bg-[var(--wc-dark-secondary)]/60">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[var(--wc-light-secondary)] text-[var(--wc-brand-primary)] dark:bg-[var(--wc-dark-secondary)]"> 
                             <x-wirechat::icons.link class="size-5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $invite->name ?: $invite->token }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @if ($invite->limit)
                                    {{ $invite->usages }} / {{ $invite->limit }} uses
                                @else
                                    {{ $invite->usages }} uses
                                @endif
                                @if ($invite->expires_at)
                                    • Expires {{ $invite->expires_at->diffForHumans() }}
                                @else
                                    • Never expires
                                @endif
                            </p>
                        </div>

                        <span class="text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                            </svg>
                        </span>
                    </button>
                @empty
                    <div class="rounded-2xl border border-dashed border-[var(--wc-light-border)] px-5 py-8 text-center text-sm text-gray-500 dark:border-[var(--wc-dark-border)] dark:text-gray-400">
                        No extra links yet. Create one for a limited campaign, a temporary invite, or a private onboarding flow.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
