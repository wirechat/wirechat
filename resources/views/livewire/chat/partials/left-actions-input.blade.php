@if($hasEmojiPicker)
    {{-- Emoji Triggger icon --}}
    <div class="w-10 hidden sm:flex max-w-fit  items-center">
        <button wire:loading.attr="disabled" type="button" dusk="emoji-trigger-button"
                x-on:keydown.escape.stop=" openEmojiPicker=false"
                @click="openEmojiPicker = ! openEmojiPicker" id="emojipickerbutton"
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
@endif

{{-- Show  upload pop if media or file are empty --}}
{{-- Also only show  upload popup if allowed in configuration  --}}
@if (count($this->media) == 0 && count($this->files) == 0 && $this->panel()->hasAttachments())
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
            @if ($this->panel()->hasFileAttachments())
                <label wire:loading.class="cursor-progress" x-data="attachments('files')"
                       class="cursor-pointer">
                    <input wire:loading.attr="disabled" wire:target="sendMessage"
                           dusk="file-upload-input"
                           @change="handleFileSelect(event, {{ count($files) }})" type="file"
                           multiple

                           accept="{{ collect($this->panel()->getFileMimes())->map(fn($ext) => '.' . $ext)->implode(',') }}"

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
            @if ($this->panel()->hasMediaAttachments())
                <label wire:loading.class="cursor-progress" x-data="attachments('media')"
                       class="cursor-pointer">

                    {{-- Trigger image upload --}}
                    <input dusk="media-upload-input" wire:loading.attr="disabled"
                           wire:target="sendMessage"
                           @change="handleFileSelect(event, {{ count($media) }})" type="file"
                           multiple
                           accept="{{ collect($this->panel()->getMediaMimes())->map(fn($ext) => '.' . $ext)->implode(',') }}"

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
