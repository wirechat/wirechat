<div x-cloak @class(['w-[5%] justify-end min-w-max  items-center gap-2 '])>

    {{--  Submit button --}}
    <button
            x-show="((body?.trim()?.length>0) ||  $wire.media.length > 0 || $wire.files.length > 0 )"
            wire:loading.attr="disabled" wire:target="sendMessage" type="submit"
            id="sendMessageButton" class="cursor-pointer hover:text-[var(--wc-brand-primary)] transition-color ml-auto disabled:cursor-progress cursor-pointer font-bold">

        <svg class="w-7 h-7   dark:text-gray-200" xmlns="http://www.w3.org/2000/svg"
             width="36" height="36" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
             stroke-linejoin="round" class="ai ai-Send">
            <path
                    d="M9.912 12H4L2.023 4.135A.662.662 0 0 1 2 3.995c-.022-.721.772-1.221 1.46-.891L22 12 3.46 20.896c-.68.327-1.464-.159-1.46-.867a.66.66 0 0 1 .033-.186L3.5 15" />
        </svg>

    </button>



    {{-- send Like button --}}
    @if($this->panel()->hasHeart())

        <button
                x-show="!((body?.trim()?.length>0) || $wire.media.length > 0 || $wire.files.length > 0 )"
                wire:loading.attr="disabled" wire:target="sendMessage" wire:click='sendLike()'
                dusk="heart-button"
                type="button" class="hover:scale-105 transition-transform cursor-pointer group disabled:cursor-progress">

            <!-- outlined heart -->
            <span class=" group-hover:hidden transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     class="w-7 h-7 text-gray-600 dark:text-white/90 stroke-[1.4] dark:stroke-[1.4]">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </span>
            <!--  filled heart -->
            <span class="hidden group-hover:block transition " x-bounce>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                     class="size-6 w-7 h-7   text-red-500">
                                    <path
                                            d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                                </svg>
                            </span>

        </button>
    @endif
</div>
