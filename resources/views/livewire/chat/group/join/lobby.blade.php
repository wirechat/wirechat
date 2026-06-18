<div class=" max-w-xl rounded-2xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-6 text-gray-900 shadow-xl dark:text-white">
    <div class="flex items-center justify-between gap-4">
        <button type="button" wire:click="closeWirechatModal" class="rounded-full p-2 text-gray-500 transition hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 autofocus tabindex="-1" class="text-lg font-semibold focus:outline-hidden">{{ __('wirechat::chat.group.join.lobby.heading.label') }}</h3>
        <span class="w-10"></span>
    </div>

    <div class="mt-6 space-y-6">
        <div class="flex items-start gap-4">
            <x-wirechat::avatar :src="$group?->cover_url" class="size-16 shrink-0" />

            <div class="w-full flex flex-col gap-1">
                <h4 class="text-xl font-semibold wrap-break-word">{{ $group?->name ?: __('wirechat::chat.group.join.lobby.labels.default_group_name') }}</h4>
                @if (filled($group?->description))
                <p class=" text-sm  text-gray-600 dark:text-gray-300">{{ $group->description }}</p>
                @endif
            </div>
        </div>
        <p wire:ignore class=" text-sm text-gray-500 dark:text-gray-400">
            {{ trans_choice('wirechat::chat.group.join.lobby.labels.members_count', $conversation?->participants_count ?? 0, ['count' => $conversation?->participants_count ?? 0]) }}
        </p>
        @if ($membersPreview->isNotEmpty())
            <div class="flex items-center overflow-x-auto  -space-x-5 pb-1">
                @foreach ($membersPreview as $participant)
                    <div class="flex  flex-col">
                        <x-wirechat::avatar :src="$participant->participantable?->wirechat_avatar_url" class="size-10" />
                    </div>
                @endforeach
                @if ($remainingMembersCount > 0)
                    <div
                        aria-label="{{ trans_choice('wirechat::chat.group.join.lobby.labels.more_members', $remainingMembersCount, ['count' => $remainingMembersCount]) }}"
                        class="flex ml-6 size-10 shrink-0 items-center justify-center rounded-full border border-[var(--wc-light-border)] bg-[var(--wc-light-secondary)] text-xs font-semibold text-gray-700 dark:border-[var(--wc-dark-border)] dark:bg-[var(--wc-dark-secondary)] dark:text-gray-200"
                    >
                        +{{ number_format($remainingMembersCount) }}
                    </div>
                @endif
            </div>
        @endif

        <div
            @class([
                'rounded-xl px-4 py-3 text-sm leading-6',
                'bg-[var(--wc-light-secondary)] text-gray-700 dark:bg-[var(--wc-dark-secondary)] dark:text-gray-200' => ! $joinBlocked,
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800/50' => $joinBlocked,
            ])>
            @if ($isMember)
                {{ __('wirechat::chat.group.join.lobby.labels.already_member') }}
            @elseif ($joinBlocked)
                {{ __('wirechat::chat.group.join.lobby.labels.join_blocked') }}
            @elseif ($hasPendingJoinRequest)
                {{ __('wirechat::chat.group.join.lobby.labels.pending_review') }}
            @elseif ($requiresApproval)
                {{ __('wirechat::chat.group.join.lobby.labels.requires_approval') }}
            @else
                {{ __('wirechat::chat.group.join.lobby.labels.open_access') }}
            @endif
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    
            <x-wirechat::button
            variant="subtle"
            wire:click="closeWirechatModal"
            >
               {{ __('wirechat::chat.group.join.lobby.actions.cancel.label') }}
            </x-wirechat::button>
            @if (! $joinBlocked)
                <x-wirechat::button
                    wire:click="proceed"
                    wire:loading.attr="disabled"
                    size="sm"
                    >
                    @if ($isMember)
                        {{ __('wirechat::chat.group.join.lobby.actions.open_group.label') }}
                    @elseif ($hasPendingJoinRequest)
                        {{ __('wirechat::chat.group.join.lobby.actions.request_pending.label') }}
                    @elseif ($requiresApproval)
                        {{ __('wirechat::chat.group.join.lobby.actions.request_to_join.label') }}
                    @else
                        {{ __('wirechat::chat.group.join.lobby.actions.join_group.label') }}
                    @endif
                </x-wirechat::button>
            @endif

         
        </div>
    </div>
</div>
