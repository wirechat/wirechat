@php

    $hasEmojiPicker= $this->panel()->hasEmojiPicker();
    $floatingEmojiPicker=$this->panel()->emojiPickerPosition()===\Wirechat\Wirechat\Support\Enums\EmojiPickerPosition::Floating;
@endphp
<footer class="shrink-0 h-auto relative   sticky bottom-0 mt-auto">

    {{-- Check if group allows :sending messages --}}
    @if ($conversation->isGroup() && !$conversation->group?->allowsMembersToSendMessages() && !$authParticipant->isAdmin())
        <div
            class="dark:bg-[var(--wc-dark-secondary)]  bg-[var(--wc-light-secondary)] w-full text-center text-gray-600 dark:text-gray-200 justify-center text-sm flex py-4 ">
            Only admins can send messages
        </div>
    @else
        <div id="chat-footer" x-data="{ 'openEmojiPicker': false }"
            class=" px-3 md:px-1 border-t  shadow-sm bg-[var(--wc-light-primary)]   dark:bg-[var(--wc-dark-secondary)]   z-50   border-[var(--wc-light-border)] dark:border-[var(--wc-dark-primary)] flex flex-col gap-3 items-center  w-full   mx-auto">

            {{-- Emoji section , we put it seperate to avoid interfering as overlay for form when opened --}}
            @if($hasEmojiPicker)
            {{--    If emoji picker is floading -wrap the emoji picke element into a teleport blade in order to allow proper render --}}
            {{--  --START-- TELEPORT --}}
            @if($floatingEmojiPicker) @teleport('body') @endif
            {{--  --END-- TELEPORT --}}
                <section wire:ignore  x-cloak x-show="openEmojiPicker"
                         @click.outside="openEmojiPicker=false"

                    @if($floatingEmojiPicker)
                     x-anchor.top.offset.20="document.getElementById('emojipickerbutton')"
                     x-transition:enter="transition ease-out duration-180 transform"
                     x-transition:enter-start="opacity-0 translate-y-4 scale-90"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-180 transform"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 scale-90"
                         dusk="floating-emojipicker"

                         @else
                    x-transition:enter="transition  ease-out duration-180 transform"
                    x-transition:enter-start=" translate-y-full" x-transition:enter-end=" translate-y-0"
                    x-transition:leave="transition ease-in duration-180 transform" x-transition:leave-start=" translate-y-0"
                    x-transition:leave-end="translate-y-full"
                         dusk="docked-emojipicker"
                    @endif
                    @class([
                            "max-w-lg h-[450px] xl:h-[520px] z-50 shadow-sm  bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] rounded-xl"=>$floatingEmojiPicker,
                            "min-w-full  border-b  h-96 border-[var(--wc-light-primary)] dark:border-[var(--wc-dark-primary)] "=>!$floatingEmojiPicker,
                            "w-full flex hidden sm:flex  inset-x-auto py-2 sm:px-4 py-1.5  "])>

                    <emoji-picker  dusk="emoji-picker" style="width: 100%"
                        class=" flex w-full h-full rounded-xl"></emoji-picker>

                    {{-- Clip-Arrow--}}
                    <div
                        style="
                            position: absolute;
                            top: -6px;  /* place above picker box */
                            left: 50%;  /* center horizontally */
                            transform: translateX(-50%);
                            width: 12px;
                            height: 6px;
                            z-index: 50;
                            background: transparent;
                            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
                            /* You can also use an SVG instead of clip-path */
                        "
                    ></div>
                </section>
            {{--  --START-- TELEPORT --}}
            @if($floatingEmojiPicker) @endteleport @endif
            {{--  --END-- TELEPORT --}}
            @endif

            {{-- form and detail section  --}}
            <section
                class="  sm:px-4 py-3.5   z-50     flex flex-col gap-3 items-center  w-full mx-auto">

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
                                           accept="{{ collect($this->panel()->getMediaMimes())->map(fn($ext) => '.' . $ext)->implode(',') }}"
                                           class="sr-only">
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
                                       accept="{{ collect($this->panel()->getFileMimes())->map(fn($ext) => '.' . $ext)->implode(',') }}"

                                       class="sr-only"
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
                                    {{ $replyMessage?->ownedBy($this->auth) ? __('wirechat::chat.labels.replying_to_yourself'): __('wirechat::chat.labels.replying_to',['participant'=>$replyMessage->sendable?->wirechat_name])  }}
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
                }" x-init="
                    @if($hasEmojiPicker)
                {{-- Emoji picture click event listener --}}
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
                    });
                @endif
                    "
                    @submit.prevent="((body && body?.trim().length > 0) || ($wire.media && $wire.media.length > 0)|| ($wire.files && $wire.files.length > 0)) ? $wire.sendMessage() : null"
                    method="POST" autocapitalize="off" @class(['flex  items-center col-span-12 w-full  gap-2 gap-5'])>
                    @csrf

                    <input type="hidden" autocomplete="false" style="display: none">


                    {{-- ------------------ --}}
                    {{-- Left Actions Input --}}
                    {{-- ------------------ --}}
                    @if($this->panel()->isShowLeftActions())
                    @include('wirechat::livewire.chat.partials.left-actions-input')
                    @endif

                    {{-- -------------- --}}
                    {{-- TextArea Input --}}
                    {{-- -------------- --}}
                    @if($this->panel()->isShowTextArea())
                    @include('wirechat::livewire.chat.partials.textarea-input')
                    @endif

                    {{-- ------------------- --}}
                    {{-- Right Actions Input --}}
                    {{-- ------------------- --}}
                    @if($this->panel()->isShowRightActions())
                    @include('wirechat::livewire.chat.partials.right-actions-input')
                    @endif
                </form>
            </section>



            @script
                <script>
                    Alpine.data('attachments', (type = "media") => ({
                        // State variables
                        isDropping: false, // Tracks if a file is being dragged over the drop area
                        type: type, // Type of file being uploaded (e.g., "media" or "file")
                        isUploading: false, // Indicates if files are currently uploading
                        MAXFILES: @json($this->panel()->getMaxUploads()), // Maximum number of files allowed
                        maxSize:  @json($this->panel()->getMediaMaxUploadSize()) * 1024, // Max size per file (in bytes)
                        allowedFileTypes: type === 'media' ? @json($this->panel()->getMediaMimes()) :@json($this->panel()->getFileMimes()), // Allowed MIME types based on type
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
                                    message: @js(__('wirechat::validation.max.array', ['attribute' => __('wirechat::chat.inputs.media.label'),'max'=>$this->panel()->getMaxUploads()]))
                                });
                            }

                            // Filter invalid files
                            const invalidFiles = Array.from(files).filter((file) => {
                                let fileType = file.name.split('.');
                                fileType = fileType.length > 1 ? fileType.pop().toLowerCase() : '';
                                return file.size > this.maxSize || !this.allowedFileTypes.includes(fileType);
                            });

                         // Filter valid files
                            const validFiles = Array.from(files).filter((file) => {
                                let fileType = file.name.split('.');
                                fileType = fileType.length > 1 ? fileType.pop().toLowerCase() : '';
                                return file.size <= this.maxSize && this.allowedFileTypes.includes(fileType);
                            });


                            // Handle invalid files by showing appropriate error messages
                            if (invalidFiles.length > 0) {
                                invalidFiles.forEach((file) => {
                                    if (file.size > this.maxSize) {
                                        $dispatch('wirechat-toast', {
                                            type: 'warning',
                                            message:this.type==='media'?
                                                    @js(__('wirechat::validation.max.file', ['attribute' => __('wirechat::chat.inputs.media.label'),'max'=>$this->panel()->getMediaMaxUploadSize()])):
                                                    @js(__('wirechat::validation.max.file', ['attribute' => __('wirechat::chat.inputs.media.label'),'max'=>$this->panel()->getFileMaxUploadSize()]))

                                         //   message: `File size exceeds the maximum limit (${this.maxSize / 1024 / 1024}MB): ${file.name}`
                                        });
                                    } else {
                                        const extension = file.name.split('.').pop().toLowerCase();
                                        $dispatch('wirechat-toast', {
                                            type: 'warning',
                                            message: this.type==='media'?
                                                    @js(__('wirechat::validation.mimes', [ 'attribute' => __('wirechat::chat.inputs.media.label'), 'values' => implode(', ', $this->panel()->getMediaMimes()) ])):
                                                    @js(__('wirechat::validation.mimes', [ 'attribute' => __('wirechat::chat.inputs.media.label'), 'values' => implode(', ', $this->panel()->getFileMimes()) ]))
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
