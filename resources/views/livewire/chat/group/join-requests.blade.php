<div class="min-h-screen w-full bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] text-gray-900 dark:text-white">
    <section class="sticky top-0 z-10 flex items-center gap-4 border-b border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] px-5 py-4">
        <button wire:click="$dispatch('closeChatDrawer')" class="focus:outline-hidden">
            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        
        </button>
        <h3 class="text-lg font-medium">Join Requests</h3>
    </section>

    <section class="mx-auto flex max-w-3xl flex-col gap-6 px-5 py-8 sm:px-8">
        <div class="space-y-3 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)]">
                    <x-wirechat::icons.user-group class="size-10" />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Review and handle everyone who asked to join this group through an invite link.</p>
        </div>

        <div class="space-y-3">
            @forelse ($requests as $request)
                @php
                    $meta = $request->data ?? [];
                    $requester = $request->requester;
                @endphp

                <div wire:key="join-request-{{ $request->id }}" class="rounded-3xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] px-5 py-4 shadow-sm">
                    <div class="flex items-start gap-4">
                        <x-wirechat::avatar :src="$requester?->wirechat_avatar_url" class="h-12 w-12 shrink-0" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium">{{ $requester?->wirechat_name ?: 'Unknown user' }}</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Requested {{ $request->created_at?->diffForHumans() }}
                                        @if (filled($meta['token'] ?? null))
                                            • via invite link
                                        @endif
                                    </p>
                                </div>

                                <span class="text-sm text-gray-400">{{ $request->created_at?->format('H:i') }}</span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3">
                                <button type="button" wire:click="approve({{ $request->id }})"
                                    class="inline-flex items-center justify-center rounded-2xl bg-[var(--wc-brand-primary)] px-4 py-2 text-sm font-medium text-white">
                                    Add To Group
                                </button>

                                <button type="button" wire:click="dismiss({{ $request->id }})"
                                    class="inline-flex items-center justify-center rounded-2xl border border-[var(--wc-light-border)] px-4 py-2 text-sm font-medium dark:border-[var(--wc-dark-border)]">
                                    Dismiss
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-[var(--wc-light-border)] px-5 py-12 text-center text-sm text-gray-500 dark:border-[var(--wc-dark-border)] dark:text-gray-400">
                    There are no pending join requests right now.
                </div>
            @endforelse
        </div>
    </section>
</div>
