@php
    $appName = config('app.name', 'this app');
@endphp

<div class="flex min-h-full flex-col bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
    <header class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50/95 px-4 py-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="flex items-center gap-3">
            <button type="button" wire:click="closeChatListDrawer" class="inline-flex size-9 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" aria-label="{{ __('wirechat::chats.settings.actions.back.label') }}">
                <x-wirechat::icons.chevron-left class="size-5" />
            </button>
            <h2 class="truncate text-left text-lg font-semibold" dusk="settings-security-privacy-heading">
                {{ __('wirechat::chats.settings.security_privacy.heading') }}
            </h2>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto px-4 py-5">
        <section class="space-y-3 text-left" aria-labelledby="settings-security-privacy-groups-heading">
            <div class="space-y-1">
                <h3 id="settings-security-privacy-groups-heading" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100" dusk="settings-security-privacy-groups-heading">
                    {{ __('wirechat::chats.settings.security_privacy.groups.heading') }}
                </h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('wirechat::chats.settings.security_privacy.options.groups.description') }}
                </p>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                <div class="flex items-center justify-between gap-4 py-4 text-left">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ __('wirechat::chats.settings.security_privacy.groups.options.add_me.label') }}
                        </p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('wirechat::chats.settings.security_privacy.groups.options.add_me.description', ['app' => $appName]) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="relative h-6 w-11 shrink-0 rounded-full bg-zinc-300 transition dark:bg-zinc-700"
                        @style($groupsCanAddMe ? ['background-color: var(--wc-brand-primary)'] : [])
                        wire:click="toggleGroupsCanAddMe"
                        aria-pressed="{{ $groupsCanAddMe ? 'true' : 'false' }}"
                        dusk="settings-security-privacy-groups-add-me-toggle"
                    >
                        <span class="absolute left-0 top-0.5 size-5 rounded-full bg-white shadow transition" @style(['transform: translateX(1.375rem)' => $groupsCanAddMe, 'transform: translateX(0.125rem)' => ! $groupsCanAddMe])></span>
                    </button>
                </div>
            </div>
        </section>
    </main>
</div>
