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


                 <x-wirechat::icon
                                :icon="$this->panel()->deleteChatActionIcon()"
                                 default="wirechat::icons.trash"
                                :icon-attributes="$this->panel()->deleteChatActionIconAttributes()->merge(['class' => 'size-6 text-red-500 dark:text-red-500'])" 
                            />
                <span>{{ __('wirechat::chat.info.actions.delete_chat.label') }}</span>
            </button>

    </section>
</div>
