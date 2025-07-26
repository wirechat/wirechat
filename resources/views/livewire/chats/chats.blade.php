@use('Namu\WireChat\Facades\WireChat')
<div x-data="{ selectedConversationId: '{{ request()->conversation ?? $selectedConversationId }}' }"
    x-on:open-chat.window="selectedConversationId= $event.detail.conversation; $wire.selectedConversationId= $event.detail.conversation;"Maybe on wirenagiat ei can get he height os the element then scroll to that elemtn inside the the container and make sure it is in te middle of the viewport

     x-init="
    const container = document.getElementById('wirechat-chats-scrollable-container');

    function scrollToConversation(attempts = 5, delay = 400) {
        const el = document.getElementById('conversation-' + selectedConversationId);

        if (!container || !el || el.offsetParent === null || !selectedConversationId) {
            if (attempts > 0) {
                setTimeout(() => scrollToConversation(attempts - 1, delay), delay);
            }
            return;
        }

        container.style.overflowY = 'auto';

        const containerHeight = container.clientHeight;
        const containerScrollHeight = container.scrollHeight;
        const elementTop = el.offsetTop;
        const elementHeight = el.offsetHeight;

        const offsetToCenter = (containerHeight - elementHeight) / 2;
        let scrollOffset = elementTop - offsetToCenter;

        if (scrollOffset + containerHeight > containerScrollHeight) {
            scrollOffset = containerScrollHeight - containerHeight;
        }

        const maxScroll = containerScrollHeight - containerHeight;
        const finalScroll = Math.max(0, Math.min(scrollOffset, maxScroll));

        requestAnimationFrame(() => {
            container.scrollTo({
                top: finalScroll,
                behavior: 'smooth'
            });
        });

        console.log({
            containerHeight,
            containerScrollHeight,
            elementTop,
            elementHeight,
            scrollOffset,
            finalScroll
        });
    }

    setTimeout(scrollToConversation, 400);

    document.addEventListener('livewire:navigated', () => {
        setTimeout(scrollToConversation, 400);
    });
"




     class="flex flex-col bg-[var(--wc-light-primary)]  dark:bg-[var(--wc-dark-primary)]  transition-all h-full overflow-hidden w-full sm:p-3">

    @php
        /* Show header if any of these conditions are true  */
        $showHeader = $showNewChatModalButton || $allowChatsSearch || $showHomeRouteButton || !empty($title);
    @endphp

    {{-- include header --}}
    @includeWhen($showHeader, 'wirechat::livewire.chats.partials.header')

    <main x-data
        @scroll.self.debounce="
           {{-- Detect when scrolled to the bottom --}}
            // Calculate scroll values
            scrollTop = $el.scrollTop;
            scrollHeight = $el.scrollHeight;
            clientHeight = $el.clientHeight;

            // Check if the user is at the bottom of the scrollable element
            if ((scrollTop + clientHeight) >= (scrollHeight - 1) && $wire.canLoadMore) {
                // Trigger load more if we're at the bottom
                await $nextTick();
                $wire.loadMore();
            }
            "
          id="wirechat-chats-scrollable-container"
        class=" overflow-y-auto py-2   grow  h-full relative " style="contain:content">

        {{-- loading indicator --}}

        @if (count($conversations) > 0)
            {{-- include list item --}}
            @include('wirechat::livewire.chats.partials.list')


            {{-- include load more if true --}}
            @includeWhen($canLoadMore, 'wirechat::livewire.chats.partials.load-more-button')
        @else
            <div class="w-full flex items-center h-full justify-center">
                <h6 class=" font-bold text-gray-700 dark:text-white">{{ __('wirechat::chats.labels.no_conversations_yet')  }}</h6>
            </div>
        @endif
    </main>

</div>
