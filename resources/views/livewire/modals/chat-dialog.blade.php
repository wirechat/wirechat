<div>

    <script>
        window.WireChatDialog = () => {
            return {
                show: false,
                showActiveDialogComponent: true,
                activeDialogComponent: false,
                componentHistory: [],
                listeners: [],
                closeOnEscape: false,
                closeOnEscapeIsForceful: false,
                dispatchCloseEvent: false,
                destroyOnClose: false,
                closeOnClickAway:false,
        
                closeDialogOnEscape(trigger) {

                    ///Only proceed if the trigger is for ChatModal
                   if (trigger.modalType != 'WireChatDialog'){ return;}
                    //check if canCloseOnEsp
                   if (this.closeOnEscape === false) { return; }
                   if (!this.closingDialog('closingModalOnEscape')) { return; }

                   //check if should also close all children modal when this current on is closed
                   const force = this.closeOnEscapeIsForceful === true;
                   this.closeDialog(force);

                },
                closeDialogOnClickAway(trigger) {
                    if (this.closeOnClickAway === false) {
                        return;
                    }

                    if (!this.closingDialog('closingModalOnClickAway')) {
                        return;
                    }

                    this.closeDialog(true);
                },
                closingDialog(eventName) {
                    const componentName = this.$wire.get('dialogComponents')[this.activeDialogComponent].name;

                    var params = {
                        id: this.activeDialogComponent,
                        closing: true,
                    };

                    Livewire.dispatchTo(componentName, eventName, params);

                    return params.closing;
                },
                closeDialog(force = false, skipPreviousModals = 0, destroySkipped = false) {
                    if (this.show === false) {
                        return;
                    }

                    if (this.dispatchCloseEvent === true) {
                        const componentName = this.$wire.get('dialogComponents')[this.activeDialogComponent].name;
                        Livewire.dispatch('modalClosed', {
                            name: componentName
                        });
                    }

                    //Check if should completley destroy component on close 
                    //Meaning state won't be retained if component is opened again
                    if (this.destroyOnClose === true) {
                        Livewire.dispatch('destroyWireChatComponent', {
                            id: this.activeDialogComponent
                        });
                    }

                    const id = this.componentHistory.pop();

                    if (id && !force) {
                        if (id) {
                            this.setActiveDialogComponent(id, true);
                        } else {
                            this.setShowPropertyTo(false);
                        }
                    } else {
                        this.setShowPropertyTo(false);
                    }
                },
                setActiveDialogComponent(id, skip = false) {

                    this.setShowPropertyTo(true);

                    if (this.activeDialogComponent === id) {
                        return;
                    }

                    if (this.activeDialogComponent !== false && skip === false) {
                        this.componentHistory.push(this.activeDialogComponent);
                    }

                    let focusableTimeout = 50;

                    if (this.activeDialogComponent === false) {
                        this.activeDialogComponent = id
                        this.showActiveDialogComponent = true;
                    } else {
                        this.showActiveDialogComponent = false;

                        focusableTimeout = 400;

                        setTimeout(() => {
                            this.activeDialogComponent = id;
                            this.showActiveDialogComponent = true;
                        }, 300);
                    }

                    const attributes = this.$wire.get('dialogComponents')[id]?.modalAttributes || {};
                    this.closeOnEscape = attributes.closeOnEscape ?? false;
                    this.closeOnEscapeIsForceful = attributes.closeOnEscapeIsForceful ?? false;
                    this.dispatchCloseEvent = attributes.dispatchCloseEvent ?? false;
                    this.destroyOnClose = attributes.destroyOnClose ?? false; 
                    this.closeOnClickAway = attributes.closeOnClickAway ?? false; 

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
                            this.activeDialogComponent = false;
                            this.$wire.resetState();
                        }, 300);
                    }
                },
                init() {

                    this.listeners.push(
                        Livewire.on('closeModal', (data) => {
                            this.closeDialog(data?.force ?? false, data?.skipPreviousModals ?? 0, data
                                ?.destroySkipped ?? false);
                        })
                    );

                    this.listeners.push(
                        Livewire.on('activeDialogComponentChanged', ({
                            id
                        }) => {
                            this.setActiveDialogComponent(id);
                        })
                    );
                },
                destroy() {
                    this.listeners.forEach((listener) => {
                        listener();
                    });
                }
            };
        }
    </script>

    <div x-data="WireChatDialog()" x-on:close.stop="setShowPropertyTo(false)"
           x-on:keydown.escape="closeDialogOnEscape({modalType: 'WireChatDialog', event: $event })"
           x-show="show" class="fixed  inset-0 z-10 overflow-y-auto" style="display: none;">
        <div class="flex items-end  justify-center min-h-screen px-4 pt-4 pb-10 text-center sm:block sm:p-0">
            <div x-show="show" x-on:click="closeDialogOnClickAway()" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 transition-all transform">
                <div class="absolute inset-0 bg-gray-500 dark:bg-gray-800 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen " aria-hidden="true">&#8203;</span>

            <div x-show="show && showActiveDialogComponent" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"

                class="inline-block  align-bottom  rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full sm:max-w-lg"
                id="modal-container" x-trap.noscroll.inert="show && showActiveDialogComponent" aria-modal="true">
                @forelse($dialogComponents as $id => $component)
                    <div  x-show.immediate="activeDialogComponent == '{{ $id }}'" x-ref="{{ $id }}"
                        wire:key="{{ $id }}">
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>

  
</div>
