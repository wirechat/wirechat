@use('Namu\WireChat\Facades\WireChat')
<div
    x-data="{ selectedConversationId: '{{ request()->conversation ?? $selectedConversationId }}' }"
     x-on:open-chat.window="selectedConversationId = $event.detail.conversation; $wire.selectedConversationId = $event.detail.conversation;"
     x-init="
        const container = document.getElementById('wirechat-chats-scrollable-container');

        function scrollToConversation(attempts = 10, delay = 200) {
            const el = document.getElementById('conversation-' + selectedConversationId);
            if (!container || !el || !selectedConversationId) {
                if (attempts > 0) {
                    setTimeout(() => scrollToConversation(attempts - 1, delay), delay);
                }
                return;
            }

            // Get element's position relative to container
            const containerRect = container.getBoundingClientRect();
            const elementRect = el.getBoundingClientRect();
            const elementTop = elementRect.top - containerRect.top + container.scrollTop;
            const elementHeight = elementRect.height;

            const offsetToCenter = (container.clientHeight - elementHeight) / 2;
            let scrollOffset = elementTop - offsetToCenter;

            const maxScroll = container.scrollHeight - container.clientHeight;
            const finalScroll = Math.max(0, Math.min(scrollOffset, maxScroll));

            // Animate scroll
            requestAnimationFrame(() => {
                container.scrollTo({ top: finalScroll, behavior: 'smooth' });
            });
        }

        // Initial scroll on load
        setTimeout(() => scrollToConversation(), 400);

        // Scroll after navigation
        document.addEventListener('livewire:navigated', () => {
            setTimeout(() => scrollToConversation(), 400);
        });


        // Optional: track scroll when more messages are prepended (Load More)
        const observer = new MutationObserver(() => {
            scrollToConversation();
        });
        observer.observe(container, { childList: true, subtree: true });
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
