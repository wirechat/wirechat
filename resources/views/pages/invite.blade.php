@extends(\Wirechat\Wirechat\Facades\Wirechat::currentPanel()->getInvitePageLayout())

@section('content')

    <div class="min-h-full dark:text-white w-full flex items-center justify-center px-4 py-8">

        <div class=" max-w-xl  w-full text-center items-center  rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex flex-col items-center justify-center gap-4">
                <x-wirechat::avatar group :src="$group->cover_url" class="size-20 shrink-0 " />

                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold break-words">{{ $group->name ?: __('wirechat::chat.group.invite_link.page.labels.group_fallback') }}</h1>

                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300 break-words">{{ __('wirechat::chat.group.invite_link.page.labels.invite_title') }}</p>
                </div>
            </div>


            <p class="text-sm">{{ __('wirechat::chat.group.invite_link.page.messages.invited_to_join_at', ['app' => config('app.name')]) }}</p>


            <div class="flex flex-col gap-3 justify-center">
                <form method="POST" action="{{ \Wirechat\Wirechat\Facades\Wirechat::currentPanel()->inviteJoinRoute($invite->token) }}">
                    @csrf
                    <button type="submit"
                        class="w-full w-full inline-flex justify-center items-center rounded-xl px-5 py-3 bg-[var(--primary-500)] hover:opacity-85 transition-all dark:text-white">
                        {{ __('wirechat::chat.group.invite_link.page.actions.continue.label') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
