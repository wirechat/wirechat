<div>


    {{-- 
    This script was copied from wire-elements modal 
    Copyright © 2021 Philo Hermans and contributors
    --}}
    <script>
        window.WireChatModal = () => {
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
                closeWireChatModalOnEscape(trigger) {


                    ///Only proceed if the trigger is for ChatModal
                   if (trigger.modalType != 'WireChatModal'){ return;}
                   if (this.getActiveComponentModalAttribute('closeOnEscape') === false) { return; }
                   if (!this.closingModal('closingModalOnEscape')) { return; }

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

                    if (this.getActiveComponentModalAttribute('destroyOnClose') === true) {
                        Livewire.dispatch('destroyWireChatComponent', {
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

                    this.listeners.push(
                        Livewire.on('closeModal', (data) => {
                            this.closeModal(data?.force ?? false, data?.skipPreviousModals ?? 0, data
                                ?.destroySkipped ?? false);
                        })
                    );

                    this.listeners.push(
                        Livewire.on('activeWireChatModalComponentChanged', ({
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
      <div x-data="WireChatModal()" x-on:close.stop="setShowPropertyTo(false)"
           x-on:keydown.escape="closeWireChatModalOnEscape({modalType: 'WireChatModal', event: $event })"
           x-show="show" class="fixed inset-0 z-10 overflow-y-auto"
        style="display: none;">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-10 text-center sm:block sm:p-0">
            <div x-show="show" x-on:click="closeModalOnClickAway()" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 transition-all transform">
                <div class="absolute inset-0 bg-gray-500 dark:bg-gray-800 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="show && showActiveComponent" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-bind:class="modalWidth"
                class="inline-block w-full align-bottom  rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:w-full"
                id="modal-container" x-trap.noscroll.inert="show && showActiveComponent" aria-modal="true">
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
