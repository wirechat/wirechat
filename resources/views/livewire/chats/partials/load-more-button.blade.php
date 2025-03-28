<section wire:loading.remove wire:target="search" class="w-full justify-center flex my-3 ">
    <button wire:loading.remove wire:target="loadMore" wire:loading.attr="disabled"
        dusk="loadMoreButton" @click="$wire.loadMore()"
        class="  text-sm dark:text-white disabled:hover:cursor-not-allowed hover:text-gray-700 transition-colors dark:hover:text-gray-500 dark:gray-200">
        @lang('wirechat::chats.labels.load_more')
    </button>

    <div wire:loading wire:target="loadMore">
        <x-wirechat::loading-spin />
    </div>
</section>