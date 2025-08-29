<div id="info-modal" class="bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]      min-h-screen">


    <section class="flex gap-4 z-10  items-center p-5 sticky top-0 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]   ">
        <button wire:click="$dispatch('closeChatDrawer')" class="focus:outline-hidden cursor-pointer"> <svg class="w-7 h-7"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg> </button>
        <h3>{{ __('wirechat::chat.info.heading.label') }}</h3>
    </section>
    {{-- Details --}}

    <header>

            <div class="flex  flex-col items-center gap-5 ">

                <div class="mx-auto items-center justify-center grid">

                    <a href="{{ $receiver?->wirechat_profile_url }}">
                        <x-wirechat::avatar :src="$wirechat_avatar_url" class=" h-32 w-32 mx-auto" />
                    </a>
                </div>

                <div class=" grid  ">

                    <a class="px-8 py-5 " @dusk="receiver_name" href="{{ $receiver?->wirechat_profile_url }}">
                        <h5 class="text-2xl">{{ $receiver?->wirechat_name }}</h5>
                    </a>
                </div>

            </div>

    </header>



    <x-wirechat::divider />


    {{-- Footer section --}}
    <section class="flex flex-col justify-start w-full">

        {{-- Only show if is not group --}}
            <button wire:confirm="{{ __('wirechat::chat.info.actions.delete_chat.confirmation_message') }}" wire:click="deleteChat"
                class=" w-full cursor-pointer py-5 px-8 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] transition  flex gap-3 items-center text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6 w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>

                <span>{{ __('wirechat::chat.info.actions.delete_chat.label') }}</span>
            </button>

    </section>
</div>
