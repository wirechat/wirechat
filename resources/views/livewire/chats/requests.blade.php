<div class="flex min-h-full flex-col bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
    <header class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50/95 px-4 py-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="truncate text-left text-lg font-semibold">
                    {{ __('wirechat::chats.requests.heading') }}
                </h2>
                <p class="mt-1 text-left text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('wirechat::chats.requests.labels.description') }}
                </p>
            </div>

                <button
                    type="button"
                    wire:click="closeChatListDrawer"
                    class="inline-flex size-9 items-center justify-center rounded-full border border-zinc-200 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    aria-label="{{ __('wirechat::chats.requests.actions.close.label') }}"
                >
                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </header>

    <div class="flex-1 space-y-8 px-4 py-4">
        @php
            $incomingRequests = $this->incomingRequests;
            $outgoingRequests = $this->outgoingRequests;
            $hasRequests = $incomingRequests->isNotEmpty() || $outgoingRequests->isNotEmpty();
        @endphp

        @if (! $hasRequests)
            <div class="flex min-h-56 items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 text-center dark:border-zinc-700 dark:bg-zinc-950">
                <p class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('wirechat::chats.requests.labels.empty_state') }}
                </p>
            </div>
        @endif

        @if ($incomingRequests->isNotEmpty())
            <section class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-left text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('wirechat::chats.requests.labels.incoming') }}
                    </h3>
                    <span class="rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        {{ $incomingRequests->count() }}
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach ($incomingRequests as $request)
                        @php
                            $conversation = $request->conversation;
                            $peer = $request->sender;
                            $lastMessage = $conversation?->lastMessage;
                            $preview = $lastMessage?->body ?: __('wirechat::chats.requests.labels.no_message');
                        @endphp

                        <button
                            type="button"
                            wire:click="openConversation({{ $request->id }})"
                            class="flex w-full items-start gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-left transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700 dark:hover:bg-zinc-900"
                        >
                            <x-wirechat::avatar :src="$peer?->wirechat_avatar_url ?? null" class="size-11 shrink-0" />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $peer?->wirechat_name }}
                                    </h4>
                                    <span class="text-xs text-zinc-400">
                                        {{ optional($request->created_at)->diffForHumans() }}
                                    </span>
                                </div>

                                <p class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $preview }}
                                </p>
                            </div>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($outgoingRequests->isNotEmpty())
            <section class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-left text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ __('wirechat::chats.requests.labels.outgoing') }}
                    </h3>
                    <span class="rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        {{ $outgoingRequests->count() }}
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach ($outgoingRequests as $request)
                        @php
                            $conversation = $request->conversation;
                            $peer = $request->recipient;
                            $lastMessage = $conversation?->lastMessage;
                            $preview = $lastMessage?->body ?: __('wirechat::chats.requests.labels.no_message');
                        @endphp

                        <button
                            type="button"
                            wire:click="openConversation({{ $request->id }})"
                            class="flex w-full items-start gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-left transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700 dark:hover:bg-zinc-900"
                        >
                            <x-wirechat::avatar :src="$peer?->wirechat_avatar_url ?? null" class="size-11 shrink-0" />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $peer?->wirechat_name }}
                                    </h4>
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        {{ __('wirechat::chats.requests.labels.pending') }}
                                    </span>
                                </div>

                                <p class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $preview }}
                                </p>
                            </div>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
