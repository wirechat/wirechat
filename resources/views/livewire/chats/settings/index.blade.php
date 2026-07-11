@php
    $auth = auth()->user();
@endphp

<div class="flex min-h-full flex-col bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
    <header class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50/95 px-4 py-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="flex items-center justify-between gap-3">
            <h2 class="truncate text-left text-lg font-semibold" dusk="settings-heading">
                {{ __('wirechat::chats.settings.heading') }}
            </h2>

            <button
                type="button"
                wire:click="closeChatListDrawer"
                class="inline-flex size-9 items-center justify-center rounded-full border border-zinc-200 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                aria-label="{{ __('wirechat::chats.settings.actions.close.label') }}"
            >
                <x-wirechat::icons.x class="size-5" />
            </button>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto px-4 py-5">
        <section class="space-y-3" aria-labelledby="settings-general-heading">
            <h2 id="settings-general-heading" dusk="settings-general-heading" class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ __('wirechat::chats.settings.general.heading') }}
            </h2>

            <div class="flex items-center gap-3 text-left">
                <x-wirechat::avatar :src="data_get($auth, 'wirechat_avatar_url')" class="size-14 shrink-0" />
                <div class="min-w-0">
                    <h3 class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ data_get($auth, 'wirechat_name') ?? data_get($auth, 'name') ?? __('wirechat::chats.settings.labels.profile') }}
                    </h3>
                    @if (filled(data_get($auth, 'wirechat_subtitle')))
                        <p class="mt-1 truncate text-sm text-zinc-500 dark:text-zinc-400">
                            {{ data_get($auth, 'wirechat_subtitle') }}
                        </p>
                    @endif
                </div>
            </div>
        </section>

        <section class="mt-7" aria-label="{{ __('wirechat::chats.settings.heading') }}">
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                <x-wirechat::actions.open-chats-drawer component="wirechat.chats.settings.notifications" widget="{{ $this->isWidget() }}" panel="{{ $this->panel }}" class="block">
                    <button
                        type="button"
                        dusk="settings-option-notifications"
                        class="flex w-full items-center justify-between gap-4 py-4 text-left"
                    >
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('wirechat::chats.settings.options.notifications.label') }}
                            </span>
                            <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('wirechat::chats.settings.options.notifications.description') }}
                            </span>
                        </span>
                        <x-wirechat::icons.chevron-right class="size-5 shrink-0 text-zinc-400 dark:text-zinc-500" />
                    </button>
                </x-wirechat::actions.open-chats-drawer>

                <x-wirechat::actions.open-chats-drawer component="wirechat.chats.settings.security-privacy" widget="{{ $this->isWidget() }}" panel="{{ $this->panel }}" class="block">
                    <button
                        type="button"
                        dusk="settings-option-security-privacy"
                        class="flex w-full items-center justify-between gap-4 py-4 text-left"
                    >
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('wirechat::chats.settings.options.security_privacy.label') }}
                            </span>
                            <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('wirechat::chats.settings.options.security_privacy.description') }}
                            </span>
                        </span>
                        <x-wirechat::icons.chevron-right class="size-5 shrink-0 text-zinc-400 dark:text-zinc-500" />
                    </button>
                </x-wirechat::actions.open-chats-drawer>
            </div>
        </section>
    </main>
</div>
