<div>

    @script
        <script>
            window.ChatDrawer = () => {
                return {
                    show: false,
                    showActiveComponent: true,
                    activeDrawerComponent: false,
                    componentHistory: [],
                    listeners: [],
                    //current component attributes
                    closeOnEscape: false,
                    closeOnEscapeIsForceful: false,
                    dispatchCloseEvent: false,
                    destroyOnClose: false,
                    closeModalOnClickAway:false,

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

                        if (this.activeDrawerComponent !== false && skip === false) {
                            this.componentHistory.push(this.activeDrawerComponent);
                        }

                        let focusableTimeout = 50;

                        if (this.activeDrawerComponent === false) {
                            this.activeDrawerComponent = id
                            this.showActiveComponent = true;
                        } else {

                            this.showActiveComponent = false;
                            focusableTimeout = 400;

                            setTimeout(() => {
                                this.activeDrawerComponent = id;
                                this.showActiveComponent = true;
                            }, 300);
                        }

                        
                        // Fetch modal attributes and set Alpine properties 
                        const attributes = this.$wire.get('drawerComponents')[id]?.modalAttributes || {};
                        this.closeOnEscape = attributes.closeOnEscape ?? false;
                        this.closeOnEscapeIsForceful = attributes.closeOnEscapeIsForceful ?? false;
                        this.dispatchCloseEvent = attributes.dispatchCloseEvent ?? false;
                        this.destroyOnClose = attributes.destroyOnClose ?? true; 
                        this.closeModalOnClickAway = attributes.closeModalOnClickAway ?? false; 


                        this.$nextTick(() => {
                            let focusable = this.$refs[id]?.querySelector('[autofocus]');
                            if (focusable) {
                                setTimeout(() => {
                                    focusable.focus();
                                }, focusableTimeout);
                            }
                        });

         
                    },

                    setShowPropertyTo(show) {
                        this.show = show;
                        if (show) {
                            document.body.classList.add('overflow-y-hidden');
                        } else {
                            document.body.classList.remove('overflow-y-hidden');

                            setTimeout(() => {
                                this.activeDrawerComponent = false;
                                this.$wire.resetState();
                            }, 300);
                        }
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
         class="fixed bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]  dark:text-white opacity-100 inset-0 z-50 h-full overflow-y-auto" style="display: none;"
         aria-modal="true"
         tabindex="0"
    
        >
        <div class="justify-center text-center relative">
            <div x-show="show && showActiveComponent" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 -translate-x-full"
                class="w-auto  transition-all " id="chatmodal-container"
                x-trap.noscroll.inert="show && showActiveComponent" aria-modal="true">
                @forelse($drawerComponents as $id => $component)
                    <div x-show.immediate="activeDrawerComponent == '{{ $id }}'" x-ref="{{ $id }}"
                        wire:key="{{ $id }}">
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>




</div>
