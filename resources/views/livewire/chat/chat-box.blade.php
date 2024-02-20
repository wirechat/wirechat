<div x-data="{
    height:0,
    conversationElement: document.getElementById('conversation'),
    }" x-init="
    setTimeout(() => {
        height=conversationElement.scrollHeight;
        $nextTick(()=> conversationElement.scrollTop= height);
        $wire.dispatch('focus-input-field');
    }, 150);


    {{-- Echo.private('users.{{auth()->user()->id}}')
    .notification((notification) => {

        if(
            notification['type']=='App\\Notifications\\MessageSentNotification' &&
            notification['conversation_id']=={{$conversation->id}}
        )
        {

            $wire.listenBroadcastedMessage(notification);
        }
     
    }); --}}
    " @scroll-bottom.window="
    
    setTimeout(() => {

        $nextTick(()=> { 

                {{--overflow-y: hidden; is used to hide the vertical scrollbar initially. --}}
                conversationElement.style.overflowY='hidden';

            {{-- scroll the element down --}}
            conversationElement.scrollTop = conversationElement.scrollHeight;

               {{-- After updating the chat height, overflowY is set back to 'auto', 
                 which allows the browser to determine whether to display the scrollbar 
                 based on the content height.  --}}
                 conversationElement.style.overflowY='auto';
        });

         
       
    }); 

  
    " class=" w-full overflow-hidden  h-full ">
    {{-- todo: add rounded corners to attachment --}}
    <div class="  border-r   flex flex-col overflow-y-hidden grow  h-full">
        {{--------------}}
        {{-----Header---}}
        {{--------------}}

      @include('wirechat::livewire.chat.Includes.chatbox-header')
        {{--------------}}
        {{---Messages---}}
        {{--------------}}
    @include('wirechat::livewire.chat.Includes.chatbox-main')

        <footer x-data="fileUploadComponent" class="shrink-0 z-10 bg-white dark:bg-inherit   py-2 overflow-x-hidden">
            <div
                class="  border px-3 py-1.5 rounded-3xl grid grid-cols-12 gap-3 items-center  w-full max-w-[95%] mx-auto">

                {{-- Image preview section --}}
                @if (count($photos)>0)
                 <section
                    class="flex  overflow-x-scroll  ms-overflow-style-none items-center w-full col-span-12 py-2 gap-5 "
                    style=" scrollbar-width: none; -ms-overflow-style: none;">

                    {{-- Loop through media for preview --}}

                    @foreach ($photos as $key=> $image)

                    <div class="relative">
                        {{-- Delete image --}}
                        <button class="absolute -top-2 -right-2  z-10"
                            @click="removeUpload('{{ $image->getFilename()}}')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-x-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                <path
                                    d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                            </svg>
                        </button>
                        <img class="w-14 h-14 rounded-lg object-cover" src="{{$image->temporaryUrl()}}" alt="image">

                    </div>

                    @endforeach

                    {{-- TODO @if "( count($photos)< $MAXFILES )" to hide upload button when maz files exceeded --}}
                        <div>
                        {{-- Trigger image upload --}}
                        <label
                            class="relative w-16 h-14 rounded-lg bg-gray-100 flex text-center justify-center border border-gray-50">
                            <input @change="handleFileSelect(event, {{count($photos)}})" type="file" multiple {{--
                                wire:model.live='photos' --}} accept=".jpg,.png,.jpeg" class="sr-only">
                            <span class="  m-auto">

                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="w-7 h-7 text-gray-600">
                                    <path fill-rule="evenodd"
                                        d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z"
                                        clip-rule="evenodd" />
                                </svg>

                            </span>
                        </label>
                  </div>

                 </section>
                @endif

                {{-- Replying to --}}
                @if ($replyMessage !=null)
                <section class="p-px py-1 w-full col-span-12">

                    <div class="flex justify-between items-center">
                        <h6 class="text-sm">Replying to
                            <span class="font-bold">
                                {{$replyMessage->sender_id== $receiver->id? $receiver->name:" Yourself"}}
                            </span>
                        </h6>
                        <button wire:click="removeReply()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Message being replies to --}}
                    <p class="truncate text-sm text-gray-500 max-w-md">
                        {{$replyMessage->body!=''?$replyMessage->body:($replyMessage->hasAttachment()?'Attachment':'')}}
                    </p>
                </section>
                @endif

                {{-- Emoji icon --}}
                {{-- <span class="col-span-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-7 h-7">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                    </svg>
                </span> --}}

                <form 
                    x-data="{
                        'body':@entangle('body'),
                         insertNewLine: function (textarea) {

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
                            textarea.style.height = 'auto';textarea.style.height = textarea.scrollHeight + 'px';

                        }
                        
                    }"
                    @submit.prevent="((body && body?.trim().length > 0) || ($wire.photos && $wire.photos.length > 0)) ? $wire.sendMessage() : null"
                    method="POST" autocapitalize="off" @class([' flex w-full col-span-12 gap-2'])>
                    @csrf
                    <input type="hidden" autocomplete="false" style="display: none">

                    <div @class(['flex gap-2 sm:px-2 w-full'])>
                        <textarea 
                        @focus-input-field.window="$el.focus()" 
                        autocomplete="off" x-model='body' id="inputField"
                        autofocus type="text" name="message" placeholder="Message" maxlength="1700"
                        rows="1"
                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px';"
                        @keydown.shift.enter.prevent="insertNewLine($el)"
                        {{-- @keydown.enter.prevent prevents the default behavior of Enter key press only if Shift is not held down. --}}
                        @keydown.enter.prevent=""
                        @keyup.enter.prevent="$event.shiftKey ? null : (((body && body?.trim().length > 0) || ($wire.photos && $wire.photos.length > 0)) ? $wire.sendMessage() : null)"
                        class="w-full resize-none h-auto max-h-20  sm:max-h-72 flex grow border-0 outline-0 focus:border-0 focus:ring-0  hover:ring-0 rounded-lg   dark:text-gray-300     focus:outline-none   " ></textarea>
                        <button :class="{'hidden': !((body?.trim()?.length)|| @js(count($this->photos)>0))}" type="submit"
                            id="sendMessageButton" class="hidden w-[10%]  text-blue-500 font-bold text-right">Send</button>

                    </div>


                    {{-- Actions --}}
                    <div :class="{'hidden md:hidden':(body?.trim()?.length) || @json(count($this->photos)>0) }"
                        @class(['w-[15%] justify-end flex items-center gap-2 hidden md:hidden'])>

                        {{-- upload Image --}}
                        <label class="cursor-pointer">

                            {{-- Trigger image upload --}}
                            <input @change="handleFileSelect(event, {{count($photos)}})" type="file" multiple {{--
                                wire:model.live='photos' --}} accept=".jpg,.png,.jpeg" class="sr-only"
                                style="display: none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9"
                                stroke="currentColor" class="w-7 h-7">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>

                        </label>


                        {{--send Like --}}
                        <button wire:click='sendLike()' type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                stroke="currentColor" class="w-7 h-7">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>

                        </button>
                    </div>
                </form>

            </div>

       </footer>

    </div>



        @script
        <script>
            Alpine.data('fileUploadComponent', () => ({
                        isDropping: false,
                        isUploading: false,
                        MAXFILES: 5,
                        MAXFILESIZE: 11 * 1024 * 1024,
                        allowedFileTypes: ['png', 'jpeg', 'jpg'],
                        progress: 0,
                        wireModel: 'photos',
            
                    handleFileSelect(event,count) {
                        
                
                        if (event.target.files.length) {
                            const files = event.target.files;
                            this.validateFiles(files,count)
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
                    uploadFiles(files) {
                        
                        const $this = this;
                        this.isUploading = true;
                        const promises = [];
                        
                            const promise = new Promise((resolve, reject) => {
                                $wire.uploadMultiple(this.wireModel,files, function (success) {
                                    resolve(success);
                                }, function (error) {
                                    console.log('Validation error:', error);
                                    reject(error);
                                }, function (event) {
                                    $this.progress = event.detail.progress;
                                });
                            });
                
                        promises.push(promise);
                            
                        
                        Promise.all(promises)
                            .then((results) => {
                                console.log('Upload complete');
                                $this.isUploading = false;
                                $this.progress = 0;
                            })
                            .catch((error) => {
                                console.log('Upload error:', error);
                                $this.isUploading = false;
                                $this.progress = 0;
                            });
                    },
                    removeUpload(filename) {
                        $wire.removeUpload(this.wireModel, filename);
                    },

                    validateFiles(files,count) {
                
                        const maxSize = 9 * 1024 * 1024; // 7MB in bytes
                    // const allowedFileTypes = ['image/png', 'image/jpeg', 'image/jpg'];
                        var totalFiles=count + files.length;
                
                
                        //make sure max file not exceeded  
                        // Make sure max file count is not exceeded

                        if (totalFiles > this.MAXFILES) {

                        files = Array.from(files).slice(0, this.MAXFILES - count);

                        return  $dispatch('notify',{type:'warning',message:'File limit exceeded , allowed '+ this.MAXFILES});
                        }
                
                    
                        // const invalidFiles = Array.from(files).filter((file) => {
                        //     console.log(''file.type);
                        //     return file.size > maxSize || !this.allowedFileTypes.includes(file.type);
                        // });

                        const invalidFiles = Array.from(files).filter((file) => {

                            const fileType = file.type.split('/')[1].toLowerCase(); // Get the file extension from the MIME type
                            const isInvalid = file.size > maxSize || ! (this.allowedFileTypes.includes(fileType));

                            console.log('File Name:', file.name);
                            console.log('File Type:', fileType);
                            console.log('Is Invalid:', isInvalid);
                            console.log('includes', this.allowedFileTypes.includes(fileType));


                            return isInvalid;
                                });
                        
                        //filter valid file 
                        const validFiles = Array.from(files).filter((file) => {
                            const fileType = file.type.split('/')[1].toLowerCase();
                            return file.size <= maxSize && this.allowedFileTypes.includes(fileType);
                        });
                
                        if (invalidFiles.length > 0) {
                
                            const errorMessages = invalidFiles.map((file) => {
                                if (file.size > maxSize) {

                                return  $dispatch('notify',{type:'warning',message:`File size exceeds the maximum limit (9MB): ${file.name}`});
                                } else {

                                    
                                    //WIREUI error
                                //   return  window.$wireui.notify({
                                //         title: 'File type is not allowed:',
                                //         description:'Only PNG, JPEG, and JPG files are accepted.',
                                //          icon: 'error'
                                //     });

                                return  $dispatch('notify',{type:'warning',message:'File type is not allowed'});
                                }
                            });
                
                            
                            console.log('Validation errors:', errorMessages);
                            // Returning an empty array since there are no valid files
                            // return Promise.resolve([]);
                        }
                        return Promise.resolve(validFiles);
                    }
                    }))

        </script>
        @endscript

</div>