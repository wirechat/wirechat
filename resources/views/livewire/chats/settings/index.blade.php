@php
    $auth = auth()->user();
    $notificationRows = [
        ['key' => 'messages', 'property' => 'messages'],
        ['key' => 'groups', 'property' => 'groups'],
        ['key' => 'previews', 'property' => 'previews'],
    ];
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
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('wirechat::chats.settings.general.profile.description') }}
                    </p>
                </div>
            </div>
        </section>

        <section class="mt-7 space-y-3" aria-labelledby="settings-notifications-heading">
            <div class="space-y-1">
                <h2 id="settings-notifications-heading" dusk="settings-notifications-heading" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('wirechat::chats.settings.notifications.heading') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('wirechat::chats.settings.notifications.description') }}
                </p>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($notificationRows as $row)
                    <div class="flex items-center justify-between gap-4 py-4 text-left">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('wirechat::chats.settings.notifications.options.'.$row['key'].'.label') }}
                            </h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('wirechat::chats.settings.notifications.options.'.$row['key'].'.description') }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="relative h-6 w-11 shrink-0 rounded-full bg-zinc-300 transition dark:bg-zinc-700"
                            @style($this->{$row['property']} ? ['background-color: var(--wc-brand-primary)'] : [])
                            wire:click="toggleNotificationSetting('{{ $row['property'] }}')"
                            aria-pressed="{{ $this->{$row['property']} ? 'true' : 'false' }}"
                            dusk="settings-notifications-{{ $row['key'] }}-toggle"
                        >
                            <span class="absolute left-0 top-0.5 size-5 rounded-full bg-white shadow transition" @style(['transform: translateX(1.375rem)' => $this->{$row['property']}, 'transform: translateX(0.125rem)' => ! $this->{$row['property']}])></span>
                        </button>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-7 space-y-3" aria-labelledby="settings-security-privacy-heading">
            <div class="space-y-1">
                <h2 id="settings-security-privacy-heading" dusk="settings-security-privacy-heading" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('wirechat::chats.settings.security_privacy.heading') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('wirechat::chats.settings.security_privacy.description') }}
                </p>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                <div class="py-4 text-left">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100" dusk="settings-security-privacy-groups-heading">
                            {{ __('wirechat::chats.settings.security_privacy.groups.heading') }}
                        </h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('wirechat::chats.settings.security_privacy.options.groups.description') }}
                        </p>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ __('wirechat::chats.settings.security_privacy.groups.options.add_me.label') }}
                            </p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('wirechat::chats.settings.security_privacy.groups.options.add_me.description') }}
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
            </div>
        </section>
    </main>
</div>
