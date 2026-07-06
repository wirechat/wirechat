<div class="h-[calc(100vh_-_8rem)] rounded-xl sm:h-[450px] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] dark:text-white border border-zinc-200 dark:border-zinc-700 overflow-y-auto overflow-x-hidden">
    <header class="sticky top-0 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] z-10 p-2">
        <div class="flex items-center pb-2">
            <x-wirechat::actions.close-modal>
                <button class="p-2 ml-0 text-gray-600 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] dark:hover:text-white rounded-full hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </button>
            </x-wirechat::actions.close-modal>

            <h3 class="text-sm mx-auto font-semibold">{{ __('wirechat::chat.group.past_members.heading.label') }}</h3>
        </div>

        <section class="flex flex-wrap items-center px-0 border-b border-zinc-200 dark:border-zinc-700">
            <input type="search" wire:model.live.debounce="search" autocomplete="off"
                placeholder="{{ __('wirechat::chat.group.past_members.inputs.search.placeholder') }}"
                class="wc-input w-full border-0 p-1 w-auto dark:bg-none dark:bg-transparent outline-hidden focus:outline-hidden bg-none rounded-lg focus:ring-0 hover:ring-0">
        </section>
    </header>

    <div class="relative w-full p-2">
        <section class="my-4">
            @if ($pastMembers->isEmpty())
                <div class="flex min-h-32 items-center justify-center text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('wirechat::chat.group.past_members.labels.no_results') }}</p>
                </div>
            @else
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($pastMembers as $pastMember)
                        @php
                            $reason = $pastMember->pastMembershipReason();
                            $reasonLabel = match ($reason) {
                                'blocked' => __('wirechat::chat.group.past_members.labels.reason_blocked'),
                                'removed' => __('wirechat::chat.group.past_members.labels.reason_removed'),
                                default => __('wirechat::chat.group.past_members.labels.reason_left'),
                            };
                            $atLabel = $pastMember->pastMembershipAt()?->diffForHumans();
                        @endphp

                        <li class="py-4" wire:key="past-member-{{ $pastMember->id }}">
                            <div class="flex items-start gap-3">
                                <x-wirechat::avatar :src="$pastMember->participantable?->wirechat_avatar_url" class="w-10 h-10 shrink-0" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ $pastMember->participantable?->wirechat_name }}</p>
                                    @if (filled($pastMember->participantable?->wirechat_subtitle))
                                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                                            {{ $pastMember->participantable?->wirechat_subtitle }}</p>
                                    @endif
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $reasonLabel }}</p>
                                    @if ($atLabel)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.past_members.labels.at', ['time' => $atLabel]) }}</p>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($canLoadMore)
                    <section class="w-full justify-center flex my-3">
                        <button type="button" wire:click="loadMore"
                            class="text-sm dark:text-white hover:text-gray-700 transition-colors dark:hover:text-gray-500 dark:gray-200">
                            {{ __('wirechat::chat.group.members.actions.load_more.label') }}
                        </button>
                    </section>
                @endif
            @endif
        </section>
    </div>
</div>
