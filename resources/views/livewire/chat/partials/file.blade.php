@php
    $fileExtension = str($attachment->extension ?: 'file')->upper()->toString();
    $fileIconExtension = str($fileExtension)->limit(4, '')->toString();
    $usesSolidOutgoingTone = ($belongsToAuth ?? false) && ($hasSolidColorTone ?? false);
@endphp

<div dusk="message-file-attachment" class="grid w-72 max-w-full grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 overflow-hidden rounded-xl bg-white/30 p-3 dark:bg-zinc-900/10 sm:w-[26rem]">
    <span
        @class([
            'relative grid h-12 w-12 shrink-0 place-items-center',
            'text-white/80' => $usesSolidOutgoingTone,
            'text-zinc-500 dark:text-zinc-500/80' => ! $usesSolidOutgoingTone,
        ])
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-10 w-10">
            <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625Z" />
            <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
        </svg>
        <span dusk="message-file-extension"
            @class([
                'absolute inset-x-0 top-1/2 -translate-y-[12%] text-center text-[8px] font-bold leading-none',
                'text-zinc-700' => $usesSolidOutgoingTone,
                'text-white' => ! $usesSolidOutgoingTone,
            ])>
            {{ $fileIconExtension }}
        </span>
    </span>

    <div class="min-w-0">
        <p @class([
            'truncate text-sm font-medium',
            'text-white' => $usesSolidOutgoingTone,
            'text-zinc-900 dark:text-zinc-100' => ! $usesSolidOutgoingTone,
        ])>
            {{ $attachment->original_name }}
        </p>

        <p dusk="message-file-meta"
            @class([
                'mt-1 flex items-center gap-1.5 text-xs font-medium uppercase leading-none',
                'text-white/80' => $usesSolidOutgoingTone,
                'text-zinc-600 dark:text-zinc-300' => ! $usesSolidOutgoingTone,
            ])>
            <span>{{ $fileExtension }}</span>

            @if ($attachment->formatted_size)
                <span aria-hidden="true">&middot;</span>
                <span>{{ $attachment->formatted_size }}</span>
            @endif
        </p>
    </div>

    <button
        type="button"
        wire:loading.attr="disabled"
        wire:click="download(@js(encrypt($attachment->id)))"
        @class([
            'grid shrink-0 place-items-center rounded-full border p-2 transition-colors disabled:cursor-progress',
            'border-white/30 text-white/85 hover:text-white' => $usesSolidOutgoingTone,
            'border-zinc-800/20 text-gray-500/90 hover:text-[var(--wc-brand-primary)] dark:border-zinc-500/70 dark:border-zinc-300/40 dark:text-gray-400/90 dark:hover:text-[var(--wc-brand-primary)]/90' => ! $usesSolidOutgoingTone,
        ])
    >
     
    <svg class="size-6 stroke-[0.8]  " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" fill="currentColor"><path d="M227.7,134.47A4,4,0,0,0,224,132H180V48a12,12,0,0,0-12-12H88A12,12,0,0,0,76,48v84H32a4,4,0,0,0-2.83,6.83l96,96a4,4,0,0,0,5.66,0l96-96A4,4,0,0,0,227.7,134.47ZM128,226.34,41.66,140H80a4,4,0,0,0,4-4V48a4,4,0,0,1,4-4h80a4,4,0,0,1,4,4v88a4,4,0,0,0,4,4h38.34Z"></path></svg>

    </button>
</div>
