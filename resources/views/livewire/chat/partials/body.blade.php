<main
    x-ref="main-chat-body"
    x-data="{
        el: null,

        // paging guards
        loadingOlder: false,
        loadingNewer: false,
        pendingPrependRestore: false,

        // prepend anchor
        anchorId: null,
        anchorOffset: 0,

        // jump target (only valid for a short time)
        jumpTargetId: null,
        jumpLockUntil: 0,

        // cancels old retries
        jumpSeq: 0,

        // prevent infinite spam on load events
        mediaFixQueued: false,

        // manual scroll detection
        lastScrollTop: 0,

        // initial load guard
        initializing: true,

        captureAnchor() {
            const container = this.el;
            if (!container) return;

            const containerTop = container.getBoundingClientRect().top;
            const nodes = container.querySelectorAll('[data-message-id]');

            for (const node of nodes) {
                const rect = node.getBoundingClientRect();
                if (rect.bottom >= containerTop + 1) {
                    this.anchorId = node.getAttribute('data-message-id');
                    this.anchorOffset = rect.top - containerTop;
                    return;
                }
            }

            this.anchorId = null;
            this.anchorOffset = 0;
        },

        restoreToAnchor() {
            const container = this.el;
            if (!container || !this.anchorId) return;

            const containerTop = container.getBoundingClientRect().top;
            const node = container.querySelector(`[data-message-id='${this.anchorId}']`);
            if (!node) return;

            const rect = node.getBoundingClientRect();
            const newOffset = rect.top - containerTop;

            container.scrollTop += (newOffset - this.anchorOffset);
        },

        restoreAfterOlderLoaded() {
            if (!this.pendingPrependRestore) return;
            this.pendingPrependRestore = false;
            this.loadingOlder = false;

            requestAnimationFrame(() => {
                this.restoreToAnchor();
                requestAnimationFrame(() => this.restoreToAnchor());
            });
        },

        isNearTop() {
            const c = this.el;
            return c && c.scrollTop <= 10 && !this.loadingOlder && !this.pendingPrependRestore;
        },

        isNearBottom() {
            const c = this.el;
            return c && (c.scrollHeight - (c.scrollTop + c.clientHeight)) <= 30 && !this.loadingNewer;
        },

        async loadOlderWithStableScroll() {
            if (this.loadingOlder || this.pendingPrependRestore) return;
            if (!$wire.canLoadOlder) return;

            this.jumpTargetId = null;
            this.jumpLockUntil = 0;
            this.loadingOlder = true;
            this.captureAnchor();
            this.pendingPrependRestore = true;

            try {
                await $wire.loadOlder();

                if (!this.pendingPrependRestore) {
                    this.loadingOlder = false;
                }
            } catch (error) {
                this.pendingPrependRestore = false;
                this.loadingOlder = false;
            }
        },

        loadNewerIfNeeded() {
            if (this.loadingNewer) return;
            if (!$wire.canLoadNewer) return;

            this.loadingNewer = true;

            $wire.loadNewer().finally(() => {
                this.loadingNewer = false;
            });
        },

        scrollToMessageCenter(id) {
            const container = this.el;
            if (!container) return false;

            const node = container.querySelector(`[data-message-id='${id}']`);
            if (!node) return false;

            const nodeTop = node.offsetTop;
            const nodeH = node.offsetHeight;
            const cH = container.clientHeight;

            const target = Math.max(0, nodeTop - (cH / 2) + (nodeH / 2));
            container.scrollTop = target;

            node.classList.add('ring-2', 'ring-offset-2', 'ring-primary-500');
            setTimeout(() => node.classList.remove('ring-2', 'ring-offset-2', 'ring-primary-500'), 1200);

            return true;
        },

        scrollToMessageCenterWithRetry(id, tries = 40) {
            this.jumpSeq++;
            const seq = this.jumpSeq;

            this.jumpTargetId = id;
            this.jumpLockUntil = Date.now() + 1200;

            const tick = () => {
                if (seq !== this.jumpSeq) return;

                if (this.scrollToMessageCenter(id)) return;
                if (tries <= 0) return;

                tries--;
                requestAnimationFrame(tick);
            };

            requestAnimationFrame(tick);
        },

        onAnyMediaLoad() {
            if (this.mediaFixQueued) return;
            this.mediaFixQueued = true;

            requestAnimationFrame(() => {
                this.mediaFixQueued = false;

                if (this.pendingPrependRestore || this.loadingOlder) {
                    this.restoreToAnchor();
                    requestAnimationFrame(() => this.restoreToAnchor());
                    return;
                }

                if (this.initializing) {
                    this.scrollToBottom();
                    requestAnimationFrame(() => this.scrollToBottom());
                    return;
                }

                if (this.jumpTargetId && Date.now() < this.jumpLockUntil) {
                    this.scrollToMessageCenter(this.jumpTargetId);
                    requestAnimationFrame(() => this.scrollToMessageCenter(this.jumpTargetId));
                }
            });
        },

        scrollToBottom() {
            if (!this.el) return;
            this.el.scrollTop = this.el.scrollHeight;
            this.lastScrollTop = this.el.scrollTop;
        },

        onScroll() {
            const c = this.el;
            if (!c) return;

            const currentTop = c.scrollTop;
            const delta = Math.abs(currentTop - this.lastScrollTop);

            if (delta > 8) {
                this.jumpTargetId = null;
                this.jumpLockUntil = 0;
            }

            this.lastScrollTop = currentTop;

            if (this.isNearTop()) this.loadOlderWithStableScroll();
            if (this.isNearBottom()) this.loadNewerIfNeeded();
        },
    }"
        x-init="
        el = $el;
        lastScrollTop = el.scrollTop;

        setTimeout(() => {
            requestAnimationFrame(() => {
                scrollToBottom();
            });
        }, 100);

        setTimeout(() => {
            initializing = false;
        }, 220);
        "
    @scroll="onScroll()"
    x-on:load.capture="$data.onAnyMediaLoad()"
    x-on:error.capture="$data.onAnyMediaLoad()"

        @scroll-bottom.window="
        requestAnimationFrame(() => {
            el.style.overflowY = 'hidden';
            scrollToBottom();
            el.style.overflowY = 'auto';
        });
    "

        @scroll-to-message.window="$data.scrollToMessageCenterWithRetry($event.detail.id)"
        @older-loaded.window="$data.restoreAfterOlderLoaded()"

    x-cloak
    x-bind:class="{'opacity-0 pointer-events-none': initializing}"
     class='flex flex-col h-full transition-opacity duration-150 relative gap-2 gap-y-4 p-4 md:p-5 lg:p-8 grow overscroll-contain overflow-x-hidden w-full my-auto'
    style="contain: layout paint"
