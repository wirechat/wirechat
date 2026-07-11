<div>
    @script
        <script>
            window.ChatListDrawer = () => {
                return {
                    show: false,
                    showActiveComponent: true,
                    activeDrawerComponent: false,
                    previousDrawerComponent: false,
                    transitioningDrawerComponent: false,
                    drawerComponentTransitionDirection: 'forward',
                    drawerComponentTransitionPhase: 'idle',
                    drawerComponentTransitionTimeout: null,
                    componentHistory: [],
                    listeners: [],
                    closeOnEscape: false,
                    closeOnEscapeIsForceful: false,
                    dispatchCloseEvent: false,
                    destroyOnClose: false,
                    closeOnClickAway: false,

                    closeChatListDrawerOnEscape(trigger) {
                        if (trigger.modalType !== 'ChatListDrawer') {
                            return;
                        }

                        if (this.closeOnEscape === false) {
                            return;
                        }

                        if (!this.closingModal('closingModalOnEscape')) {
                            return;
                        }

                        const force = this.closeOnEscapeIsForceful === true;
                        this.closeDrawer(force);
                    },

                    closeChatListDrawerOnClickAway() {
                        if (this.closeOnClickAway === false) {
                            return;
                        }

                        if (!this.closingModal('closingModalOnClickAway')) {
                            return;
                        }

                        this.closeDrawer(true);
                    },

                    closingModal(eventName) {
                        const componentName = this.$wire.get('drawerComponents')[this.activeDrawerComponent].name;

                        const params = {
                            id: this.activeDrawerComponent,
                            closing: true,
                        };

                        Livewire.dispatchTo(componentName, eventName, params);

                        return params.closing;
                    },

                    closeDrawer(force = false) {
                        if (this.show === false) {
                            return;
                        }

                        if (this.dispatchCloseEvent === true) {
                            const componentName = this.$wire.get('drawerComponents')[this.activeDrawerComponent].name;
                            Livewire.dispatch('chatsDrawerClosed', {
                                name: componentName
                            });
                        }

                        if (this.destroyOnClose === true) {
                            Livewire.dispatch('destroyChatListDrawer', {
                                id: this.activeDrawerComponent
                            });
                        }

                        const id = this.componentHistory.pop();
                        if (id && !force) {
                            this.setActiveDrawerComponent(id, true);
                        } else {
                            this.setShowPropertyTo(false);
                        }
                    },

                    setActiveDrawerComponent(id, skip = false) {
                        this.setShowPropertyTo(true);

                        if (this.activeDrawerComponent === id) {
                            return;
                        }

                        const previousDrawerComponent = this.activeDrawerComponent;

                        if (this.activeDrawerComponent !== false && skip === false) {
                            this.componentHistory.push(this.activeDrawerComponent);
                        }

                        this.activeDrawerComponent = id;
                        this.showActiveComponent = true;

                        if (previousDrawerComponent === false) {
                            this.previousDrawerComponent = false;
                            this.transitioningDrawerComponent = false;
                            this.drawerComponentTransitionPhase = 'idle';
                        } else {
                            if (this.drawerComponentTransitionTimeout) {
                                clearTimeout(this.drawerComponentTransitionTimeout);
                            }

                            this.previousDrawerComponent = previousDrawerComponent;
                            this.transitioningDrawerComponent = true;
                            this.drawerComponentTransitionDirection = skip ? 'back' : 'forward';
                            this.drawerComponentTransitionPhase = 'start';

                            requestAnimationFrame(() => {
                                requestAnimationFrame(() => {
                                    if (this.activeDrawerComponent === id) {
                                        this.drawerComponentTransitionPhase = 'end';
                                    }
                                });
                            });

                            this.drawerComponentTransitionTimeout = setTimeout(() => {
                                if (this.activeDrawerComponent === id) {
                                    this.previousDrawerComponent = false;
                                    this.transitioningDrawerComponent = false;
                                    this.drawerComponentTransitionPhase = 'idle';
                                }
                            }, 300);
                        }

                        const attributes = this.$wire.get('drawerComponents')[id]?.modalAttributes || {};
                        this.closeOnEscape = attributes.closeOnEscape ?? false;
                        this.closeOnEscapeIsForceful = attributes.closeOnEscapeIsForceful ?? false;
                        this.dispatchCloseEvent = attributes.dispatchCloseEvent ?? false;
                        this.destroyOnClose = attributes.destroyOnClose ?? true;
                        this.closeOnClickAway = attributes.closeOnClickAway ?? false;

                        this.$nextTick(() => {
                            const focusable = this.$refs[id]?.querySelector('[autofocus]');

                            if (focusable) {
                                setTimeout(() => {
                                    focusable.focus();
                                }, 50);
                            }
                        });
                    },

                    setShowPropertyTo(show) {
                        this.show = show;

                        if (!show) {
                            this.previousDrawerComponent = false;
                            this.transitioningDrawerComponent = false;
                            this.drawerComponentTransitionPhase = 'idle';

                            setTimeout(() => {
                                this.activeDrawerComponent = false;
                                this.$wire.resetState();
                            }, 300);
                        }
                    },

                    shouldShowDrawerComponent(id) {
                        return this.activeDrawerComponent === id || this.previousDrawerComponent === id;
                    },

                    drawerComponentTransitionClasses(id) {
                        const isActive = this.activeDrawerComponent === id;
                        const isPrevious = this.previousDrawerComponent === id;
                        const direction = this.drawerComponentTransitionDirection;
                        const phase = this.drawerComponentTransitionPhase;

                        if (!this.transitioningDrawerComponent) {
                            return isActive ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0';
                        }

                        if (phase === 'start') {
                            if (isActive) {
                                return direction === 'forward'
                                    ? 'translate-x-full opacity-100'
                                    : '-translate-x-full opacity-100';
                            }

                            return isPrevious ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0';
                        }

                        if (isActive) {
                            return 'translate-x-0 opacity-100';
                        }

                        if (isPrevious) {
                            return direction === 'forward'
                                ? '-translate-x-full opacity-100'
                                : 'translate-x-full opacity-100';
                        }

                        return 'translate-x-full opacity-0';
                    },

                    init() {
                        this.listeners.push(Livewire.on('closeChatListDrawer', (data) => {
                            this.closeDrawer(data?.force ?? false);
                        }));

                        this.listeners.push(Livewire.on('activeChatListDrawerComponentChanged', ({ id }) => {
                            this.setActiveDrawerComponent(id);
                        }));
                    },

                    destroy() {
                        this.listeners.forEach((listener) => listener());
                    }
                };
            }
        </script>
    @endscript

    <div
        data-modal-type="ChatListDrawer"
        id="chats-drawer"
        x-data="ChatListDrawer()"
        x-on:close.stop="setShowPropertyTo(false)"
        x-on:keydown.escape.stop="closeChatListDrawerOnEscape({ modalType: 'ChatListDrawer', event: $event })"
        x-show="show"
        class="pointer-events-none absolute inset-0 z-50 h-full overflow-y-auto"
        style="display: none;"
        tabindex="0"
    >
        <div class="pointer-events-auto relative h-full overflow-x-hidden bg-[var(--wc-light-primary)] text-left dark:bg-[var(--wc-dark-primary)]">
            <div
                x-show="show && showActiveComponent"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-full"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-full"
                class="relative h-full w-full overflow-x-hidden transition-all"
                id="chatsdrawer-container"
            >
                @foreach($drawerComponents as $id => $component)
                    <div
                        x-show="shouldShowDrawerComponent(@js($id))"
                        x-bind:class="drawerComponentTransitionClasses(@js($id))"
                        class="absolute inset-0 h-full w-full transition-all duration-300 ease-out"
                        x-ref="{{ $id }}"
                        wire:key="{{ $id }}"
                    >
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
