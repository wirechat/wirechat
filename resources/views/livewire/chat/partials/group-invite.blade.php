@php
    $hasVisibleSenderName = $hasVisibleSenderName ?? false;
    $usesSolidOutgoingTone = ($belongsToAuth ?? false) && ($hasSolidColorTone ?? false);
@endphp

<div @class([
    '-mx-1.5',
    'mt-1.5' => $hasVisibleSenderName,
])>
    <div
        dusk="group-invite-preview"
        @class([
            'mx-auto mb-2 w-full max-w-full overflow-hidden rounded-lg text-start',
            '-mt-1.5' => ! $hasVisibleSenderName,
            'bg-white/10 text-white dark:bg-white/10 dark:text-white' => $usesSolidOutgoingTone,
            'bg-white/50 text-black dark:bg-[color-mix(in_srgb,var(--primary-300)_40%,transparent)] dark:text-white' => $belongsToAuth && ! $usesSolidOutgoingTone,
            'bg-zinc-200/70 text-zinc-900 dark:bg-zinc-700/70 dark:text-white' => ! $belongsToAuth,
        ])>
        <div class="flex flex-row items-start gap-3 px-4 py-3">
            <x-wirechat::avatar :group="true" :src="$preview['cover_url']" class="size-12 shrink-0" />

            <div
                @class([
                    'min-w-0 flex-1 text-start',
                    'text-white' => $usesSolidOutgoingTone,
                    'text-black dark:text-white' => $belongsToAuth && ! $usesSolidOutgoingTone,
                    'text-zinc-800 dark:text-zinc-200' => ! $belongsToAuth,
                ])>
                <p class="truncate text-sm font-semibold leading-5">
                    {{ $preview['name'] }}
                </p>

                <p class="mt-0.5 text-xs leading-4 opacity-80">
                    {{ __('wirechat::chat.group.invite_message.labels.type') }}
                </p>
            </div>
        </div>
    </div>
</div>
