@extends(\Wirechat\Wirechat\Facades\Wirechat::currentPanel()->getLayout())

@section('content')
    <div class="min-h-full w-full flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-xl rounded-3xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex items-start gap-4">
                <x-wirechat::avatar :src="$group->cover_url" class="w-18 h-18 shrink-0" />

                <div class="min-w-0">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Invite to group</p>
                    <h1 class="text-2xl font-semibold break-words">{{ $group->name ?: 'Group' }}</h1>
                    @if (filled($group->description))
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300 break-words">{{ $group->description }}</p>
                    @endif
                </div>
            </div>

            <div class="space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">Members {{ $conversation->participants->count() }}</p>

                <div class="flex items-center gap-3 overflow-x-auto pb-2">
                    @foreach ($membersPreview as $participant)
                        <div class="flex flex-col items-center gap-2 min-w-[60px]">
                            <x-wirechat::avatar :src="$participant->participantable?->wirechat_avatar_url" class="w-12 h-12" />
                            <span class="text-xs text-center text-gray-500 dark:text-gray-400 truncate max-w-[70px]">{{ $participant->participantable?->wirechat_name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl px-4 py-3 bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] text-sm leading-6">
                @if ($isMember)
                    You are already a member of this group.
                @elseif ($joinBlocked)
                    You cannot join this group with this invite link right now.
                @elseif ($hasPendingJoinRequest)
                    Your join request is still pending. Continue in Wirechat to view it.
                @else
                    Continue in Wirechat to join this group or request access.
                @endif
            </div>

            <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                <button type="button"
                    onclick="window.history.length > 1 ? window.history.back() : window.location.href = @js(\Wirechat\Wirechat\Facades\Wirechat::currentPanel()->chatsRoute())"
                    class="inline-flex justify-center items-center rounded-2xl px-5 py-3 border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)]">
                    Cancel
                </button>

                @if ($isMember)
                    <a href="{{ \Wirechat\Wirechat\Facades\Wirechat::currentPanel()->chatRoute($conversation->id) }}"
                        class="w-full sm:w-auto inline-flex justify-center items-center rounded-2xl px-5 py-3 bg-[var(--wc-brand-primary)] text-white">
                        Open Group
                    </a>
                @else
                    <form method="POST" action="{{ \Wirechat\Wirechat\Facades\Wirechat::currentPanel()->inviteJoinRoute($invite->token) }}">
                        @csrf
                        <button type="submit"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-2xl px-5 py-3 bg-[var(--wc-brand-primary)] text-white">
                            Join Group
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
