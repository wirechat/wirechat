<div class="w-[92vw] max-w-lg rounded-xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-6 text-gray-900 shadow-xl dark:text-white">
    <div class="flex items-center justify-between gap-4">
        <button type="button" wire:click="closeWirechatModal" class="rounded-full p-2 text-gray-500 transition hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-semibold">{{ __('wirechat::chat.group.invite_link.show.heading.label') }}</h3>
        <span class="w-10"></span>
    </div>

    <div class="mt-6 space-y-5">
        @if ($inviteUrl)
            {{-- Invite Link --}}
            <div class="rounded-xl border border-dashed border-[var(--wc-light-border)] p-4 dark:border-[var(--wc-dark-border)]">
                <p class="text-sm   text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.show.labels.link') }}</p>
                <p class="mt-3 break-all text-[var(--primary-500)] text-sm">{{ $inviteUrl }}</p>
            </div>
        @endif

        {{-- Created By --}}
        <div class="rounded-xl border border-dashed border-[var(--wc-light-border)] p-4 dark:border-[var(--wc-dark-border)]">
            <p class="text-sm   text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.show.labels.created_by') }}</p>
            <div class="mt-3 flex items-center gap-3">
                <x-wirechat::avatar :src="$invite->createdBy?->wirechat_avatar_url" class="h-12 w-12" />
                <div>
                    <p class="font-medium">{{ $invite->createdBy?->wirechat_name ?: __('wirechat::chat.group.invite_link.show.labels.unknown') }}</p>
                    @if (filled($invite->createdBy?->wirechat_subtitle))
                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $invite->createdBy?->wirechat_subtitle }}</p>
                    @endif
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $invite->created_at?->format('M j, Y g:i A') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border dark:border-zinc-700 border-dashed p-3 flex flex-col">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.show.labels.uses') }}</p>
                <p class="mt-2 text-sm font-medium">{{ $invite->usages }}</p>
            </div>
            <div class="rounded-xl border dark:border-zinc-700 border-dashed p-3 flex flex-col">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.show.labels.limit') }}</p>
                <p class="mt-2 text-sm font-medium">{{ $invite->limit ?: __('wirechat::chat.group.invite_link.show.labels.unlimited') }}</p>
            </div>
            <div class="rounded-xl border dark:border-zinc-700 border-dashed p-3 flex flex-col">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.show.labels.expires') }}</p>
                <p class="mt-2 text-sm font-medium">{{ $invite->expires_at?->diffForHumans() ?: __('wirechat::chat.group.invite_link.show.labels.never') }}</p>
            </div>
        </div>

        @if ($inviteUrl)
            <div class="grid grid-cols-1 max-h-fit gap-3 sm:grid-cols-2">
                <x-wirechat::actions.copy
                    :value="$inviteUrl"
                    :success-message="__('wirechat::chat.group.invite_link.show.messages.copied_success')"
                    :prompt-message="__('wirechat::chat.group.invite_link.show.messages.copy_prompt')"
                    class="w-full"
                >
                    <x-wirechat::button
                        type="button"
                        size="sm"
                        variant="default"
                        full-width
                    >
                        {{ __('wirechat::chat.group.invite_link.show.actions.copy_link.label') }}
                    </x-wirechat::button>
                </x-wirechat::actions.copy>

                <x-wirechat::actions.open-modal
                    component="wirechat.chat.group.links.send"
                    :conversation="$conversation->id"
                    :panel="$this->panel"
                    :arguments="['invite' => $invite->id]"
                    class="w-full"
                >
                    <x-wirechat::button
                        type="button"
                        variant="outline"
                        full-width
                    >
                        {{ __('wirechat::chat.group.invite_link.show.actions.share_link.label') }}
                    </x-wirechat::button>
                </x-wirechat::actions.open-modal>
            </div>
        @endif

        @if ($canRevokeLink)
            <button type="button" wire:click="revokeLink" wire:confirm="{{ __('wirechat::chat.group.invite_link.show.messages.revoke_confirmation') }}"
                class="inline-flex w-full items-center justify-center rounded-xl border border-red-200 px-4 py-3 text-sm font-medium text-red-500 dark:border-red-900/60">
                {{ __('wirechat::chat.group.invite_link.show.actions.revoke.label') }}
            </button>
        @endif
    </div>
</div>
