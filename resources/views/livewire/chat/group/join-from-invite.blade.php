<div class=" max-w-xl rounded-2xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-6 text-gray-900 shadow-xl dark:text-white">
    <div class="flex items-center justify-between gap-4">
        <button type="button" wire:click="closeWirechatModal" class="rounded-full p-2 text-gray-500 transition hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-semibold">Join Group</h3>
        <span class="w-10"></span>
    </div>

    <div class="mt-6 space-y-6">
        <div class="flex items-start gap-4">
            <x-wirechat::avatar :src="$group?->cover_url" class="size-16 shrink-0" />

            <div class="w-full flex flex-col gap-1">
                <h4 class="text-xl font-semibold wrap-break-word">{{ $group?->name ?: 'Group' }}</h4>
                @if (filled($group?->description))
                <p class=" text-sm  text-gray-600 dark:text-gray-300">{{ $group->description }}</p>
                @endif
            </div>
        </div>
        <p class=" text-sm text-gray-500 dark:text-gray-400">{{ $conversation?->participants_count }} members</p>
        @if ($membersPreview->isNotEmpty())
            <div class="flex items-center overflow-x-auto  -space-x-5 pb-1">
                @foreach ($membersPreview as $participant)
                    <div class="flex  flex-col">
                        <x-wirechat::avatar :src="$participant->participantable?->wirechat_avatar_url" class="size-10" />
                    </div>
                @endforeach
            </div>
        @endif

        <div class="rounded-xl bg-[var(--wc-light-secondary)] px-4 py-3 text-sm leading-6 text-gray-700 dark:bg-[var(--wc-dark-secondary)] dark:text-gray-200">
            @if ($isMember)
                You are already a member of this group.
            @elseif ($joinBlocked)
                You cannot join this group with this invite right now.
            @elseif ($hasPendingJoinRequest)
                Your join request is already pending admin review.
            @elseif ($requiresApproval)
                This group requires admin approval before new members can join.
            @else
                You can join this group immediately.
            @endif
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    
            <x-wirechat::button
            variant="subtle"
            wire:click="closeWirechatModal"
            >
               Cancel
            </x-wirechat::button>
            <x-wirechat::button
                wire:click="proceed"
                wire:loading.attr="disabled"
                ::disabled="{{ ($joinBlocked || $hasPendingJoinRequest) }}"
                class="inline-flex items-center justify-center rounded-2xl bg-[var(--wc-brand-primary)] px-5 py-3 text-sm font-medium text-white">
                @if ($isMember)
                    Open Group
                @elseif ($hasPendingJoinRequest)
                    Request Pending
                @elseif ($requiresApproval)
                    Request To Join
                @else
                    Join Group
                @endif
            </x-wirechat::button>

         
        </div>
    </div>
</div>
