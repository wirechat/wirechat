@use('Wirechat\Wirechat\Facades\Wirechat')
@php
    $chatsShellClass = trim('relative flex flex-col bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] transition-all h-full overflow-hidden w-full'.$this->getUiClass());
    $chatsShellStyles = $this->getUiStyles();
@endphp
<div
    x-data="{ selectedConversationId: @js(request()->conversation ?? $selectedConversationId) }"
     x-on:open-chat.window="selectedConversationId = $event.detail.conversation; $wire.selectedConversationId = $event.detail.conversation;"
     x-on:chat-opened.window="selectedConversationId = $event.detail.conversation"
     x-on:chat-closed.window="selectedConversationId = null"
     x-init="
        const container = document.getElementById('wirechat-chats-scrollable-container');
        const pendingInviteToken = @js($pendingInviteToken);
        const pendingInvitePanel = @js($pendingInvitePanel);
        const pendingConversationId = @js($pendingConversationId);
        const pendingConversationPanel = @js($pendingConversationPanel);
        const currentPanel = @js($this->panel);

        if (pendingConversationId && (!pendingConversationPanel || pendingConversationPanel === currentPanel)) {
            setTimeout(() => {
                selectedConversationId = pendingConversationId;
                $wire.selectedConversationId = pendingConversationId;
                $dispatch('open-chat', { conversation: pendingConversationId });
            }, 250);
        }

        if (pendingInviteToken && @js($this->panel()->hasGroupInvitations())) {
            const invitePanel = pendingInvitePanel || currentPanel;

            setTimeout(() => {
                Livewire.dispatch('openWirechatModal', {
                    component: 'wirechat.chat.group.join.lobby',
                    arguments: {
                        token: pendingInviteToken,
                        panel: invitePanel,
                        widget: @js($this->widget)
                    }
                });
            }, 250);
        }

        function scrollToConversation(attempts = 10, delay = 200) {
            requestAnimationFrame(() => {
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
        //const observer = new MutationObserver(() => {
        //    scrollToConversation();
        //});
        //observer.observe(container, { childList: true, subtree: true });
    "
     class="{{ $chatsShellClass }}"
     @if($chatsShellStyles) style="{{ $chatsShellStyles }}" @endif>

    @php
        /* Show header if any of these conditions are true  */
        $showHeader = $createChatAction || $chatsSearch || $redirectToHomeAction || !empty($heading) || $this->panel()->hasSettings();
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
          wire:navigate:scroll
        class=" overflow-y-auto py-2  scrollbar-thumb-zinc-400/70 dark:scrollbar-thumb-zinc-700 scrollbar-track-transparent grow  h-full relative " style="contain:content">

        {{-- loading indicator --}}

        @if (count($conversations) > 0)
            {{-- include list item --}}
            @include('wirechat::livewire.chats.partials.list')


            {{-- include load more if true --}}
            @includeWhen($canLoadMore, 'wirechat::livewire.chats.partials.load-more-button')
        @else
            @php
                $emptyStateLabel = trim((string) ($search ?? '')) !== ''
                    ? __('wirechat::chats.labels.no_conversations_found')
                    : __('wirechat::chats.labels.no_conversations_yet');
            @endphp

            <div class="flex h-full min-h-40 w-full items-center justify-center px-6 py-8 text-center" dusk="chats-empty-state">
                <p class="max-w-64 text-sm font-normal leading-5 text-zinc-500 dark:text-zinc-400">{{ $emptyStateLabel }}</p>
            </div>
        @endif
    </main>

    <livewire:wirechat.chats.drawer wire:key="chats-drawer" />

</div>
