<div class="min-h-screen w-full bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] text-gray-900 dark:text-white">
    <section class="sticky top-0 z-10 flex items-center gap-4 border-b border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] px-5 py-4">
        <button wire:click="$dispatch('closeChatDrawer')" class="focus:outline-hidden">
            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        
        </button>
        <h3 class="text-lg font-medium">{{ __('wirechat::chat.group.join.requests.heading.label') }}</h3>
    </section>

    <section class="mx-auto flex max-w-3xl flex-col gap-6 px-5 py-6 sm:px-8">
        <div class="space-y-2 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.join.requests.labels.description') }}</p>

            <x-wirechat::loading-spin wire:loading.delay />
        </div>

        <div>
            @if ($requests->isNotEmpty())
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--wc-light-border)] pb-3 dark:border-[var(--wc-dark-border)]">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ trans_choice('wirechat::chat.group.join.requests.labels.count', $pendingRequestsCount, ['count' => $pendingRequestsCount]) }}
                    </p>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <button
                            type="button"
                            wire:click="dismissAll"
                            wire:loading.attr="disabled"
                            wire:confirm="{{ __('wirechat::chat.group.join.requests.actions.dismiss_all.confirmation_message') }}"
                            class="inline-flex disabled:cursor-not-allowed disabled:opacity-80 transition-all text-red-500 items-center justify-center rounded-md px-3 py-2 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-500/10">
                            {{ __('wirechat::chat.group.join.requests.actions.dismiss_all.label') }}
                        </button>

                        <button
                            type="button"
                            wire:click="approveAll"
                            wire:loading.attr="disabled"
                            wire:confirm="{{ __('wirechat::chat.group.join.requests.actions.approve_all.confirmation_message') }}"
                            class="inline-flex disabled:cursor-not-allowed disabled:opacity-80 transition-all items-center justify-center rounded-md px-3 py-2 text-sm font-medium text-[var(--wc-brand-primary)] hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]">
                            {{ __('wirechat::chat.group.join.requests.actions.approve_all.label') }}
                        </button>
                    </div>

                </div>
            @endif

            <div class="divide-y divide-[var(--wc-light-border)] dark:divide-[var(--wc-dark-border)]">
                @forelse ($requests as $request)
                    @php
                        $meta = $request->data ?? [];
                        $requester = $request->requester;
                    @endphp

                    <div wire:key="join-request-{{ $request->id }}" class="py-4">
                    <div class="flex items-start gap-3">
                        <x-wirechat::avatar :src="$requester?->wirechat_avatar_url" class="h-12 w-12 shrink-0" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 text-start">
                                    <p class="font-medium">{{ $requester?->wirechat_name ?: __('wirechat::chat.group.join.requests.labels.unknown_user') }}</p>
                                    @if (filled($requester?->wirechat_subtitle))
                                        <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">
                                            {{ $requester?->wirechat_subtitle }}
                                        </p>
                                    @endif
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request->created_at?->diffForHumans() }}
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="ml-auto flex shrink-0 items-center gap-2 self-center">
                          
                            <button
                                 wire:loading.attr="disabled"
                                type="button"
                                wire:click="dismiss(@js($request->id))"
                                class="inline-flex
                                 size-9 items-center disabled:cursor-not-allowed disabled:opacity-80 transition-all justify-center rounded-md text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-500/10"
                                aria-label="{{ __('wirechat::chat.group.join.requests.actions.dismiss.label') }}"
                                title="{{ __('wirechat::chat.group.join.requests.actions.dismiss.label') }}">
                                <x-wirechat::icons.x class="size-5 !text-current dark:!text-current" />
                            </button>

                            <button
                                type="button"
                                     wire:loading.attr="disabled"
                                wire:click="approve(@js($request->id))"
                                class="inline-flex disabled:cursor-not-allowed disabled:opacity-80 transition-all size-9 items-center justify-center rounded-md text-[var(--primary-700)] hover:bg-[var(--primary-50)] dark:text-[var(--primary-300)] dark:hover:bg-[color-mix(in_srgb,var(--primary-700)_20%,transparent)]"
                                aria-label="{{ __('wirechat::chat.group.join.requests.actions.approve.label') }}"
                                title="{{ __('wirechat::chat.group.join.requests.actions.approve.label') }}">
                                <x-wirechat::icons.check class="size-5 !text-current" />
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                    <div class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('wirechat::chat.group.join.requests.labels.empty_state') }}
                    </div>
                @endforelse
            </div>

            @if ($hasMoreRequests)
                <div class="flex justify-center pt-2">
                    <button
                        wire:loading.attr="disabled"
                        type="button"
                        wire:click="loadMore"
                        class="inline-flex disabled:cursor-not-allowed disabled:opacity-80 items-center justify-center rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-[var(--wc-light-secondary)] dark:text-gray-200 dark:hover:bg-[var(--wc-dark-secondary)]">
                        {{ __('wirechat::chat.group.join.requests.actions.load_more.label') }}
                    </button>
                </div>
            @endif
        </div>
    </section>
</div>
