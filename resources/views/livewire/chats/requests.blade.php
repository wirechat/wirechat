<div class="flex min-h-full flex-col bg-zinc-50 text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
    <header class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50/95 px-4 py-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="truncate text-left text-lg font-semibold" dusk="requests-heading">
                    {{ __('wirechat::chats.requests.heading') }}
                </h2>
                <p class="mt-0.5 truncate text-left text-sm text-zinc-500 dark:text-zinc-400" dusk="requests-description">
                    {{ __('wirechat::chats.requests.labels.description') }}
                </p>
            </div>

            <button
                type="button"
                wire:click="closeChatListDrawer"
                class="inline-flex size-9 shrink-0 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                aria-label="{{ __('wirechat::chats.requests.actions.close.label') }}"
            >
                <x-wirechat::icons.x class="size-5" />
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

    <div class="border-b border-zinc-200 px-4 dark:border-zinc-800">
        <div
            class="flex gap-5"
            role="tablist"
            aria-label="{{ __('wirechat::chats.requests.heading') }}"
            dusk="requests-tabs"
        >
            <button
                type="button"
                wire:click="setActiveTab('incoming')"
                dusk="incoming-requests-tab"
                @class([
                    'inline-flex min-h-10 items-center gap-2 border-b-2 text-sm font-medium transition',
                    'border-zinc-900 text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' => $isIncomingTab,
                    'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ! $isIncomingTab,
                ])
                aria-pressed="{{ $isIncomingTab ? 'true' : 'false' }}"
            >
                <span>{{ __('wirechat::chats.requests.labels.incoming') }}</span>
                <span @class([
                    'text-xs',
                    'text-zinc-700 dark:text-zinc-300' => $isIncomingTab,
                    'text-zinc-400 dark:text-zinc-500' => ! $isIncomingTab,
                ])>{{ $incomingRequests->count() }}</span>
            </button>

            <button
                type="button"
                wire:click="setActiveTab('outgoing')"
                dusk="outgoing-requests-tab"
                @class([
                    'inline-flex min-h-10 items-center gap-2 border-b-2 text-sm font-medium transition',
                    'border-zinc-900 text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' => ! $isIncomingTab,
                    'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $isIncomingTab,
                ])
                aria-pressed="{{ ! $isIncomingTab ? 'true' : 'false' }}"
            >
                <span>{{ __('wirechat::chats.requests.labels.outgoing') }}</span>
                <span @class([
                    'text-xs',
                    'text-zinc-700 dark:text-zinc-300' => ! $isIncomingTab,
                    'text-zinc-400 dark:text-zinc-500' => $isIncomingTab,
                ])>{{ $outgoingRequests->count() }}</span>
            </button>
        </div>
    </div>

    <main class="flex-1 overflow-y-auto px-4">
        @if (! $hasRequests)
            <div class="flex min-h-40 items-center justify-center text-center" dusk="requests-empty-state">
                <p class="max-w-64 text-sm font-normal leading-5 text-zinc-500 dark:text-zinc-400">
                    {{ __('wirechat::chats.requests.labels.empty_state') }}
                </p>
            </div>
        @elseif ($currentRequests->isEmpty())
            <div class="flex min-h-40 items-center justify-center text-center" dusk="tab-empty-state">
                <p class="max-w-64 text-sm font-normal leading-5 text-zinc-500 dark:text-zinc-400">
                    {{ $this->currentEmptyState }}
                </p>
            </div>
        @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($currentRequests as $request)
                    @php
                        $conversation = $request->conversation;
                        $peer = $isIncomingTab ? $request->sender : $request->recipient;
                        $lastMessage = $conversation?->lastMessage;
                        $preview = $lastMessage?->body ?: __('wirechat::chats.requests.labels.no_message');
                    @endphp

                    <button
                        type="button"
                        wire:click="openConversation(@js($request->id))"
                        class="flex w-full items-start gap-3 py-3.5 text-left transition hover:bg-zinc-100/70 dark:hover:bg-zinc-800/60"
                    >
                        <x-wirechat::avatar :src="$peer?->wirechat_avatar_url ?? null" class="size-10 shrink-0" />

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $peer?->wirechat_name }}
                                </h4>

                                @if ($isIncomingTab)
                                    <span class="shrink-0 text-xs text-zinc-400">
                                        {{ optional($request->created_at)->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="shrink-0 text-xs text-zinc-400 dark:text-zinc-500">
                                        {{ __('wirechat::chats.requests.labels.pending') }}
                                    </span>
                                @endif
                            </div>

                            @if (filled($peer?->wirechat_subtitle))
                                <p class="mt-1 truncate text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $peer?->wirechat_subtitle }}
                                </p>
                            @endif

                            <p class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $preview }}
                            </p>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </main>
</div>
