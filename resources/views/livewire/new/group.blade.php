
<div x-data dusk="new_group_modal">

    <div
        class="relative w-full h-[410px] border overflow-hidden  items-center justify-center border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] overflow-auto bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] dark:text-white sm:max-w-lg sm:rounded-lg">

        {{--  Group Details --}}
        <section x-show="$wire.showAddMembers==false"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-x-full" x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-full"
            >

            <form wire:submit="validateDetails()" class="flex flex-col  h-full p-4">

                <header>

                    <div class="flex gap-10 w-full">

                        @if ($photo)
                            <div class="relative  w-28 h-28 overflow-clip rounded-full">
                                <x-wirechat::avatar :src="$photo->temporaryUrl()" class="w-28 h-28" />
                                <button
                                    type="button"
                                    class="bottom-0 inset-x-0 bg-white/40 text-red-800 flex items-center justify-center  absolute "
                                    wire:click="deletePhoto">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="size-6 w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        @else
                            <label class="cursor-pointer">
                                <x-wirechat::avatar wire:loading.class="animate-pulse" wire:target="photo" :group="true" class="w-28 h-28" />
                                <input wire:model="photo" dusk="add_photo_field" type="file" hidden accept=".jpg,.jpeg,.png,.webp">
                            </label>
                        @endif

                        <div class=" my-auto">

                            <label for="name">@lang('wirechat::new.group.inputs.name.label')</label>

                            <input id='name' type="text" wire:model='name' autofocus placeholder="{{__('wirechat::new.group.inputs.name.placeholder') }}"
                                class="wc-input w-full border-0 px-0  bg-inherit dark:text-white outline-hidden w-full focus:outline-hidden  focus:ring-0 hover:ring-0">

                            <span class="text-red-500 text-sm ">
                                @error('name')
                                    {{ $message }}
                                @enderror
                            </span>

                        </div>


                    </div>

                    <span class="text-red-500 text-sm ">
                        @error('photo')
                            {{ $message }}
                        @enderror
                    </span>

                </header>

                <main class="my-5">
                    <div class=" my-auto flex flex-col gap-y-2">

                        <label class="my-2" for="description">@lang('wirechat::new.group.inputs.description.label')</label>

                        <textarea id='description' type="text" wire:model='description' placeholder="{{__('wirechat::new.group.inputs.description.placeholder')}}" rows="4"
                            class="wc-textarea w-full resize-none rounded-lg border-[var(--wc-light-border)]  dark:border-[var(--wc-dark-border)]   bg-inherit dark:text-white outline-hidden w-full focus:outline-hidden  focus:ring-0 hover:ring-0">
                        </textarea>


                        <span class="text-red-500 text-sm ">
                            @error('description')
                                {{ $message }}
                            @enderror
                        </span>

                    </div>

                </main>

                <footer class="flex gap-4 justify-end mt-auto">
                    <x-wirechat::actions.close-modal>
                        <button type="button" dusk="cancel_create_new_group_button"class="font-bold cursor-pointer hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] p-3 px-4 rounded-xl ">
                            @lang('wirechat::new.group.actions.cancel.label')
                        </button>
                    </x-wirechat::actions.close-modal>

                    <button type="submit" :disabled="!($wire.name?.trim()?.length)" dusk="next_button"
                        :class="{ 'cursor-not-allowed hover:bg-none dark:hover:bg-inherit opacity-70': !($wire.name?.trim()?.length) }"
                        class="font-bold cursor-pointer transition hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] p-3 px-4 rounded-xl ">
                        @lang('wirechat::new.group.actions.next.label')
                    </button>
                </footer>
            </form>

        </section>

        {{-- Add members --}}
        <section dusk="add_members_section" x-cloak x-show="$wire.showAddMembers==true"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-full"
            x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-full"
            class="px-7 relative h-full overflow-x-hidden ">

            <header class=" sticky top-0 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] z-10 py-2">
                <div class="flex items-center pb-2">

                    <button @click="$wire.showAddMembers=false"
                        class="p-2 ml-0 text-gray-600 dark:hover:bg-[var(--wc-dark-secondary)] dark:hover:text-white rounded-full hover:text-gray-800 hover:bg-[var(--wc-light-secondary)]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class=" w-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>

                    </button>

                    <h3 class="text-sm mx-auto font-semibold "><span>@lang('wirechat::new.group.labels.add_members')</span> {{count($selectedMembers)}} / {{$maxGroupMembers}}</h3>

                    <button
                        wire:click="create"
                        wire:loading.attr="disabled"
                        wire:target='create'
                        class="p-2 disabled:cursor-not-allowed  ml-0 text-gray-600 cursor-pointer dark:text-gray-300 dark:hover:bg-[var(--wc-dark-secondary)] dark:hover:text-white rounded-full hover:text-gray-800 hover:bg-[var(--wc-light-secondary)]">
                        @lang('wirechat::new.group.actions.create.label')

                    </button>

                </div>

                {{-- Member limit error --}}
                <div
                  x-data="{ showError:false }"
                  x-on:show-member-limit-error.window="
                  showError=true;
                  setTimeout(()=>{ showError=false; },1500);
                  "
                 class="text-red-500 text-sm mx-auto ">
                   <span x-transition x-show="showError">
                    @lang('wirechat::new.group.messages.members_limit_error',['count'=>$maxGroupMembers])
                   </span>
                </div>
                {{-- Search input --}}
                <section class="flex flex-wrap items-center px-0 border-b border-[var(--wc-light-secondary)] dark:border-[var(--wc-dark-secondary)]">
                    <input type="search" id="users-search-field" wire:model.live.debounce='search' autocomplete="off"
                        placeholder="{{__('wirechat::new.group.inputs.search.placeholder')}}"
                        class="wc-input w-full border-0 w-auto dark:bg-[var(--wc-dark-primary)] outline-hidden focus:outline-hidden bg-[var(--wc-light-primary)] bg-none rounded-lg focus:ring-0 hover:ring-0">
                </section>


                <section class="  overflow-x-hidden my-2  ">
                    <ul
                    style="
                     -ms-overflow-style: none;
                     scrollbar-width: none;
                    "
                     class="flex w-full overflow-x-auto gap-3">

                        @if ($selectedMembers)

                            @foreach ($selectedMembers as $key => $member)
                                <li class="flex items-center text-nowrap min-w-fit px-2 py-1 text-sm font-medium text-gray-800  bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] rounded-sm  dark:text-gray-300"
                                    wire:key="selected-member-{{ $member->id }}">
                                    {{ $member->wirechat_name }}
                                    <button type="button"
                                        wire:click="toggleMember('{{ $member->id }}',{{ json_encode(get_class($member)) }})"
                                        class="flex items-center p-1 ms-2 text-sm text-gray-400 bg-transparent rounded-xs hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]  hover:text-gray-900  dark:hover:text-gray-300"
                                        aria-label="Remove">
                                        <svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                        <span class="sr-only">Remove badge</span>

                                    </button>
                                </li>
                            @endforeach
                        @endif




                    </ul>
                </section>

            </header>


            {{-- Search --}}
            <div class="relative w-full">
                {{-- <h5 class="text font-semibold text-gray-800 dark:text-gray-100">Recent Chats</h5> --}}
                <section class="my-4 grid">
                    @if (count($users)!=0)
                        <ul class="overflow-auto flex flex-col">
                            @foreach ($users as $key => $user)
                                <li class="flex cursor-pointer group gap-2 items-center p-2">

                                    <label
                                        wire:click="toggleMember('{{ $user['id'] }}',{{ json_encode($user['type']) }})"
                                        class="flex cursor-pointer gap-2 items-center w-full">
                                        <x-wirechat::avatar  src="{{ $user['wirechat_avatar_url'] }}" class="w-10 h-10" />

                                        <p class="group-hover:underline transition-all truncate">
                                            {{ $user['wirechat_name'] }}</p>

                                        <div class="ml-auto">
                                            @if ($selectedMembers->contains(fn($member) => $member->id == $user['id'] && $member->getMorphClass() == $user['type']))
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor"
                                                    class="bi bi-plus-square-fill w-6 h-6 text-green-500"
                                                    viewBox="0 0 16 16">
                                                    <path
                                                        d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm6.5 4.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3a.5.5 0 0 1 1 0" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-plus-square-dotted w-6 h-6"
                                                    viewBox="0 0 16 16">
                                                    <path
                                                        d="M2.5 0q-.25 0-.487.048l.194.98A1.5 1.5 0 0 1 2.5 1h.458V0zm2.292 0h-.917v1h.917zm1.833 0h-.917v1h.917zm1.833 0h-.916v1h.916zm1.834 0h-.917v1h.917zm1.833 0h-.917v1h.917zM13.5 0h-.458v1h.458q.151 0 .293.029l.194-.981A2.5 2.5 0 0 0 13.5 0m2.079 1.11a2.5 2.5 0 0 0-.69-.689l-.556.831q.248.167.415.415l.83-.556zM1.11.421a2.5 2.5 0 0 0-.689.69l.831.556c.11-.164.251-.305.415-.415zM16 2.5q0-.25-.048-.487l-.98.194q.027.141.028.293v.458h1zM.048 2.013A2.5 2.5 0 0 0 0 2.5v.458h1V2.5q0-.151.029-.293zM0 3.875v.917h1v-.917zm16 .917v-.917h-1v.917zM0 5.708v.917h1v-.917zm16 .917v-.917h-1v.917zM0 7.542v.916h1v-.916zm15 .916h1v-.916h-1zM0 9.375v.917h1v-.917zm16 .917v-.917h-1v.917zm-16 .916v.917h1v-.917zm16 .917v-.917h-1v.917zm-16 .917v.458q0 .25.048.487l.98-.194A1.5 1.5 0 0 1 1 13.5v-.458zm16 .458v-.458h-1v.458q0 .151-.029.293l.981.194Q16 13.75 16 13.5M.421 14.89c.183.272.417.506.69.689l.556-.831a1.5 1.5 0 0 1-.415-.415zm14.469.689c.272-.183.506-.417.689-.69l-.831-.556c-.11.164-.251.305-.415.415l.556.83zm-12.877.373Q2.25 16 2.5 16h.458v-1H2.5q-.151 0-.293-.029zM13.5 16q.25 0 .487-.048l-.194-.98A1.5 1.5 0 0 1 13.5 15h-.458v1zm-9.625 0h.917v-1h-.917zm1.833 0h.917v-1h-.917zm1.834-1v1h.916v-1zm1.833 1h.917v-1h-.917zm1.833 0h.917v-1h-.917zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
                                                </svg>
                                            @endif
                                        </div>
                                    </label>

                                </li>
                            @endforeach
                        </ul>
                    @else
                        @if (!empty($search))
                        <span class="m-auto">@lang('wirechat::new.group.messages.empty_search_result')</span>
                        @endif
                    @endif
                </section>
            </div>
        </section>

    </div>
</div>
