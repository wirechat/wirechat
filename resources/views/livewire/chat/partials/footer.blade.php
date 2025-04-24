@use('Namu\WireChat\Helpers\Helper')

<footer class="shrink-0 h-auto relative   sticky bottom-0 mt-auto">

    {{-- Check if group allows :sending messages --}}
    @if ($conversation->isGroup() && !$conversation->group?->allowsMembersToSendMessages() && !$authParticipant->isAdmin())
        <div
            class="dark:bg-[var(--wc-dark-secondary)]  bg-[var(--wc-light-secondary)] w-full text-center text-gray-600 dark:text-gray-200 justify-center text-sm flex py-4 ">
            Only admins can send messages
        </div>
    @else
        <div id="chat-footer" x-data="{ 'openEmojiPicker': false }"
            class=" px-3 md:px-1 border-t shadow-sm bg-[var(--wc-light-secondary)]  dark:bg-[var(--wc-dark-secondary)]   z-50   border-[var(--wc-light-primary)] dark:border-[var(--wc-dark-primary)] flex flex-col gap-3 items-center  w-full   mx-auto">

            {{-- Emoji section , we put it seperate to avoid interfering as overlay for form when opened --}}
            <section wire:ignore x-cloak x-show="openEmojiPicker" x-transition:enter="transition  ease-out duration-180 transform"
                x-transition:enter-start=" translate-y-full" x-transition:enter-end=" translate-y-0"
                x-transition:leave="transition ease-in duration-180 transform" x-transition:leave-start=" translate-y-0"
                x-transition:leave-end="translate-y-full"
                class="w-full flex hidden sm:flex   py-2 sm:px-4 py-1.5 border-b border-[var(--wc-light-primary)] dark:border-[var(--wc-dark-primary)]  h-96 min-w-full">

                <emoji-picker  dusk="emoji-picker" style="width: 100%"
                    class=" flex w-full h-full rounded-xl"></emoji-picker>
            </section>
            {{-- form and detail section  --}}
            <section
                class=" py-2 sm:px-4 py-1.5    z-50  dark:bg-[var(--wc-dark-secondary)]  bg-[var(--wc-light-secondary)]   flex flex-col gap-3 items-center  w-full mx-auto">

                {{-- Media preview section --}}
                <section x-show="$wire.media.length>0 ||$wire.files.length>0" x-cloak
                    class="  flex flex-col w-full gap-3" wire:loading.class="animate-pulse" wire:target="sendMessage">



                    @if (count($media) > 0)
                        <div x-data="attachments('media')">
                            {{-- todo: Implement error handling fromserver during file uploads --}}
                            {{--
                                @error('media')
                            <span class="flex text-sm text-red-500 pb-2 bg-gray-100 p-2 w-full justify-between">
                                    {{$message}}
                                    <button @click="$wire.resetAttachmentErrors()">X</button>
                            </span>
                            @enderror --}}
                                                {{-- todo:Show progress when uploading files --}}
                                                {{-- <div  x-show="isUploading"  class="w-full">
                                    <progress class="w-full h-1 rounded-lg" max="100" x-bind:value="progress"></progress>
                                </div> --}}
                            <section
                                class=" flex  overflow-x-scroll  ms-overflow-style-none items-center w-full col-span-12 py-2 gap-5 "
                                style=" scrollbar-width: none; -ms-overflow-style: none;">


                                {{-- Loop through media for preview --}}
                                @foreach ($media as $key => $mediaItem)
                                    @if (str()->startsWith($mediaItem->getMimeType(), 'image/'))
                                        <div class="relative h-24 sm:h-36 aspect-4/3 ">
                                            {{-- Delete image --}}
                                            <button wire:loading.attr="disabled"
                                                class="disabled:cursor-progress absolute -top-2 -right-2  z-10 dark:text-gray-50"
                                                @click="removeUpload('{{ $mediaItem->getFilename() }}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                                    <path
                                                        d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                                    <path
                                                        d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                                                </svg>
                                            </button>
                                            <img class="h-full w-full  rounded-lg object-scale-down"
                                                src="{{ $mediaItem->temporaryUrl() }}" alt="mediaItem">

                                        </div>
                                    @endif

                                    {{-- Attachemnt is Video/ --}}
                                    @if (str()->startsWith($mediaItem->getMimeType(), 'video/'))
                                        <div class="relative h-24 sm:h-36 ">
                                            <button wire:loading.attr="disabled"
                                                class="disabled:cursor-progress absolute -top-2 -right-2  z-10 dark:text-gray-50"
                                                @click="removeUpload('{{ $mediaItem->getFilename() }}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                                    <path
                                                        d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                                    <path
                                                        d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                                                </svg>
                                            </button>
                                            <x-wirechat::video height="h-24 sm:h-36 " :cover="false"
                                                :showToggleSound="false" :source="$mediaItem->temporaryUrl()" />
                                        </div>
                                    @endif
                                @endforeach


                                <label wire:loading.class="cursor-progress"
                                    class="shrink-0 cursor-pointer relative w-16 h-14 rounded-lg  bg-[var(--wc-light-secondary)] dark:bg-[var(--wc-dark-primary)]   hover:bg-[var(--wc-light-primary)] dark:hover:bg-[var(--wc-dark-primary)] border border-[var(--wc-light-secondary)] dark:border-[var(--wc-dark-secondary)]  flex text-center justify-center ">
                                    <input wire:loading.attr="disabled"
                                        @change="handleFileSelect(event,{{ count($media) }})" type="file" multiple
                                        accept="{{ Helper::formattedMediaMimesForAcceptAttribute() }}" class="sr-only">
                                    <span class="m-auto ">

                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                            class="w-7 h-7 text-gray-600 dark:text-gray-100">
                                            <path fill-rule="evenodd"
                                                d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z"
                                                clip-rule="evenodd" />
                                        </svg>

                                    </span>
                                </label>

                            </section>
                        </div>

                    @endif
                    {{-- ----------------------- --}}
                    {{-- Files preview section --}}
                    @if (count($files) > 0)
                        <section x-data="attachments('files')"
                            class="flex  overflow-x-scroll  ms-overflow-style-none items-center w-full col-span-12 py-2 gap-5 "
                            style=" scrollbar-width: none; -ms-overflow-style: none;">

                            {{-- Loop through files for preview --}}
                            @foreach ($files as $key => $file)
                                <div class="relative shrink-0">
                                    {{-- Delete file button --}}
                                    <button wire:loading.attr="disabled"
                                        class="disabled:cursor-progress absolute -top-2 -right-2  z-10"
                                        @click="removeUpload('{{ $file->getFilename() }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor"
                                            class="bi bi-x-circle dark:text-white dark:hover:text-red-500 hover:text-red-500 transition-colors"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                            <path
                                                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                                        </svg>
                                    </button>

                                    {{-- File details --}}
                                    <div
                                        class="flex items-center group overflow-hidden bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]   hover:border-[var(--wc-light-primary)] dark:hover:border-[var(--wc-dark-primary)] border border-[var(--wc-light-secondary)] dark:border-[var(--wc-dark-secondary)] rounded-xl">
                                        <span class=" p-2">
                                            {{-- document svg:HI --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="currentColor" class="w-8 h-8 text-gray-500 dark:text-gray-100">
                                                <path
                                                    d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625Z" />
                                                <path
                                                    d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z" />
                                            </svg>
                                        </span>

                                        <p class="mt-auto  p-2 text-gray-600 dark:text-gray-100 text-sm">
                                            {{ $file->getClientOriginalName() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Add more files --}}
                            {{-- TODO @if "( count($media)< $MAXFILES )" to hide upload button when maz files exceeded --}}
                            <label wire:loading.class="cursor-progress"
                                class="cursor-pointer shrink-0 relative w-16 h-14 rounded-lg bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]   hover:border-[var(--wc-light-primary)] dark:hover:border-[var(--wc-dark-primary)] border border-[var(--wc-light-secondary)] dark:border-[var(--wc-dark-secondary)]  transition-colors   flex text-center justify-center  ">
                                <input wire:loading.attr="disabled"
                                    @change="handleFileSelect(event,{{ count($files) }})" type="file" multiple
                                    accept="{{ Helper::formattedFileMimesForAcceptAttribute() }}" class="sr-only"
                                    hidden>
                                <span class="  m-auto">

                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                        class="w-6 h-6 dark:text-gray-50">
                                        <path fill-rule="evenodd"
                                            d="M12 3.75a.75.75 0 0 1 .75.75v6.75h6.75a.75.75 0 0 1 0 1.5h-6.75v6.75a.75.75 0 0 1-1.5 0v-6.75H4.5a.75.75 0 0 1 0-1.5h6.75V4.5a.75.75 0 0 1 .75-.75Z"
                                            clip-rule="evenodd" />
                                    </svg>


                                </span>
                            </label>

                        </section>
                    @endif
                </section>


                {{-- Replying to --}}
                @if ($replyMessage != null)
                    <section class="p-px py-1 w-full col-span-12">
                        <div class="flex justify-between items-center dark:text-white">
                            <h6 class="text-sm">
                                    {{ $replyMessage?->ownedBy($this->auth) ? __('wirechat::chat.labels.replying_to_yourself'): __('wirechat::chat.labels.replying_to',['participant'=>$replyMessage->sendable?->name])  }}
                            </h6>
                            <button wire:loading.attr="disabled" wire:click="removeReply()"
                                class="disabled:cursor-progress">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Message being replied to --}}
                        <p class="truncate text-sm text-gray-500 dark:text-gray-200 max-w-md">
                            {{ $replyMessage->body != '' ? $replyMessage->body : ($replyMessage->hasAttachment() ? 'Attachment' : '') }}
                        </p>

                    </section>
                @endif



                <form x-data="{
                    'body': $wire.entangle('body'),
                    insertNewLine: function(textarea) {
                        {{-- Get the current cursor position --}}
                        var startPos = textarea.selectionStart;
                        var endPos = textarea.selectionEnd;
                
                        {{-- Insert a line break character at the cursor position --}}
                        var text = textarea.value;
                        var newText = text.substring(0, startPos) + '\n' + text.substring(endPos, text.length);
                
                        {{-- Update the textarea value and cursor position --}}
                        textarea.value = newText;
                        textarea.selectionStart = startPos + 1; // Set cursor position after the inserted newline
                        textarea.selectionEnd = startPos + 1;
                
                        {{-- update height of element smoothly --}}
                        textarea.style.height = 'auto';
                        textarea.style.height = textarea.scrollHeight + 'px';
                
                    }
                }" x-init="{{-- Emoji picture click event listener --}}
                document.querySelector('emoji-picker')
                    .addEventListener('emoji-click', event => {
                        // Get the emoji unicode from the event
                        const emoji = event.detail['unicode'];
                
                        // Get the current value and cursor position
                        const inputField = $refs.body;
                        const inputFieldValue = inputField._x_model.get() ?? '';
                
                        const startPos = inputField.selectionStart;
                        const endPos = inputField.selectionEnd;
                
                        // Insert the emoji at the current cursor position
                        const newValue = inputFieldValue.substring(0, startPos) + emoji + inputFieldValue.substring(endPos);
                
                        // Update the value and move cursor after the emoji
                        inputField._x_model.set(newValue);
                
                
                        inputField.setSelectionRange(startPos + emoji.length, startPos + emoji.length);
                    });"
                    @submit.prevent="((body && body?.trim().length > 0) || ($wire.media && $wire.media.length > 0)|| ($wire.files && $wire.files.length > 0)) ? $wire.sendMessage() : null"
                    method="POST" autocapitalize="off" @class(['flex items-center col-span-12 w-full  gap-2 gap-5'])>
                    @csrf

                    <input type="hidden" autocomplete="false" style="display: none">


                    {{-- Emoji Triggger icon --}}
                    <div class="w-10 hidden sm:flex max-w-fit  items-center">
                        <button wire:loading.attr="disabled" type="button" dusk="emoji-trigger-button"
                            @click="openEmojiPicker = ! openEmojiPicker" x-ref="emojibutton"
                            class="cursor-pointer hover:scale-105 transition-transform disabled:cursor-progress rounded-full p-px dark:border-gray-700">
                            <svg x-bind:style="openEmojiPicker && { color: 'var(--wc-brand-primary)' }"
                                viewBox="0 0 24 24" height="24" width="24"
                                preserveAspectRatio="xMidYMid meet"
                                class="w-7 h-7 text-gray-600 dark:text-gray-300 srtoke-[1.3] dark:stroke-[1.2]"
                                version="1.1" x="0px" y="0px" enable-background="new 0 0 24 24">
                                <title>smiley</title>
                                <path fill="currentColor"
                                    d="M9.153,11.603c0.795,0,1.439-0.879,1.439-1.962S9.948,7.679,9.153,7.679 S7.714,8.558,7.714,9.641S8.358,11.603,9.153,11.603z M5.949,12.965c-0.026-0.307-0.131,5.218,6.063,5.551 c6.066-0.25,6.066-5.551,6.066-5.551C12,14.381,5.949,12.965,5.949,12.965z M17.312,14.073c0,0-0.669,1.959-5.051,1.959 c-3.505,0-5.388-1.164-5.607-1.959C6.654,14.073,12.566,15.128,17.312,14.073z M11.804,1.011c-6.195,0-10.826,5.022-10.826,11.217 s4.826,10.761,11.021,10.761S23.02,18.423,23.02,12.228C23.021,6.033,17.999,1.011,11.804,1.011z M12,21.354 c-5.273,0-9.381-3.886-9.381-9.159s3.942-9.548,9.215-9.548s9.548,4.275,9.548,9.548C21.381,17.467,17.273,21.354,12,21.354z  M15.108,11.603c0.795,0,1.439-0.879,1.439-1.962s-0.644-1.962-1.439-1.962s-1.439,0.879-1.439,1.962S14.313,11.603,15.108,11.603z">
                                </path>
                            </svg>
                        </button>
                    </div>

                    {{-- Show  upload pop if media or file are empty --}}
                    {{-- Also only show  upload popup if allowed in configuration  --}}
                    @if (count($this->media) == 0 &&
                            count($this->files) == 0 &&
                            (config('wirechat.allow_file_attachments', true) || config('wirechat.allow_media_attachments', true)))
                        <x-wirechat::popover position="top" popoverOffset="70">

                            <x-slot name="trigger" wire:loading.attr="disabled">
                                <span dusk="upload-trigger-button">

                                    {{-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor" class="w-7 h-7 dark:text-white/90">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg> --}}
                                    {{-- <svg  xmlns="http://www.w3.org/2000/svg"
                                            width="16" height="16" fill="currentColor"
                                            class="bi bi-plus-lg w-6 h-6 text-gray-600 dark:text-white/90" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
                                        </svg> --}}

                                    {{-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.3" stroke="currentColor" class="size-6 w-7 h-7 text-gray-600 dark:text-white/90">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                          </svg> --}}
                                    <svg class="size-6 w-7 h-7 text-gray-600 dark:text-white/60"
                                        xmlns="http://www.w3.org/2000/svg" width="36" height="36"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" class="ai ai-Attach">
                                        <path
                                            d="M6 7.91V16a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V6a4 4 0 0 0-4-4v0a4 4 0 0 0-4 4v9.182a2 2 0 0 0 2 2v0a2 2 0 0 0 2-2V8" />
                                    </svg>

                                </span>

                            </x-slot>

                            {{-- content --}}
                            <div class="grid gap-2 w-full ">

                                {{-- Upload Files --}}
                                @if (config('wirechat.allow_file_attachments', true))
                                    <label wire:loading.class="cursor-progress" x-data="attachments('files')"
                                        class="cursor-pointer">
                                        <input wire:loading.attr="disabled" wire:target="sendMessage"
                                            dusk="file-upload-input"
                                            @change="handleFileSelect(event, {{ count($files) }})" type="file"
                                            multiple accept="{{ Helper::formattedFileMimesForAcceptAttribute() }}"
                                            class="sr-only" style="display: none">

                                        <div
                                            class="w-full  flex items-center gap-3 px-1.5 py-2 rounded-md hover:bg-[var(--wc-light-primary)] dark:hover:bg-[var(--wc-dark-primary)] cursor-pointer">

                                            <span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" style="color: var(--wc-brand-primary);"
                                                    class="bi bi-folder-fill w-6 h-6" viewBox="0 0 16 16">
                                                    <path
                                                        d="M9.828 3h3.982a2 2 0 0 1 1.992 2.181l-.637 7A2 2 0 0 1 13.174 14H2.825a2 2 0 0 1-1.991-1.819l-.637-7a2 2 0 0 1 .342-1.31L.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3m-8.322.12q.322-.119.684-.12h5.396l-.707-.707A1 1 0 0 0 6.172 2H2.5a1 1 0 0 0-1 .981z" />
                                                </svg>
                                            </span>

                                            <span class=" dark:text-white">
                                               @lang('wirechat::chat.actions.upload_file.label')
                                            </span>
                                        </div>
                                    </label>
                                @endif


                                {{-- Upload Media --}}
                                @if (config('wirechat.allow_media_attachments', true))
                                    <label wire:loading.class="cursor-progress" x-data="attachments('media')"
                                        class="cursor-pointer">

                                        {{-- Trigger image upload --}}
                                        <input dusk="media-upload-input" wire:loading.attr="disabled"
                                            wire:target="sendMessage"
                                            @change="handleFileSelect(event, {{ count($media) }})" type="file"
                                            multiple accept="{{ Helper::formattedMediaMimesForAcceptAttribute() }}"
                                            class="sr-only" style="display: none">

                                        <div
                                            class="w-full flex items-center gap-3 px-1.5 py-2 rounded-md hover:bg-[var(--wc-light-primary)] dark:hover:bg-[var(--wc-dark-primary)] cursor-pointer">

                                            <span class="">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                    fill="currentColor" class="w-6 h-6"
                                                    style="color: var(--wc-brand-primary);">
                                                    <path fill-rule="evenodd"
                                                        d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </span>

                                            <span class=" dark:text-white">
                                               @lang('wirechat::chat.actions.upload_media.label')
                                            </span>
                                        </div>
                                    </label>
                                @endif


                            </div>
                        </x-wirechat::popover>
                    @endif

                    {{-- --------------- --}}
                    {{-- TextArea Input --}}
                    {{-- --------------- --}}

                    <div @class(['flex gap-2 sm:px-2 w-full'])>
                        <textarea @focus-input-field.window="$el.focus()" autocomplete="off" x-model='body' x-ref="body"
                            wire:loading.delay.longest.attr="disabled" wire:target="sendMessage" id="chat-input-field" autofocus
                            type="text" name="message" placeholder="{{ __('wirechat::chat.inputs.message.placeholder') }}" maxlength="1700" rows="1"
                            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px';"
                            @keydown.shift.enter.prevent="insertNewLine($el)" {{-- @keydown.enter.prevent prevents the
                               default behavior of Enter key press only if Shift is not held down. --}} @keydown.enter.prevent=""
                            @keyup.enter.prevent="$event.shiftKey ? null : (((body && body?.trim().length > 0) || ($wire.media && $wire.media.length > 0)) ? $wire.sendMessage() : null)"
                            class="w-full disabled:cursor-progress resize-none h-auto max-h-20  sm:max-h-72 flex grow border-0 outline-0 focus:border-0 focus:ring-0  hover:ring-0 rounded-lg   dark:text-white bg-none dark:bg-inherit  focus:outline-hidden   "
                            x-init="document.querySelector('emoji-picker')
                                .addEventListener('emoji-click', event => {
                                    const emoji = event.detail['unicode'];
                                    const inputField = $refs.body;
                            
                                    // Get the current cursor position (start and end)
                                    const startPos = inputField.selectionStart;
                                    const endPos = inputField.selectionEnd;
                            
                                    // Get current value of the input field
                                    const currentValue = inputField.value;
                            
                                    // Insert the emoji at the cursor position, preserving line breaks and spaces
                                    const newValue = currentValue.substring(0, startPos) + emoji + currentValue.substring(endPos);
                            
                                    // Update Alpine.js model (x-model='body') with the new value
                                    inputField._x_model.set(newValue);
                            
                                    // Set the cursor position after the inserted emoji
                                    inputField.setSelectionRange(startPos + emoji.length, startPos + emoji.length);
                            
                                    // Ensure the textarea resizes correctly after adding the emoji
                                    inputField.style.height = 'auto';
                                    inputField.style.height = inputField.scrollHeight + 'px';
                                });"></textarea>


                    </div>

                    {{-- --------------- --}}
                    {{-- input Actions --}}
                    {{-- --------------- --}}

                    <div x-cloak @class(['w-[5%] justify-end min-w-max  items-center gap-2 '])>

                        {{--  Submit button --}}
                        <button
                            x-show="((body?.trim()?.length>0) ||  $wire.media.length > 0 || $wire.files.length > 0 )"
                            wire:loading.attr="disabled" wire:target="sendMessage" type="submit"
                            id="sendMessageButton" class="cursor-pointer hover:text-[var(--wc-brand-primary)] transition-color ml-auto disabled:cursor-progress cursor-pointer font-bold">

                            <svg class="w-7 h-7   dark:text-gray-200" xmlns="http://www.w3.org/2000/svg"
                                width="36" height="36" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" class="ai ai-Send">
                                <path
                                    d="M9.912 12H4L2.023 4.135A.662.662 0 0 1 2 3.995c-.022-.721.772-1.221 1.46-.891L22 12 3.46 20.896c-.68.327-1.464-.159-1.46-.867a.66.66 0 0 1 .033-.186L3.5 15" />
                            </svg>

                        </button>



                        {{-- send Like button --}}
                        <button
                            x-show="!((body?.trim()?.length>0) || $wire.media.length > 0 || $wire.files.length > 0 )"
                            wire:loading.attr="disabled" wire:target="sendMessage" wire:click='sendLike()'
                            type="button" class="hover:scale-105 transition-transform cursor-pointer group disabled:cursor-progress">

                            <!-- outlined heart -->
                            <span class=" group-hover:hidden transition">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    class="w-7 h-7 text-gray-600 dark:text-white/90 stroke-[1.4] dark:stroke-[1.4]">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </span>
                            <!--  filled heart -->
                            <span class="hidden group-hover:block transition " x-bounce>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="size-6 w-7 h-7   text-red-500">
                                    <path
                                        d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                                </svg>
                            </span>

                        </button>


                    </div>

                </form>
            </section>



            @script
                <script>
                    Alpine.data('attachments', (type = "media") => ({
                        // State variables
                        isDropping: false, // Tracks if a file is being dragged over the drop area
                        type: type, // Type of file being uploaded (e.g., "media" or "file")
                        isUploading: false, // Indicates if files are currently uploading
                        MAXFILES: @json(config('wirechat.attachments.max_uploads', 5)), // Maximum number of files allowed
                        maxSize: @json(config('wirechat.attachments.media_max_upload_size', 12288)) * 1024, // Max size per file (in bytes)
                        allowedFileTypes: type === 'media' ? @json(config('wirechat.attachments.media_mimes')) :
                        @json(config('wirechat.attachments.file_mimes')), // Allowed MIME types based on type
                        progress: 0, // Progress of the current upload (0-100)
                        wireModel: type, // The Livewire model to bind to

                        // Handle file selection from the input field
                        handleFileSelect(event, count) {
                            if (event.target.files.length) {
                                const files = event.target.files;

                                // Validate selected files and upload if valid
                                this.validateFiles(files, count)
                                    .then((validFiles) => {
                                        if (validFiles.length > 0) {
                                            this.uploadFiles(validFiles);
                                        } else {
                                            console.log('No valid files to upload');
                                        }
                                    })
                                    .catch((error) => {
                                        console.log('Validation error:', error);
                                    });
                            }
                        },

                        // Upload files using Livewire's upload
                        uploadFiles(files) {
                            this.isUploading = true;
                            this.progress = 0;

                            // Initialize per-file progress tracking
                            const fileProgress = Array.from(files).map(() => 0);
                            files.forEach((file, index) => {
                                $wire.upload(
                                    `${this.wireModel}`, // Livewire model
                                    file, // Single file
                                    () => {
                                        fileProgress[index] = 100; // Mark this file as complete
                                        // this.isUploading = false;
                                        this.progress = Math.round((fileProgress.reduce((a, b) => a + b, 0)) / files.length);
                                    },
                                    (error) => {
                                        // this.isUploading = false;
                                        fileProgress[index] = -1; // Mark as failed
                                        $dispatch('wirechat-toast', { type: 'error', message: `Validation error: ${error}` });
                                    },
                                    (event) => {
                                        fileProgress[index] = event.detail.progress; // Update per-file progress
                                        this.progress = Math.round((fileProgress.reduce((a, b) => a + b, 0)) / files.length); // Overall progress
                                    }
                                );
                            });
                        },

                        // Upload files using Livewire's uploadMultiple method
                        
                        // Remove an uploaded file from Livewire
                        removeUpload(filename) {
                            $wire.removeUpload(this.wireModel, filename);
                        },

                        // Validate selected files against constraints
                        validateFiles(files, count) {
                            const totalFiles = count + files.length; // Total file count including existing uploads

                            // Check if total file count exceeds the maximum allowed
                            if (totalFiles > this.MAXFILES) {
                                files = Array.from(files).slice(0, this.MAXFILES -
                                count); // Limit files to the allowed number
                                $dispatch('wirechat-toast', {
                                    type: 'warning',
                                    message: @js(__('wirechat::validation.max.array', ['attribute' => __('wirechat::chat.inputs.media.label'),'max'=>config('wirechat.attachments.max_uploads', 5)]))
                                });
                            }

                            // Filter invalid files based on size and type
                            const invalidFiles = Array.from(files).filter((file) => {
                                const fileType = file.type.split('/')[1].toLowerCase(); // Extract file extension
                                return file.size > this.maxSize || !this.allowedFileTypes.includes(
                                fileType); // Check size and type
                            });

                            // Filter valid files
                            const validFiles = Array.from(files).filter((file) => {
                                const fileType = file.type.split('/')[1].toLowerCase();
                                return file.size <= this.maxSize && this.allowedFileTypes.includes(fileType);
                            });

                            // Handle invalid files by showing appropriate error messages
                            if (invalidFiles.length > 0) {
                                invalidFiles.forEach((file) => {
                                    if (file.size > this.maxSize) {
                                        $dispatch('wirechat-toast', {
                                            type: 'warning',
                                            message: @js(__('wirechat::validation.max.file', ['attribute' => __('wirechat::chat.inputs.media.label'),'max'=>config('wirechat.attachments.media_max_upload_size', 12288)]))
                                         //   message: `File size exceeds the maximum limit (${this.maxSize / 1024 / 1024}MB): ${file.name}`
                                        });
                                    } else {
                                        const extension = file.name.split('.').pop().toLowerCase();
                                        $dispatch('wirechat-toast', {
                                            type: 'warning',
                                            message: @js(__('wirechat::validation.mimes', [ 'attribute' => __('wirechat::chat.inputs.media.label'), 'values' => implode(', ', config('wirechat.attachments.media_mimes')) ]))
                                           // message: `One or more Files not uploaded: .${extension} (type not allowed)`
                                        });

                                    }
                                });
                            }

                            return Promise.resolve(validFiles); // Return valid files for further processing
                        }
                    }));
                </script>
            @endscript
        </div>
    @endif



</footer>
