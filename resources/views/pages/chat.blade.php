@extends('wirechat::layouts.app')

@section('content')
    <div class="w-full flex min-h-full h-full rounded-lg">
        @persist('chats')
        <div class="{{ request()->conversation ? 'hidden md:grid' : 'grid' }} bg-inherit border-r border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] dark:bg-inherit relative w-full h-full md:w-[360px] lg:w-[400px] xl:w-[500px] shrink-0 overflow-y-auto">
            <livewire:wirechat.chats />
        </div>
        @endpersist

        <main class="{{ request()->conversation ? 'grid w-full grow' : 'hidden md:grid' }} h-full min-h-full {{ request()->route('conversation') ? '' : 'bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]' }} relative overflow-y-auto" style="contain:content">
            @if(request()->conversation)
                <livewire:wirechat.chat conversation="{{ request()->conversation }}" />
            @else
                <div class="m-auto text-center justify-center flex gap-3 flex-col items-center col-span-12">
                    <h4 class="font-medium p-2 px-3 rounded-full font-semibold bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] dark:text-white dark:font-normal">
                        @lang('wirechat::pages.chat.messages.welcome')
                    </h4>
                </div>
            @endif
        </main>
    </div>
@endsection
