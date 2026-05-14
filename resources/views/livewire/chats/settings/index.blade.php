@php
    $auth = auth()->user();
    $settingsItems = [
        [
            'key' => 'notifications',
            'component' => 'wirechat.chats.settings.notifications',
            'icon' => 'wirechat::icons.bell',
            'label' => __('wirechat::chats.settings.options.notifications.label'),
            'description' => __('wirechat::chats.settings.options.notifications.description'),
        ],
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

    <div class="border-b border-zinc-200 px-4 py-5 dark:border-zinc-800">
        <div class="flex items-center gap-3 text-left">
            <x-wirechat::avatar :src="data_get($auth, 'wirechat_avatar_url')" class="size-14 shrink-0" />
            <div class="min-w-0">
                <h3 class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ data_get($auth, 'wirechat_name') ?? data_get($auth, 'name') ?? __('wirechat::chats.settings.labels.profile') }}
                </h3>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-2 py-3" aria-label="{{ __('wirechat::chats.settings.heading') }}">
        <div class="space-y-1">
            @foreach ($settingsItems as $item)
                <x-wirechat::actions.open-chats-drawer :component="$item['component']" widget="{{ $this->isWidget() }}" panel="{{ $this->panel }}">
                    <button
                        type="button"
                        dusk="settings-option-{{ $item['key'] }}"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-left transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                    >
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                            <x-wirechat::icon :icon="$item['icon']" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $item['label'] }}
                            </span>
                            <span class="mt-0.5 block truncate text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $item['description'] }}
                            </span>
                        </span>
                        <x-wirechat::icons.chevron-right class="size-5 shrink-0 text-zinc-400" />
                    </button>
                </x-wirechat::actions.open-chats-drawer>
            @endforeach
        </div>
    </nav>
</div>
