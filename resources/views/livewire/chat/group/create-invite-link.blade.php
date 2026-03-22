<div class=" max-w-xl rounded-3xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-6 text-gray-900 shadow-xl dark:text-white">
    <div class="flex items-center justify-between gap-4">
        <button type="button" wire:click="closeWirechatModal" class="rounded-full p-2 text-gray-500 transition hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-semibold">New Link</h3>
        <span class="w-10"></span>
    </div>

    <div class="mt-6 space-y-6">
        <div>
            <input type="text" wire:model.live="name" maxlength="120" placeholder="Link Name (Optional)"
                class="wc-input w-full rounded-2xl border border-[var(--wc-light-border)] bg-[var(--wc-light-primary)] px-4 py-3 text-base dark:border-[var(--wc-dark-border)] dark:bg-[var(--wc-dark-primary)]">
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Only admins will see this name.</p>
            @error('name')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Limit By Time Period</p>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach (['1_hour' => '1 hour', '1_day' => '1 day', '1_week' => '1 week', 'never' => 'Never'] as $value => $label)
                    <button type="button" wire:click="$set('expiryPreset', '{{ $value }}')"
                        @class([
                            'rounded-2xl border px-3 py-3 text-sm font-medium transition',
                            'border-[var(--wc-brand-primary)] bg-[var(--wc-brand-primary)] text-white' => $expiryPreset === $value,
                            'border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)]' => $expiryPreset !== $value,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @error('expiryPreset')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Limit By Number Of Users</p>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach (['1' => '1', '10' => '10', '50' => '50', '100' => '100', 'unlimited' => 'Unlimited'] as $value => $label)
                    <button type="button" wire:click="$set('usagePreset', '{{ $value }}')"
                        @class([
                            'rounded-2xl border px-3 py-3 text-sm font-medium transition',
                            'border-[var(--wc-brand-primary)] bg-[var(--wc-brand-primary)] text-white' => $usagePreset === $value,
                            'border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)]' => $usagePreset !== $value,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @error('usagePreset')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-2xl bg-[var(--wc-light-secondary)] px-4 py-3 text-sm text-gray-600 dark:bg-[var(--wc-dark-secondary)] dark:text-gray-300">
            Approval still follows the group settings. Public groups can join directly, while private or approval-only groups will create join requests.
        </div>

        <button type="button" wire:click="createLink" wire:loading.attr="disabled"
            class="inline-flex w-full items-center justify-center rounded-2xl bg-[var(--wc-brand-primary)] px-4 py-3 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-70">
            Create
        </button>
    </div>
</div>