>

    <div x-cloak wire:loading.delay.class.remove="invisible" wire:target="loadOlder" class="invisible transition-all duration-300 ">
        <x-wirechat::loading-spin />
    </div>

    {{-- Define previous message outside the loop --}}
    @php
        $previousMessage = null;
    @endphp

    <!--Message-->
    @if ($loadedMessages)
        {{-- @dd($loadedMessages) --}}
        @foreach ($loadedMessages as $date => $messageGroup)

            {{-- Date  --}}
            <div wire:key="group-{{ md5($date) }}"  class="sticky top-0 uppercase p-2 shadow-xs px-2.5 z-50 rounded-xl border dark:border-[var(--wc-dark-primary)] border-[var(--wc-light-primary)] text-sm flex text-center justify-center  bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] dark:text-white  w-28 mx-auto ">
                {{ $date }}
            </div>

            @foreach ($messageGroup as $key => $message)
                {{-- @dd($message) --}}
                @php
                    $belongsToAuth = $message->belongsToAuth();
                    $parent = $message->parent ?? null;
                    $attachment = $message->attachment ?? null;
                    $isEmoji = $message->isEmoji();


                    // keep track of previous message
                    // The ($key -1 ) will get the previous message from loaded
                    // messages since $key is directly linked to $message
                    if ($key > 0) {
                        $previousMessage = $messageGroup->get($key - 1);
                    }

                    // Get the next message
                    $nextMessage = $key < $messageGroup->count() - 1 ? $messageGroup->get($key + 1) : null;
                @endphp
                <div
                    class="flex gap-2"
                    data-message-id="{{ $message->id }}"
                    id="message-{{ $message->id }}"
                    wire:key="msg-{{ $message->id }}"
                >

                    {{-- Message user Avatar --}}
                    {{-- Hide avatar if message belongs to auth --}}
                    @if (!$belongsToAuth && !$isPrivate)
                        <div @class([
                            'shrink-0 mb-auto  -mb-2',
                            // Hide avatar if the next message is from the same user
                            'invisible' =>
                                $previousMessage &&
                                $message?->sendable?->is($previousMessage?->sendable),
                        ])>
                            <x-wirechat::avatar src="{{ $message->sendable?->wirechat_avatar_url ?? null }}" class="h-8 w-8" />
                        </div>
                    @endif

                    {{-- we use w-[95%] to leave space for the image --}}
                    <div class="w-[95%] mx-auto">
                        <div @class([
                            'max-w-[85%] md:max-w-[78%]  flex flex-col gap-y-2  ',
                            'ml-auto' => $belongsToAuth])>



                            {{-- Show parent/reply message --}}
                            @if ($parent != null)
                                <div @class([
                                    'max-w-fit   flex flex-col gap-y-2',
                                    'ml-auto' => $belongsToAuth,
                                    // 'ml-9 sm:ml-10' => !$belongsToAuth,
                                ])>


                                    @php
                                    $sender = $message?->ownedBy($this->auth)
                                        ? __('wirechat::chat.labels.you')
                                        : ($message->sendable?->wirechat_name ?? __('wirechat::chat.labels.user'));

                                    $receiver = $parent?->ownedBy($this->auth)
                                        ? __('wirechat::chat.labels.you')
                                        : ($parent->sendable?->wirechat_name ?? __('wirechat::chat.labels.user'));
                                    @endphp

                                    <h6 class="text-xs text-gray-500 dark:text-gray-300 px-2">
                                        @if ($parent?->ownedBy($this->auth) && $message?->ownedBy($this->auth))
                                            {{ __('wirechat::chat.labels.you_replied_to_yourself') }}
                                        @elseif ($parent?->ownedBy($this->auth))
                                            {{ __('wirechat::chat.labels.participant_replied_to_you', ['sender' => $sender]) }}
                                        @elseif ($message?->ownedBy($parent->sendable))
                                            {{ __('wirechat::chat.labels.participant_replied_to_themself', ['sender' => $sender]) }}
                                        @else
                                            {{ __('wirechat::chat.labels.participant_replied_other_participant', ['sender' => $sender, 'receiver' => $receiver]) }}
                                        @endif
                                    </h6>



                                    <div @class([
                                        'px-1 border-[var(--wc-light-secondary)] dark:border-[var(--wc-dark-accent)] overflow-hidden ',
                                        ' border-r-4 ml-auto' => $belongsToAuth,
                                        ' border-l-4 mr-auto ' => !$belongsToAuth,
                                    ])>
                                        <p
                                            class=" bg-[var(--wc-light-secondary)] dark:text-white break-all  dark:bg-[var(--wc-dark-secondary)] text-black line-clamp-1 text-sm  rounded-full max-w-fit   px-3 py-1 ">
                                            {{ $parent?->body != '' ? $parent?->body : ($parent->hasAttachment() ?  __('wirechat::chat.labels.attachment') : '') }}
                                        </p>
                                    </div>


                                </div>
                            @endif



                            {{-- Body section --}}
                            <div @class([
                                'flex gap-1 md:gap-4 group transition-transform ',
                                'justify-end' => $belongsToAuth,
                            ])>

                                {{-- Message Actions --}}
                                @if (($isGroup && $conversation->group?->allowsMembersToSendMessages()) || $authParticipant->isAdmin())
                                <div dusk="message_actions" @class([ 'my-auto flex  w-auto  items-center gap-2', 'order-1' => !$belongsToAuth, ])>
                                    {{-- reply button --}}
                                    <button wire:click="setReply('{{ encrypt($message->id) }}')"
                                        class=" invisible  group-hover:visible hover:scale-110 transition-transform">

                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-reply-fill w-4 h-4 dark:text-white"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M5.921 11.9 1.353 8.62a.72.72 0 0 1 0-1.238L5.921 4.1A.716.716 0 0 1 7 4.719V6c1.5 0 6 0 7 8-2.5-4.5-7-4-7-4v1.281c0 .56-.606.898-1.079.62z" />
                                        </svg>
                                    </button>
                                    {{-- Dropdown actions button --}}
                                    <x-wirechat::dropdown class="w-40" align="{{ $belongsToAuth ? 'right' : 'left' }}"
                                        width="48">
                                        <x-slot name="trigger">
                                            {{-- Dots --}}
                                            <button class="invisible  group-hover:visible hover:scale-110 transition-transform">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor"
                                                    class="bi bi-three-dots h-3 w-3 text-gray-700 dark:text-white"
                                                    viewBox="0 0 16 16">
                                                    <path
                                                        d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3" />
                                                </svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">

                                            @if (($message->ownedBy($this->auth)|| ($authParticipant->isAdmin() && $isGroup)) && $this->panel()->hasDeleteMessageActions())
                                                <button dusk="delete_message_for_everyone" wire:click="deleteForEveryone('{{ encrypt($message->id) }}')"
                                                    wire:confirm="{{ __('wirechat::chat.actions.delete_for_everyone.confirmation_message') }}" class="w-full text-start">
                                                    <x-wirechat::dropdown-link>
                                                        @lang('wirechat::chat.actions.delete_for_everyone.label')
                                                    </x-wirechat::dropdown-link>
                                                </button>
                                            @endif


                                            {{-- Dont show delete for me if is group --}}
                                            @if (!$isGroup && $this->panel()->hasDeleteMessageActions())
                                            <button dusk="delete_message_for_me" wire:click="deleteForMe('{{ encrypt($message->id) }}')"
                                                wire:confirm="{{ __('wirechat::chat.actions.delete_for_me.confirmation_message') }}" class="w-full text-start">
                                                <x-wirechat::dropdown-link>
                                                    @lang('wirechat::chat.actions.delete_for_me.label')
                                                </x-wirechat::dropdown-link>
                                            </button>
                                            @endif


                                            <button dusk="reply_to_message_button" wire:click="setReply('{{ encrypt($message->id) }}')"class="w-full text-start">
                                                <x-wirechat::dropdown-link>
                                                    @lang('wirechat::chat.actions.reply.label')
                                                </x-wirechat::dropdown-link>
                                            </button>


                                        </x-slot>
                                    </x-wirechat::dropdown>

                                </div>
                                @endif


                                {{-- Message body --}}
                                <div class="flex flex-col gap-2 max-w-[95%]  relative">
                                    {{-- Show sender name is message does not belong to auth and conversation is group --}}


                                    {{-- -------------------- --}}
                                    {{-- Attachment section --}}
                                    {{-- -------------------- --}}
                                    @if ($attachment)
                                        @if (!$belongsToAuth && $isGroup)
                                            <div style="color:  var(--wc-brand-primary);" @class([
                                                'shrink-0 font-medium text-sm sm:text-base',
                                                // Hide avatar if the next message is from the same user
                                                'hidden' => $message?->sendable?->is($previousMessage?->sendable),
                                            ])>
                                                {{ $message->sendable?->wirechat_name }}
                                            </div>
                                        @endif

                                        {{-- Attachemnt is Video/ --}}
                                        @if ($attachment->isVideo())
                                            <x-wirechat::video height="max-h-[400px]" :cover="false" source="{{ $attachment?->url }}" />

                                        {{-- Attachemnt is image/ --}}
                                        @elseif($attachment->isImage())
                                            @include('wirechat::livewire.chat.partials.image', [ 'previousMessage' => $previousMessage, 'message' => $message, 'nextMessage' => $nextMessage, 'belongsToAuth' => $belongsToAuth, 'attachment' => $attachment ])
                                        @else
                                         {{-- Attachemnt is Application/ --}}
                                          @include('wirechat::livewire.chat.partials.file', [ 'attachment' => $attachment ])
                                        @endif

                                    @endif

                                    {{-- if message is emoji then don't show the styled messagebody layout --}}
                                    @if ($isEmoji)
                                        <p class="text-5xl dark:text-white ">
                                            {{ $message->body }}
                                        </p>
                                    @endif

                                    {{-- -------------------- --}}
                                    {{-- Message body section --}}
                                    {{-- If message is not emoji then show the message body styles --}}
                                    {{-- -------------------- --}}

                                    @if ($message->body && !$isEmoji)
                                    @include('wirechat::livewire.chat.partials.message', [ 'previousMessage' => $previousMessage, 'message' => $message, 'nextMessage' => $nextMessage, 'belongsToAuth' => $belongsToAuth, 'isGroup' => $isGroup, 'attachment' => $attachment])
                                    @endif

                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        @endforeach


    @endif

</main>
