<div x-data="{
    height:0,
    conversationElement: document.getElementById('conversation'),
 }" 
 x-init="
 setTimeout(() => {
    height=conversationElement.scrollHeight;
    $nextTick(()=> conversationElement.scrollTop= height);
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

  {{-- onDOMContentLoaded = (event) => {  $nextTick(() => conversationElement.scrollTop = conversationElement.scrollHeight)} --}}
    setTimeout(() => {
        $nextTick(() => conversationElement.scrollTop = conversationElement.scrollHeight);
    }, 100);  
    " class=" w-full overflow-hidden  h-full ">
    {{-- todo: add rounded corners to attachment --}}
    <div class="  border-r   flex flex-col overflow-y-hidden grow  h-full">
        {{--------------}}
        {{-----Header---}}
        {{--------------}}

        <header class="w-full  sticky inset-x-0 flex pb-[5px] pt-[7px] top-0 z-10 bg-white border-b">

            <div class="  flex  w-full items-center   px-2   lg:px-4 gap-2 md:gap-5 ">
                {{-- Return --}}
                <a href="{{route('wirechat')}}" class=" shrink-0 lg:hidden  dark:text-white" id="chatReturn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </a>

                {{--wirechat::Avatar --}}
                <div class=" shrink-0 ">
                    <a href="#">
                        <x-wirechat::avatar wire:ignore class="h-8 w-8 lg:w-10 lg:h-10 " />
                    </a>

                </div>
                <a href="#">
                    <h6 class="font-bold truncate"> {{$receiver->email}} </h6>
                </a>

                {{-- Actions --}}
                <div class="flex gap-2 items-center ml-auto">
                    <x-wirechat::dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex px-0">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9"
                                stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                               </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <button wire:click="delete" wire:confirm="are you sure" class="w-full text-start">

                                <x-wirechat::dropdown-link>
                                    Delete
                                </x-wirechat::dropdown-link>
                            </button>
                        </x-slot>
                    </x-wirechat::dropdown>

                </div>

            </div>

        </header>

        {{--------------}}
        {{---Messages---}}
        {{--------------}}
        <main @scroll="
         scrollTop= $el.scrollTop;
         console.log(scrollTop);
         if(scrollTop<=0){
            @this.dispatch('loadMore');
         }
        
        " @update-height.window="
  
        await $nextTick();
                newHeight=$el.scrollHeight;

                oldHeight= height;

                $el.scrollTop=newHeight-oldHeight;

                height=newHeight;


        " id="conversation"
            class="flex flex-col  gap-2 gap-y-4   p-2.5  overflow-y-auto flex-grow  overscroll-contain overflow-x-hidden w-full my-auto " style="contain: content">

            {{-- Define previous message outside the loop --}}
            @php
            $previousMessage=null;
            @endphp

            <!--Message-->
            @foreach ($loadedMessages as $key=> $message)

 
            @php
            $belongsToAuth= $message->sender_id==auth()->id();
            $attachment= $message->attachment??null;
            $isEmoji =mb_ereg('^(?:\X(?=\p{Emoji}))*\X$', $message->body??'');

            // keep track of previous message
            // The ($key -1 ) will get the previous message from loaded
            // messages since $key is directly linked to $message

            if ($key > 0){
            $previousMessage = $loadedMessages->get($key - 1) ;
            }

            // Get the next message
            $nextMessage = ($key < $loadedMessages->count() - 1) ? $loadedMessages->get($key + 1) : null;
            @endphp

            <div @class([ 'max-w-[85%] md:max-w-[78%]  flex flex-col gap-y-2 ' , 'ml-auto '=>$belongsToAuth])>

                        {{-- Show parent message --}}
                        @if ($belongsToAuth && $message->hasParent())
                        <div class="  w-full  flex flex-col gap-y-2    overflow-hidden  ">

                            <h6 class="text-xs text-gray-500 px-2 ">You replied to 
                                {{$message->parent->sender_id== $receiver->id? $receiver->name:" Yourself"}}
                            </h6>
    
                            <div class="border-r-4 px-1 ml-auto">
                                <p class=" bg-gray-100 text-black truncate rounded-full max-w-fit  text-sm px-3 py-1.5 ">
                                    {{$message->parent?->body}}
                                </p>
                            </div>
                          
    
                        </div>
                        @endif

                   
            
                    {{-- Body section --}}
                    <div 
                     @class(['flex gap-1 md:gap-4 group transition-transform',' justify-end'=>$belongsToAuth])>


                        {{-- Actions --}}
                        <div @class([
                            'my-auto flex invisible items-center gap-2 group-hover:visible',
                            'order-1'=> !$belongsToAuth,

                            ])>

                            <button wire:click="setReply('{{$message->id}}')" class="hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"
                                    class="w-4 h-4 text-gray-600/80 ">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                </svg>
                            </button>
                              
                        
                            <x-wirechat::dropdown align="{{$belongsToAuth?'right':'left'}}" width="48">
                                <x-slot name="trigger">
                                       {{-- Dots --}}
                                <button class="hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots h-3 w-3 text-gray-700" viewBox="0 0 16 16">
                                        <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3"/>
                                    </svg>
                                </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <button wire:click="delete" wire:confirm="are you sure" class="w-full text-start">
            
                                            <x-wirechat::dropdown-link>
                                                Unsend
                                            </x-wirechat::dropdown-link>
                                        </button>
                                    </x-slot>
                            </x-wirechat::dropdown>
        
                         

                        </div>

                        {{-- Avatar --}}
                        <div @class([ 'shrink-0 mt-auto -mb-2 ' , 'hidden'=> $belongsToAuth,
                            'invisible'=> ($message?->sender_id === $nextMessage?->sender_id)
                            ])>
                            <x-wirechat::avatar  class="h-7 w-7" src="https://ui-avatars.com/api/?background=random&color=fff&name={{$message->user->name}}" />
                        </div>

                        {{-- Message body --}}
                        <div class=" flex flex-col  gap-2" >
                     

                            {{-- Attachment section --}}
                            @if ($attachment)
                                <img @class([
                                    'max-w-max  h-[200px] min-h-[200px] bg-gray-200/60   object-scale-down  grow-0 shrink  overflow-hidden  rounded-3xl',
                                        //first message on RIGHT
                                        'rounded-br-md rounded-tr-2xl'=>($message?->sender_id==$nextMessage?->sender_id &&
                                    $message?->sender_id!=$previousMessage?->sender_id) && $belongsToAuth,

                                    //middle message on RIGHT
                                    'rounded-r-md'=>$previousMessage?->sender_id==$message->sender_id && $belongsToAuth,

                                    //Standalone message RIGHT
                                    'rounded-br-xl rounded-r-xl'=>($previousMessage?->sender_id!=$message?->sender_id &&
                                    $nextMessage?->sender_id!=$message?->sender_id) && $belongsToAuth,


                                    //last Message on RIGHT
                                    'rounded-br-2xl '=>$previousMessage?->sender_id!==$nextMessage?->sender_id &&$belongsToAuth,

                                    //**LEFT

                                    //first message on LEFT
                                    'rounded-bl-md rounded-tl-2xl'=>($message?->sender_id==$nextMessage?->sender_id &&$message?->sender_id!=$previousMessage?->sender_id) && !$belongsToAuth,

                                    //middle message on LEFT
                                    'rounded-l-md'=>$previousMessage?->sender_id==$message->sender_id && !$belongsToAuth,

                                    //Standalone message LEFT
                                    'rounded-bl-xl rounded-l-xl '=>($previousMessage?->sender_id!=$message?->sender_id &&$nextMessage?->sender_id!=$message?->sender_id) && !$belongsToAuth,

                                    //last message on LEFT
                                    'rounded-bl-2xl'=>($message?->sender_id!=$nextMessage?->sender_id ) && !$belongsToAuth,


                                ])
                                loading="lazy" src="{{ url('storage/' . $attachment?->file_path) }}" alt="attachment">
                            @endif

                            @if ($isEmoji)

                             <p class="text-5xl">
                                {{$message->body}}
                             </p>
                                
                            @endif

                            @if ($message->body && !$isEmoji)
                            {{-- message body --}}
                            <div @class(['flex flex-wrap  max-w-fit text-[15px] border border-gray-200/40 rounded-xl p-2.5 flex
                                flex-col text-black bg-[#f6f6f8fb]', ' bg-blue-500/80 text-white'=> $belongsToAuth,

                                //first message on RIGHT
                                'rounded-br-md rounded-tr-2xl'=>($message?->sender_id==$nextMessage?->sender_id &&
                                $message?->sender_id!=$previousMessage?->sender_id) && $belongsToAuth,

                                //middle message on RIGHT
                                'rounded-r-md'=>$previousMessage?->sender_id==$message->sender_id && $belongsToAuth,

                                //Standalone message RIGHT
                                'rounded-br-xl rounded-r-xl'=>($previousMessage?->sender_id!=$message?->sender_id &&
                                $nextMessage?->sender_id!=$message?->sender_id) && $belongsToAuth,


                                //last Message on RIGHT
                                'rounded-br-2xl '=>$previousMessage?->sender_id!==$nextMessage?->sender_id &&$belongsToAuth,

                                //**LEFT

                                //first message on LEFT
                                'rounded-bl-md rounded-tl-2xl'=>($message?->sender_id==$nextMessage?->sender_id &&$message?->sender_id!=$previousMessage?->sender_id) && !$belongsToAuth,

                                //middle message on LEFT
                                'rounded-l-md'=>$previousMessage?->sender_id==$message->sender_id && !$belongsToAuth,

                                //Standalone message LEFT
                                'rounded-bl-xl rounded-l-xl '=>($previousMessage?->sender_id!=$message?->sender_id &&$nextMessage?->sender_id!=$message?->sender_id) && !$belongsToAuth,

                                //last message on LEFT
                                'rounded-bl-2xl'=>($message?->sender_id!=$nextMessage?->sender_id ) && !$belongsToAuth,

                                ])
                                >

                                <p
                                    class="  whitespace-normal truncate text-sm md:text-base  tracking-wide lg:tracking-normal ">
                                    {{$message->body}}
                                </p>

                            </div>
                            @endif
                        </div>

                    </div>





            </div>

            @endforeach

        </main>
       
            <footer x-data="fileUploadComponent" class="shrink-0 z-10 bg-white dark:bg-inherit   py-2 overflow-x-hidden">
            <div class="  border px-3 py-1.5 rounded-3xl grid grid-cols-12 gap-2 items-center  w-full max-w-[95%] mx-auto">

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
                        <label class="relative w-16 h-14 rounded-lg bg-gray-100 flex text-center justify-center border border-gray-50">
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

               {{-- Replying to  --}}
               @if ($replyMessage !=null)   
                <section class="p-px py-1 w-full col-span-12">

                        <div class="flex justify-between items-center">
                            <h6 class="text-sm">Replying to  
                            <span class="font-bold">
                                {{$replyMessage->sender_id== $receiver->id? $receiver->name:" Yourself"}}
                            </span> </h6>
                            <button  wire:click="removeReply()">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg> 
                            </button>
                        </div>

                        {{-- Message being replies to  --}}
                        <p class="truncate text-sm text-gray-500 max-w-md">
                          {{$replyMessage->body!=''?$replyMessage->body:($replyMessage->hasAttachment()?'Attachment':'')}}
                        </p>
                </section>
               @endif

                {{-- Emoji icon --}}
                <span class="col-span-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-7 h-7">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                    </svg>
                </span>

                <form wire:submit='sendMessage' method="POST" autocapitalize="off" @class(['col-span-11 md:col-span-9 ','md:col-span-11'=>count($this->photos)>0])>
                    @csrf
                    <input type="hidden" autocomplete="false" style="display: none">
                    <div class="grid grid-cols-12">
                        <input autocomplete="off" wire:model='body' id="sendMessage" autofocus type="text" name="message"
                            placeholder="Message" maxlength="1700"
                            class="col-span-10  border-0  outline-0 focus:border-0 focus:ring-0  hover:ring-0 rounded-lg   dark:text-gray-300     focus:outline-none   " />

                        <button type="submit" class="col-span-2 text-blue-500 font-bold text-right">Send</button>

                    </div>
                </form>

                {{-- Actions --}}
                <div  @class(['col-span-2 ml-auto  hidden md:flex items-center gap-3 ','hidden md:hidden'=>count($this->photos)>0])>

                    {{-- upload Image --}}
                    <label class="cursor-pointer">
                    
                        {{-- Trigger image upload --}}
                        <input @change="handleFileSelect(event, {{count($photos)}})" type="file" multiple {{--
                            wire:model.live='photos' --}} accept=".jpg,.png,.jpeg" class="sr-only" style="display: none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9"
                            stroke="currentColor" class="w-7 h-7">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>

                    </label>


                    {{--send Like --}}
                    <button wire:click='sendLike()'>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="w-7 h-7">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>

                    </button>
                </div>
            </div>
          @error('body') <p> {{$message}} </p> @enderror

    </footer>

</div>



@script
<script>
    Alpine.data('fileUploadComponent', () => ({
                isDropping: false,
                isUploading: false,
                MAXFILES: 5,
                MAXFILESIZE: 11 * 1024 * 1024,
                allowedFileTypes: ['image/png', 'image/jpeg', 'image/jpg'],
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
        
               
                const invalidFiles = Array.from(files).filter((file) => {
                    return file.size > maxSize || !this.allowedFileTypes.includes(file.type);
                });
                
                //filter valid file 
                const validFiles = Array.from(files).filter((file) => {
                    return file.size <= maxSize && this.allowedFileTypes.includes(file.type);
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