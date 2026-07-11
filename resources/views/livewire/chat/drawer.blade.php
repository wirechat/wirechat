<div>

    @script
        <script>
            window.ChatDrawer = () => {
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
                    //current component attributes
                    closeOnEscape: false,
                    closeOnEscapeIsForceful: false,
                    dispatchCloseEvent: false,
                    destroyOnClose: false,
                    closeOnClickAway:false,

                    closeChatDrawerOnEscape(trigger) {

                        ///Only proceed if the trigger is for ChatDrawer
                        if (trigger.modalType !== 'ChatDrawer') {
                            return;
                        }

                        //check if canCloseOnEsp
                        if (this.closeOnEscape === false) {
                            return;
                        }

                        //Fire closingModalOnEscape:event to parent
                        if (!this.closingModal('closingModalOnEscape')) {
                            return;
                        }

                        //check if should also close all children modal when this current on is closed
                        const force = this.closeOnEscapeIsForceful === true;
                        this.closeDrawer(force);
                    },
                    closeChatDrawerOnClickAway() {
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

                        var params = {
                            id: this.activeDrawerComponent,
                            closing: true,
                        };

                        Livewire.dispatchTo(componentName, eventName, params);

                        return params.closing;
                    },

                    closeDrawer(force = false, skipPreviousModals = 0, destroySkipped = false) {
                        if (this.show === false) {
                            return;
                        }

                        //Check if should dispatch events
                        if (this.dispatchCloseEvent === true) {
                            const componentName = this.$wire.get('drawerComponents')[this.activeDrawerComponent].name;
                            Livewire.dispatch('chatDrawerClosed', {
                                name: componentName
                            });
                        }

                        //Check if should completley destroy component on close 
                        //Meaning state won't be retained if component is opened again
                        if (this.destroyOnClose === true) {
                            Livewire.dispatch('destroyChatDrawer', {
                                id: this.activeDrawerComponent
                            });
                        }

                        const id = this.componentHistory.pop();
                        if (id && !force) {
                            if (id) {
                                this.setActiveDrawerComponent(id, true);
                            } else {
                                this.setShowPropertyTo(false);
                            }
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

                        
                        // Fetch modal attributes and set Alpine properties 
                        const attributes = this.$wire.get('drawerComponents')[id]?.modalAttributes || {};
                        this.closeOnEscape = attributes.closeOnEscape ?? false;
                        this.closeOnEscapeIsForceful = attributes.closeOnEscapeIsForceful ?? false;
                        this.dispatchCloseEvent = attributes.dispatchCloseEvent ?? false;
                        this.destroyOnClose = attributes.destroyOnClose ?? true; 
                        this.closeOnClickAway = attributes.closeOnClickAway ?? false; 


                        this.$nextTick(() => {
                            let focusable = this.$refs[id]?.querySelector('[autofocus]');
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

                        /*! Changed the event to closeChatDrawer in order to not interfere with the main modal */
                        this.listeners.push(Livewire.on('closeChatDrawer', (data) => { this.closeDrawer(data?.force ?? false, data?.skipPreviousModals ?? 0, data ?.destroySkipped ?? false); }));

                        /*! Changed listener name to activeChatDrawerComponentChanged to not interfer with main modal*/
                        this.listeners.push(Livewire.on('activeChatDrawerComponentChanged', ({id}) => {
                            this.setActiveDrawerComponent(id);
                        }));
                    },
                    destroy() {
                        this.listeners.forEach((listener) => {
                            listener();
                        });
                    }
                };
            }
        </script>
    @endscript
    <div 
    data-modal-type="ChatDrawer"
    id="chat-drawer"
    x-data="ChatDrawer()" x-on:close.stop="setShowPropertyTo(false)"
         x-on:keydown.escape.stop="closeChatDrawerOnEscape({ modalType: 'ChatDrawer', event: $event }); "
         x-show="show"
         class="pointer-events-none absolute inset-0 z-50 h-full overflow-y-auto overscroll-contain" style="display: none;"
         tabindex="0"
    
        >
        <div class="pointer-events-auto relative h-full overflow-x-hidden bg-[var(--wc-light-primary)] text-left dark:bg-[var(--wc-dark-primary)] dark:text-white">
            <div x-show="show && showActiveComponent" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-full"
                class="relative h-full w-full overflow-x-hidden overscroll-contain transition-all" id="chatmodal-container">
                @forelse($drawerComponents as $id => $component)
                    <div class="absolute inset-0 h-full w-full overscroll-contain transition-all duration-300 ease-out" x-show="shouldShowDrawerComponent(@js($id))" x-bind:class="drawerComponentTransitionClasses(@js($id))" x-ref="{{ $id }}"
                        wire:key="{{ $id }}">
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>




</div>
