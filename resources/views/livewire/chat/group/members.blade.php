@php
       $authIsAdminInGroup=  $participant?->isAdmin();
       $authIsOwner=  $participant?->isOwner();
       $isGroup=  $conversation?->isGroup();

    @endphp


<div x-ref="members"
    class="h-[calc(100vh_-_6rem)]  sm:h-[450px] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] dark:text-white border border-[var(--wc-light-secondary)] dark:border-[var(--wc-dark-secondary)]  overflow-y-auto overflow-x-hidden  ">

    <header class=" sticky top-0 bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] z-10 p-2">
        <div class="flex items-center justify-center pb-2">

            <x-wirechat::actions.close-modal>
            <button  dusk="close_modal_button"
                class="p-2 ml-0 text-gray-600 hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)] dark:hover:text-white rounded-full hover:text-gray-800 ">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class=" w-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>

            </button>
            </x-wirechat::actions.close-modal>

            <h3 class=" mx-auto font-semibold ">{{__('wirechat::chat.group.members.heading.label')}} </h3>



        </div>

        {{-- Member limit error --}}
        <section class="flex flex-wrap items-center px-0 border-b dark:border-[var(--wc-dark-secondary)]">
            <input type="search" id="users-search-field" wire:model.live.debounce='search' autocomplete="off"
                placeholder="{{__('wirechat::chat.group.members.inputs.search.placeholder')}}"
                class="wc-input w-full border-0 w-auto dark:bg-[var(--wc-dark-primary)] outline-hidden focus:outline-hidden bg-[var(--wc-dark-parimary)] rounded-lg focus:ring-0 hover:ring-0">
        </section>

    </header>


    <div class="relative w-full p-2 ">
        {{-- <h5 class="text font-semibold text-gray-800 dark:text-gray-100">Recent Chats</h5> --}}
        <section class="my-4 grid">
            @if (count($participants)!=0)

                <ul class="overflow-auto flex flex-col">

                    @foreach ($participants as $key => $participant)
                        @php
                            $loopParticipantIsAuth =
                                $participant->participantable_id == auth()->id() &&
                                $participant->participantable_type == auth()->user()->getMorphClass();
                        @endphp
                        <li x-data="{ open: false }" x-ref="button" @click="open = ! open" x-init="$watch('open', value => {
                            $refs.members.style.overflow = value ? 'hidden' : '';
                        })"
                            aria-modal="true"
                            tabindex="0"
                            x-on:keydown.escape.stop="open=false"
                            @click.away ="open=false;" wire:key="users-{{ $key }}"
                            :class="!open || 'bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)]'"
                            class="flex cursor-pointer group gap-2 items-center overflow-x-hidden p-2 py-3">

                            <label class="flex cursor-pointer gap-2 items-center w-full">
                                <x-wirechat::avatar src="{{ $participant->participantable->wirechat_avatar_url }}"
                                    class="w-10 h-10" />

                                <div class="grid grid-cols-12 w-full ">
                                    <h6 @class(['transition-all truncate group-hover:underline col-span-10' ])>
                                        {{ $loopParticipantIsAuth ? 'You' : $participant->participantable->wirechat_name }}</h6>
                                        @if ($participant->isOwner()|| $participant->isAdmin())
                                        <span  style="background-color: var(--wirechat-primary-color);" class=" flex items-center col-span-2 text-white text-xs font-medium ml-auto px-2.5 py-px rounded-sm ">
                                            {{$participant->isOwner()? __('wirechat::chat.group.members.labels.owner'): __('wirechat::chat.group.members.labels.admin')}}
                                        </span>
                                        @endif

                                </div>

                                <div x-show="open" x-anchor.bottom-end="$refs.button"
                                    class="ml-auto bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-secondary)] border-[var(--wc-light-primary) dark:border-[var(--wc-dark-primary)] py-4 shadow-sm border rounded-md grid space-y-2 w-52">
                                    {{-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-gray-600 dark:text-gray-300  w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>   --}}

                                    <x-wirechat::dropdown-button wire:click="sendMessage('{{ $participant->id }}')"
                                        class="truncate ">
                                        @if ($loopParticipantIsAuth)

                                        {{__('wirechat::chat.group.members.actions.send_message_to_yourself.label')}}
                                        @else

                                        {{__('wirechat::chat.group.members.actions.send_message_to_member.label',['member'=>$participant->participantable?->wirechat_name ])}}
                                        @endif
                                    </x-wirechat::dropdown-button>

                                    @if ($authIsAdminInGroup || $authIsOwner)
                                        {{-- Only show admin actions to owner of group and if is not the current loop --}}
                                        {{--AND We only want to show admin actions if participant is not owner --}}
                                        @if ($authIsOwner && !$loopParticipantIsAuth)
                                            @if ($participant->isAdmin())
                                                <x-wirechat::dropdown-button
                                                    wire:click="dismissAdmin('{{ $participant->id }}')"
                                                    wire:confirm="{{__('wirechat::chat.group.members.actions.dismiss_admin.confirmation_message',['member'=>$participant->participantable?->wirechat_name])}}"
                                                    class="  ">
                                                    {{__('wirechat::chat.group.members.actions.dismiss_admin.label')}}
                                                </x-wirechat::dropdown-button>
                                            @else
                                                <x-wirechat::dropdown-button
                                                    wire:click="makeAdmin('{{ $participant->id }}')"
                                                    wire:confirm="{{__('wirechat::chat.group.members.actions.make_admin.confirmation_message',['member'=>$participant->participantable?->wirechat_name])}}"
                                                    class=" ">
                                                    {{__('wirechat::chat.group.members.actions.make_admin.label')}}
                                                </x-wirechat::dropdown-button>
                                            @endif
                                        @endif

                                            {{--AND We only want to show remove actions if participant is not owner of conversation because we don't want to remove owner--}}
                                            @if (!$participant->isOwner() && !$loopParticipantIsAuth && !$participant->isAdmin())
                                            <x-wirechat::dropdown-button
                                                wire:click="removeFromGroup('{{ $participant->id }}')"
                                                wire:confirm="{{__('wirechat::chat.group.members.actions.remove_from_group.confirmation_message',['member'=>$participant->participantable?->wirechat_name])}}"
                                                class="text-red-500 ">
                                                {{__('wirechat::chat.group.members.actions.remove_from_group.label')}}
                                            </x-wirechat::dropdown-button>
                                            @endif

                                    @else
                                    @endif



                                </div>
                            </label>

                        </li>
                    @endforeach



                </ul>


                {{-- Load more button --}}
                @if ($canLoadMore)
                    <section class="w-full justify-center flex my-3">
                        <button dusk="loadMoreButton" @click="$wire.loadMore()"
                            class=" text-sm dark:text-white hover:text-gray-700 transition-colors dark:hover:text-gray-500 dark:gray-200">
                            {{__('wirechat::chat.group.members.actions.load_more.label')}}
                        </button>
                    </section>
                @endif

            @else

            <span class="m-auto">{{__('wirechat::chat.group.members.labels.no_members_found')}}</span>
            @endif

        </section>
    </div>

</div>
