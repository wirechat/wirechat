<div id="group-info-modal" class="bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]     min-h-screen">


    @php
        $authIsAdminInGroup = $participant?->isAdmin();
        $authIsOwner = $participant?->isOwner();
        $isGroup = $conversation?->isGroup();
        $group = $conversation?->group;
    @endphp

    <section class="cursor-pointer flex gap-4 z-10  items-center p-5 sticky top-0 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]  ">
        <button wire:click="$dispatch('closeChatDrawer')" class="focus:outline-hidden cursor-pointer"> <svg class="w-7 h-7"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg> </button>
        <h3>{{__('wirechat::chat.group.info.heading.label')}}</h3>
    </section>
    {{-- Details --}}
    <header>

        {{-- Edit group form  --}}
        @if ($authIsAdminInGroup || $group?->allowsMembersToEditGroupInfo())
            <div @dusk="edit_group_information_section" class="flex  flex-col items-center gap-5 py-5  px-4    ">

                {{-- Avatar --}}
                <section class="mx-auto items-center justify-center grid">
                    <div @dusk="edit_avatar_label" class="relative  h-32 w-32 overflow-clip mx-auto rounded-full">

                        <label wire:target="photo" wire:loading.class="cursor-not-allowed" for="photo"
                            class=" cursor-pointer w-full h-full">
                            <x-wirechat::avatar wire:loading.class="cursor-not-allowed" group="{{ $isGroup }}"
                                :src="$cover_url" class="w-full h-full absolute inset-0" />
                        </label>
                        <input accept=".jpg,.jpeg,.png,.webp" wire:loading.attr="disabled" id="photo"
                            wire:model="photo" dusk="add_photo_field" type="file" hidden>


                        @if (empty($cover_url))
                            {{-- penceil --}}
                            <label wire:target="photo" wire:loading.class="cursor-not-allowed"
                                wire:loading.class.remove="cursor-pointer" for="photo"
                                class=" cursor-pointer bottom-0 inset-x-0 bg-gray-500/40 hover:bg-gray-500/80 dark:bg-white/40 dark:hover:bg-gray-700  transition-colors text-gray-600 flex items-center justify-center  absolute ">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="size-6  w-5 h-5">
                                    <path
                                        d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                </svg>

                            </label>
                        @else
                            <button type="button" wire:target="photo" wire:loading.attr="disabled"
                                class="disabled:cursor-not-allowed bottom-0 inset-x-0 bg-gray-500/40 hover:bg-gray-500/80 m-0 p-0 border-0  dark:bg-white/40  dark:hover:bg-gray-700 transition-colors  text-red-800 flex items-center justify-center  absolute "
                                wire:confirm="Are you sure you want to delete photo ?" wire:click="deletePhoto">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="size-6 w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    @error('photo')
                        <span class="text-red-500">{{ $message }}</span>
                    @enderror
                </section>


                {{-- Form --}}
                <div class="space-y-3 grid  overflow-x-hidden">

                    {{-- Form to update Group name  --}}
                    <form @dusk="edit_group_name_form" wire:submit="updateGroupName" x-data="{ editing: false }"
                        class=" justify-center flex   items-center w-full gap-5 px-5 items-center">
                        @csrf

                        {{-- Left side input --}}
                        <div class="   max-w-[90%] grid h-auto">
                            <div x-show="!editing">
                                <h4 dusk="form_group_name_when_not_editing" class="font-medium  break-all   whitespace-pre-line   text-2xl ">{{ $groupName }} </h4>
                            </div>

                            <input x-cloak maxlength="110" x-show="editing" id='groupName' type="text"
                                wire:model='groupName'
                                class="resize-none text-2xl font-medium  border-0 px-0 py-0 py-0 border-b border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)]  bg-inherit dark:text-white outline-hidden w-full focus:outline-hidden  focus:ring-0 hover:ring-0">


                            @error('groupName')
                                <p class="text-red-500 inline">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Right Side --}}
                        <span class=" items-center">

                            <button type="button" @click="editing=true" x-show="!editing">
                                {{-- pencil/edit --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="size-6  w-5 h-5">
                                    <path
                                        d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                </svg>

                            </button>

                            <button x-cloak @click="editing=false" x-show="editing">
                                {{-- check/submit --}}
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    fill="currentColor" class="bi bi-check-lg w-5 h-5" viewBox="0 0 16 16">
                                    <path
                                        d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z" />
                                </svg>
                            </button>

                        </span>

                    </form>

                    {{-- Members count --}}
                    <p class="mx-auto">  {{ __('wirechat::chat.group.info.labels.members') }} {{  $totalParticipants }} </p>

                </div>


                {{-- About --}}
                <section class=" px-8 py-5 ">
                    <div @dusk="edit_description_section" x-data="{ editing: false }" @click.outside="editing=false"
                        class="grid grid-cols-12 items-center">

                        {{-- Left side input --}}
                        <span class="col-span-11">
                            <div x-show="!editing">
                                @if (empty($description))
                                    <p class="text-sm" style="color: var(--wirechat-primary-color)">{{ __('wirechat::chat.group.info.labels.add_description') }}  </p>
                                @else
                                    <p class="font-medium break-all   whitespace-pre-line ">{{ $description }}
                                    </p>
                                @endif
                            </div>

                            <textarea x-cloak maxlength="501" x-show="editing" id='description' type="text" wire:model.blur='description'
                                class="resize-none font-medium w-full border-0 px-0 py-0 py-0 border-b border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-inherit dark:text-white outline-hidden w-full focus:outline-hidden  focus:ring-0 hover:ring-0">
                            </textarea>

                            @error('description')
                                <p class="text-red-500">{{ $message }}</p>
                            @enderror
                        </span>

                        {{-- Right Side --}}
                        <span class="col-span-1 flex items-center justify-end">

                            <button @click="editing=true" x-show="!editing">
                                {{-- pencil/edit --}}
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="size-6  w-5 h-5">
                                    <path
                                        d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
                                </svg>

                            </button>

                            <button x-cloak @click="editing=false" x-show="editing">
                                {{-- check --}}
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    fill="currentColor" class="bi bi-check-lg w-5 h-5" viewBox="0 0 16 16">
                                    <path
                                        d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z" />
                                </svg>
                            </button>




                        </span>

                    </div>
                </section>

            </div>
        @else
            {{-- Plain group information --}}
            <div @dusk="non_editable_group_information_section" class="flex  flex-col items-center gap-5 py-5 px-4  ">
                <x-wirechat::avatar :src="$cover_url" class=" h-32 w-32 mx-auto" />
                <h4 dusk="group_name" class="font-medium  break-all   whitespace-pre-line   text-2xl ">{{ $groupName }} </h4>
                <p class="mx-auto">{{ __('wirechat::chat.group.info.labels.members') }}  {{ $totalParticipants }} </p>
                <p class="font-medium break-all   whitespace-pre-line ">{{ $description }} </p>
            </div>
        @endif


    </header>



    <x-wirechat::divider />

    {{-- Members section --}}
    <section class="my-4 text-left space-y-3">
        {{-- Actiion button to trigger opening members  modal --}}
        <x-wirechat::actions.open-modal component="wirechat.chat.group.members"
            conversation="{{ $conversation?->id }}" widget="{{ $this->isWidget() }}" :panel="$this->panel">
            {{-- Members count --}}
            <button class="cursor-pointer flex w-full justify-between items-center px-8 focus:outline-hidden ">
                <span class="text-gray-600 dark:text-gray-300">{{ __('wirechat::chat.group.info.labels.members') }}  {{ $totalParticipants }}</span>
                {{-- Search icon --}}
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6 w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
            </button>
        </x-wirechat::actions.open-modal>

        {{-- Add Members --}}
        @if ($authIsAdminInGroup || $group?->allowsMembersToAddOthers())
            <x-wirechat::actions.open-modal component="wirechat.chat.group.add-members"
                conversation="{{ $conversation?->id }}" widget="{{ $this->isWidget() }}">
                <button @dusk="open_add_members_modal_button"
                    class="cursor-pointer w-full py-5 px-8 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] focus:outline-hidden transition  flex gap-3 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                        class="size-6 w-5 h-5">
                        <path
                            d="M5.25 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM2.25 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM18.75 7.5a.75.75 0 0 0-1.5 0v2.25H15a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25H21a.75.75 0 0 0 0-1.5h-2.25V7.5Z" />
                    </svg>

                    <span>{{ __('wirechat::chat.group.info.actions.add_members.label') }}</span>
                </button>
            </x-wirechat::actions.open-modal>
        @endif


    </section>

    <x-wirechat::divider />

    {{-- Footer section --}}
    <footer class="flex flex-col justify-start w-full">

        @if ($authIsOwner)

            {{-- Delete group --}}
            <button wire:confirm="{{ __('wirechat::chat.group.info.actions.delete_group.confirmation_message') }}" wire:click="deleteGroup"
                class="cursor-pointer w-full py-5 px-8 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] transition text-start space-y-2   gap-3   text-red-500">
                <div class="flex gap-3 items-center ">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6 w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    <span>{{ __('wirechat::chat.group.info.actions.delete_group.label') }}</span>
                </div>

                <p class="dark:text-white/60 text-sm text-gray-600/80">@lang('wirechat::chat.group.info.actions.delete_group.helper_text')</p>
            </button>
            {{-- Permissions --}}
            <div>

                <x-wirechat::actions.open-chat-drawer component='wirechat.chat.group.permissions'
                    conversation="{{ $conversation?->id }}">
                    <button
                        class="cursor-pointer w-full py-5 px-8 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] transition text-start space-y-2   gap-3   dark:text-white/90">
                        <div class="flex gap-3 items-center ">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="size-6 w-5 h-5 dark:text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.5 12a7.5 7.5 0 0 0 15 0m-15 0a7.5 7.5 0 1 1 15 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077 1.41-.513m14.095-5.13 1.41-.513M5.106 17.785l1.15-.964m11.49-9.642 1.149-.964M7.501 19.795l.75-1.3m7.5-12.99.75-1.3m-6.063 16.658.26-1.477m2.605-14.772.26-1.477m0 17.726-.26-1.477M10.698 4.614l-.26-1.477M16.5 19.794l-.75-1.299M7.5 4.205 12 12m6.894 5.785-1.149-.964M6.256 7.178l-1.15-.964m15.352 8.864-1.41-.513M4.954 9.435l-1.41-.514M12.002 12l-3.75 6.495" />
                            </svg>

                            <span>@lang('wirechat::chat.group.info.actions.group_permissions.label')</span>
                        </div>
                    </button>
                </x-wirechat::actions.open-chat-drawer>
            </div>
        @else
        {{-- Exit Group --}}
            <button wire:confirm="{{ __('wirechat::chat.group.info.actions.exit_group.confirmation_message') }}" wire:click="exitConversation"
                class="cursor-pointer w-full py-5 px-8 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] transition flex gap-3 items-center text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-box-arrow-right w-5 h-5" viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
                    <path fill-rule="evenodd"
                        d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
                </svg>
                <span>@lang('wirechat::chat.group.info.actions.exit_group.label')</span>
            </button>
        @endif

    </footer>
</div>
