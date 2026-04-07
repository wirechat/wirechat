<div class="flex min-h-full flex-col bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
    <header class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50/95 px-4 py-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="truncate text-left text-lg font-semibold" dusk="requests-heading">
                    {{ __('wirechat::chats.requests.heading') }}
                </h2>
                <p class="mt-1 text-left text-sm text-zinc-500 dark:text-zinc-400" dusk="requests-description">
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

    @php
        $incomingRequests = $this->incomingRequests;
        $outgoingRequests = $this->outgoingRequests;
        $currentRequests = $this->currentRequests;
        $hasRequests = $this->hasRequests;
        $isIncomingTab = $activeTab === 'incoming';
    @endphp

    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
        <div class="grid grid-cols-2 gap-2 rounded-2xl bg-zinc-100 p-1 dark:bg-zinc-800/80" role="tablist" aria-label="{{ __('wirechat::chats.requests.heading') }}" dusk="requests-tabs">
            <button
                type="button"
                wire:click="setActiveTab('incoming')"
                dusk="incoming-requests-tab"
                @class([
                    'rounded-xl px-3 py-2 text-sm font-medium transition',
                    'bg-white text-zinc-900 shadow-sm dark:bg-zinc-950 dark:text-zinc-100' => $isIncomingTab,
                    'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ! $isIncomingTab,
                ])
                aria-pressed="{{ $isIncomingTab ? 'true' : 'false' }}"
            >
                <span>{{ __('wirechat::chats.requests.labels.incoming') }}</span>
                <span class="ml-2 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-200 px-1.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    {{ $incomingRequests->count() }}
                </span>
            </button>

            <button
                type="button"
                wire:click="setActiveTab('outgoing')"
                dusk="outgoing-requests-tab"
                @class([
                    'rounded-xl px-3 py-2 text-sm font-medium transition',
                    'bg-white text-zinc-900 shadow-sm dark:bg-zinc-950 dark:text-zinc-100' => ! $isIncomingTab,
                    'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $isIncomingTab,
                ])
                aria-pressed="{{ ! $isIncomingTab ? 'true' : 'false' }}"
            >
                <span>{{ __('wirechat::chats.requests.labels.outgoing') }}</span>
                <span class="ml-2 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-200 px-1.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    {{ $outgoingRequests->count() }}
                </span>
            </button>
        </div>
    </div>

    <div class="flex-1 px-4 py-4">
        @if (! $hasRequests)
            <div class="flex min-h-56 items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 text-center dark:border-zinc-700 dark:bg-zinc-950" dusk="requests-empty-state">
                <p class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('wirechat::chats.requests.labels.empty_state') }}
                </p>
            </div>
        @elseif ($currentRequests->isEmpty())
            <div class="flex min-h-56 items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-white px-6 text-center dark:border-zinc-700 dark:bg-zinc-950" dusk="tab-empty-state">
                <p class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $this->currentEmptyState }}
                </p>
            </div>
        @else
            <div class="space-y-2">
                @foreach ($currentRequests as $request)
                    @php
                        $conversation = $request->conversation;
                        $peer = $isIncomingTab ? $request->sender : $request->recipient;
                        $lastMessage = $conversation?->lastMessage;
                        $preview = $lastMessage?->body ?: __('wirechat::chats.requests.labels.no_message');
                    @endphp

                    <button
                        type="button"
                        wire:click="openConversation({{ $request->id }})"
                        @class([
                            'flex w-full items-start gap-3 rounded-2xl border px-4 py-3 text-left transition',
                            'border-zinc-200 bg-white hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700 dark:hover:bg-zinc-900',
                        ])
                    >
                        <x-wirechat::avatar :src="$peer?->wirechat_avatar_url ?? null" class="size-11 shrink-0" />

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $peer?->wirechat_name }}
                                </h4>

                                @if ($isIncomingTab)
                                    <span class="text-xs text-zinc-400">
                                        {{ optional($request->created_at)->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        {{ __('wirechat::chats.requests.labels.pending') }}
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $preview }}
                            </p>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</div>
