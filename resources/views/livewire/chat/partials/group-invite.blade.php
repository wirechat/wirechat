<div class="-mx-1.5">

<div
    @class([
        ' -mt-1.5  mx-auto mb-2 w-full overflow-hidden rounded-lg',
         'opacity-90 bg-[color-mix(in_srgb,var(--primary-300)_40%,transparent)] text-zinc-900' => $belongsToAuth,
        ' text-zinc-900   bg-zinc-200/70 dark:bg-zinc-700/70 dark:text-white' => ! $belongsToAuth,
    ])>
    <div class="flex items-start gap-3 px-4 py-3">
        <x-wirechat::avatar :group="true" :src="$preview['cover_url']" class="size-12 shrink-0" />

        <div  
        @class([
                'min-w-0 flex-1',
                'text-white' => $belongsToAuth,
                'text-zinc-800 dark:text-zinc-200' => ! $belongsToAuth,
            ])
        >
            <p class="truncate text- font-semibold">
                {{ $preview['name'] }}
            </p>

            <p>
                {{ __('wirechat::chat.group.invite_message.labels.type') }}
            </p>
        </div>
    </div>
</div>
</div>
