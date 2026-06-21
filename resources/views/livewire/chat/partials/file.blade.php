<div dusk="message-file-attachment" class="grid w-72 max-w-full grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 overflow-hidden rounded-xl bg-white/60 p-2 dark:bg-white/5 sm:w-[26rem]">
    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white/70 text-gray-500 dark:bg-zinc-800/70 dark:text-zinc-300">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7">
            <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625Z" />
            <path d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
        </svg>
    </span>

    <p class="min-w-0 truncate text-sm font-medium text-gray-700 dark:text-gray-100">
        {{ $attachment->original_name }}
    </p>

    <a
        download="{{ $attachment->original_name }}"
        href="{{ $attachment?->url }}"
        class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/70 text-gray-600 transition-colors hover:text-blue-500 dark:bg-zinc-800/70 dark:text-white dark:hover:text-blue-400"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="h-5 w-5" viewBox="0 0 16 16">
            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5" />
            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z" />
        </svg>
    </a>
</div>
