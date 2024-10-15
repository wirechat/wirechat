<div>


    {{-- 
    This script was copied from wire-elements modal 
    Copyright © 2021 Philo Hermans and contributors
    --}}
    <script>
        window.ChatModal = () => {
            return {
                show: false,
                showActiveComponent: true,
                activeComponent: false,
                componentHistory: [],
                modalWidth: null,
                listeners: [],
                getActiveComponentModalAttribute(key) {
                    if (this.$wire.get('components')[this.activeComponent] !== undefined) {
                        return this.$wire.get('components')[this.activeComponent]['modalAttributes'][key];
                    }
                },
                closeModalOnEscape(trigger) {
                    if (this.getActiveComponentModalAttribute('closeOnEscape') === false) {
                        return;
                    }

                    if (!this.closingModal('closingModalOnEscape')) {
                        return;
                    }

                    let force = this.getActiveComponentModalAttribute('closeOnEscapeIsForceful') === true;
                    this.closeModal(force);
                },
                closeModalOnClickAway(trigger) {
                    if (this.getActiveComponentModalAttribute('closeOnClickAway') === false) {
                        return;
                    }

                    if (!this.closingModal('closingModalOnClickAway')) {
                        return;
                    }

                    this.closeModal(true);
                },
                closingModal(eventName) {
                    const componentName = this.$wire.get('components')[this.activeComponent].name;

                    var params = {
                        id: this.activeComponent,
                        closing: true,
                    };

                    Livewire.dispatchTo(componentName, eventName, params);

                    return params.closing;
                },
                closeModal(force = false, skipPreviousModals = 0, destroySkipped = false) {
                    if (this.show === false) {
                        return;
                    }

                    if (this.getActiveComponentModalAttribute('dispatchCloseEvent') === true) {
                        const componentName = this.$wire.get('components')[this.activeComponent].name;
                        Livewire.dispatch('modalClosed', {
                            name: componentName
                        });
                    }
                    
                    /*!updated name 'destroyChatComponent' to not colide with main modal*/
                    if (this.getActiveComponentModalAttribute('destroyOnClose') === true) {
                        Livewire.dispatch('destroyChatComponent', {
                            id: this.activeComponent
                        });
                    }

                    const id = this.componentHistory.pop();

                    if (id && !force) {
                        if (id) {
                            this.setActiveModalComponent(id, true);
                        } else {
                            this.setShowPropertyTo(false);
                        }
                    } else {
                        this.setShowPropertyTo(false);
                    }
                },
                setActiveModalComponent(id, skip = false) {
                    this.setShowPropertyTo(true);

                    if (this.activeComponent === id) {
                        return;
                    }

                    if (this.activeComponent !== false && skip === false) {
                        this.componentHistory.push(this.activeComponent);
                    }

                    let focusableTimeout = 50;

                    if (this.activeComponent === false) {
                        this.activeComponent = id
                        this.showActiveComponent = true;
                        this.modalWidth = this.getActiveComponentModalAttribute('maxWidthClass');
                    } else {
                        
                        this.showActiveComponent = false;
                        focusableTimeout = 400;

                        setTimeout(() => {
                            this.activeComponent = id;
                            this.showActiveComponent = true;
                            this.modalWidth = this.getActiveComponentModalAttribute('maxWidthClass');
                        }, 300);
                    }

                    this.$nextTick(() => {
                        let focusable = this.$refs[id]?.querySelector('[autofocus]');
                        if (focusable) {
                            setTimeout(() => {
                                focusable.focus();
                            }, focusableTimeout);
                        }
                    });
                },
                focusables() {
                    let selector =
                        'a, button, input:not([type=\'hidden\'], textarea, select, details, [tabindex]:not([tabindex=\'-1\']))'

                    return [...this.$el.querySelectorAll(selector)]
                        .filter(el => !el.hasAttribute('disabled'))
                },
                firstFocusable() {
                    return this.focusables()[0]
                },
                lastFocusable() {
                    return this.focusables().slice(-1)[0]
                },
                nextFocusable() {
                    return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable()
                },
                prevFocusable() {
                    return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable()
                },
                nextFocusableIndex() {
                    return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1)
                },
                prevFocusableIndex() {
                    return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1
                },
                setShowPropertyTo(show) {
                    this.show = show;

                    if (show) {
                        document.body.classList.add('overflow-y-hidden');
                    } else {
                        document.body.classList.remove('overflow-y-hidden');

                        setTimeout(() => {
                            this.activeComponent = false;
                            this.$wire.resetState();
                        }, 300);
                    }
                },
                init() {
                    this.modalWidth = this.getActiveComponentModalAttribute('maxWidthClass');

                    /*! Changed the event to closeChatModal in order to not interfere with the main modal */
                    this.listeners.push(

                        Livewire.on('closeChatModal', (data) => {
                            this.closeModal(data?.force ?? false, data?.skipPreviousModals ?? 0, data
                                ?.destroySkipped ?? false);
                        })
                    );

                    /*! Changed listener name to activeChatModalComponentChanged to not interfer with main modal*/
                    this.listeners.push(
                        Livewire.on('activeChatModalComponentChanged', ({
                            id
                        }) => {
                            this.setActiveModalComponent(id);
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
      <div x-data="ChatModal()" x-on:close.stop="setShowPropertyTo(false)"
        x-on:keydown.escape.window="closeModalOnEscape()" x-show="show" class="fixed dark:bg-gray-900  dark:text-white opacity-100 inset-0 z-10 overflow-y-auto" style="display: none;">
        <div class="justify-center text-center overflow-y-auto">
            {{-- <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span> --}}
           <div x-show="show && showActiveComponent" 
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 -translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-full"
                class="w-auto  transition-all max-h-screen relative"
                id="chatmodal-container" x-trap.noscroll.inert="show && showActiveComponent" aria-modal="true">
                @forelse($components as $id => $component)
                    <div x-show.immediate="activeComponent == '{{ $id }}'" x-ref="{{ $id }}"
                        wire:key="{{ $id }}">
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>



  
</div>
