<?php

namespace Namu\WireChat\Livewire\Chat;

use App\Notifications\TestNotification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
//use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithPagination;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Models\Attachment;


class ChatBox extends Component
{

    use WithFileUploads;
    use WithPagination;

    public $conversation;

    public $receiver;
    public $body;

    public $loadedMessages;
    public int $paginate_var = 10;
    public bool $canLoadMore;


    public $media = [];


    public $files = [];



    //Theme 
    public string $authMessageBodyColor;

    public $replyMessage = null;

    /** 
     * Todo: Authorize the property
     * Todo: or lock it 
     * todo:Check if user can reply to this message 
     * Set replyMessage as Message Model
     *  */
    public function setReply(Message $message)
    {
        #check if user belongs to message
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);

        #abort if message does not belong to this conversation or is not owned by any participant
        abort_unless($message->conversation_id==$this->conversation->id,403);

        //Set owner as Id we are replying to 
        $this->replyMessage = $message;

        #dispatch event to focus input field 
        $this->dispatch('focus-input-field');
    }

    public function removeReply()
    {

        $this->replyMessage = null;
    }

    /**
     * livewire method
     ** This is avoid replacing temporary files on add more files
     * We override the function in WithFileUploads Trait
     */
    function _finishUpload($name, $tmpPath, $isMultiple)
    {
        $this->cleanupOldUploads();


        $files = collect($tmpPath)->map(function ($i) {
            return TemporaryUploadedFile::createFromLivewire($i);
        })->toArray();
        $this->dispatch('upload:finished', name: $name, tmpFilenames: collect($files)->map->getFilename()->toArray())->self();

        // If the property is an array, APPEND the upload to the array.
        $currentValue = $this->getPropertyValue($name);

        if (is_array($currentValue)) {
            $files = array_merge($currentValue, $files);
        } else {
            $files = $files[0];
        }

        app('livewire')->updateProperty($this, $name, $files);
    }

    
    function listenBroadcastedMessage($event)
    {

        // dd('reached');
        $this->dispatch('scroll-bottom');
        $newMessage = Message::find($event['message_id']);



        #push message
        $this->loadedMessages->push($newMessage);

        #mark as read
        $newMessage->read_at = now();
        $newMessage->save();
    }


    public function getListeners()
    {
        return [
            "echo-private:conversation.{$this->conversation->id},.Namu\\WireChat\\Events\\MessageCreated" => 'appendNewMessage',
        ];
    }

    //handle incomming broadcasted message event
    public function appendNewMessage($event)
    {

        //before appending message make sure it belong to this conversation 
        if ($event['message']['conversation_id'] == $this->conversation->id) {

            #scroll to bottom
            $this->dispatch('scroll-bottom');

            $newMessage = Message::find($event['message']['id']);

            #push message
            $this->loadedMessages->push($newMessage);

            #mark as read
            $newMessage->read_at = now();
            $newMessage->save();

            #broadcast 
            // $this->selectedConversation->getReceiver()->notify(new MessageRead($this->selectedConversation->id));
        }
    }


    /**
     * Delete conversation  */
    function deleteConversation()
    {

        #delete conversation 
        auth()->user()->deleteConversation($this->conversation);

        #redirect to chats page 
        $this->redirectRoute("wirechat");
    }

    protected function rateLimit(){


        if (RateLimiter::tooManyAttempts('send-message:'.auth()->id(), $perMinute = 60)) {

            return abort(429,'Too many attempts!, Please slow down');
         }
          
         RateLimiter::increment('send-message:'.auth()->id());
    }

    /**
     * Send a message  */
    function sendMessage()
    {
        abort_unless(auth()->check(), 401);


        #rate limit 
        $this->rateLimit();

         

        /* If media is empty then conitnue to validate body , since media can be submited without body */
        // Combine media and files arrays
        //dd($this->media);

        $attachments = array_merge($this->media, $this->files);
        //    dd(config('wirechat.file_mimes'));

        // If combined files array is empty, continue to validate body
        if (empty($attachments)) {
            $this->validate(['body' => 'required|string']);
        }

        if (count($attachments) != 0) {

            //Validation 

            // Retrieve maxUploads count
            $maxUploads = config('wirechat.attachments.max_uploads');

            //Files
            $fileMimes = implode(',', config('wirechat.attachments.file_mimes'));
            $fileMaxUploadSize = config('wirechat.attachments.file_max_upload_size');

            //media
            $mediaMimes = implode(',', config('wirechat.attachments.media_mimes'));
            $mediaMaxUploadSize = config('wirechat.attachments.media_max_upload_size');

            try {
                //$this->js("alert('message')");
                $this->validate([
                    "files" => "max:$maxUploads|nullable",
                    "files.*" => "mimes:$fileMimes|max:$fileMaxUploadSize",
                    "media" => "max:$maxUploads|nullable",
                    "media.*" => "max:$mediaMaxUploadSize|mimes:$mediaMimes",

                ]);
            } catch (\Illuminate\Validation\ValidationException $th) {


                return $this->dispatch('notify', type: 'warning', message: $th->getMessage());
            }


            //Combine media and files thne perform loop together

            $createdMessages = [];
            foreach ($attachments as $key => $attachment) {

                /**
                 * todo: Add url to table
                 */

                #save photo to disk 
                $path =  $attachment->store(config('wirechat.attachments.storage_folder', 'attachments'), config('wirechat.attachments.storage_disk','public'));

                #create attachment
                $createdAttachment = Attachment::create([
                    'file_path' => $path,
                    'file_name' => basename($path),
                    'original_name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'url' => url($path)
                ]);



                #create message
                $message = Message::create([
                    'reply_id' => $this->replyMessage?->id,
                    'conversation_id' => $this->conversation->id,
                    'attachment_id' => $createdAttachment->id,
                    'user_id' => auth()->id()
                    // 'body'=>$this->body
                ]);

                #append message to createdMessages
                $createdMessages[] = $message;


                #update the conversation model - for sorting in chatlist
                $this->conversation->updated_at = now();
                $this->conversation->save();

                #dispatch event 'refresh ' to chatlist 
                $this->dispatch('refresh')->to(ChatList::class);

                #broadcast message 
                $this->dispatchMessageCreatedEvent($message);
            }

            #push the message
            $this->loadedMessages = $this->loadedMessages->concat($createdMessages);

            #scroll to bottom
            $this->dispatch('scroll-bottom');
        }


        if ($this->body != null) {

            $createdMessage = Message::create([
                'reply_id' => $this->replyMessage?->id,
                'conversation_id' => $this->conversation->id,
                'user_id' => auth()->id(),
                'body' => $this->body
            ]);

            $this->reset('body');

            #push the message
            $this->loadedMessages->push($createdMessage);


            #update the conversation model - for sorting in chatlist
            $this->conversation->updated_at = now();
            $this->conversation->save();

            #dispatch event 'refresh ' to chatlist 
            $this->dispatch('refresh')->to(ChatList::class);

            #broadcast message  
            $this->dispatchMessageCreatedEvent($createdMessage);
        }
        $this->reset('media', 'files', 'body');

        #scroll to bottom
        $this->dispatch('scroll-bottom');


        #remove reply just incase it is present 
        $this->removeReply();
    }

    /**
     * UnSend/Delete a message  */
    function unSendMessage(Message $message){


        #make sure user is authenticated
        abort_unless(auth()->check(), 401);

        #make sure user owns message
        abort_unless($message->sender_id==auth()->id(), 403);


        #remove message from collection

        $this->loadedMessages= $this->loadedMessages->reject(function ($loadedMessage) use ($message) {
            return $loadedMessage->id == $message->id;
        });

       // dd($this->loadedMessages);

        #delete message from database
        $message->delete();

    }

  

    //used to broadcast message sent to receiver
    protected function dispatchMessageCreatedEvent(Message $message)
    {

        //send broadcast message only to others 
        //we add try catch to avoid runtime error when broadcasting services are not connected
        try {
            broadcast(new MessageCreated($message))->toOthers();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /** Send Like as  message */
    public function sendLike()
    {


        #rate limit 
        $this->rateLimit();

        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'attachment_id' => null,
            'user_id' => auth()->id(),
            'body' => '❤️'
        ]);


        #update the conversation model - for sorting in chatlist
        $this->conversation->updated_at = now();
        $this->conversation->save();

        #push the message
        $this->loadedMessages->push($message);

        #dispatch event 'refresh ' to chatlist 
        $this->dispatch('refresh')->to(ChatList::class);

        #scroll to bottom
        $this->dispatch('scroll-bottom');



        #dispatch event 
        $this->dispatchMessageCreatedEvent($message);
    }

    // #[On('loadMore')]
    function loadMore()
    {
        //dd('reached');

        #increment
        $this->paginate_var += 10;
        #call loadMessage
        $this->loadMessages();

        #dispatch event- update height
        $this->dispatch('update-height');
    }


    function loadMessages()
    {

        #get count
        $count = Message::where('conversation_id', $this->conversation->id)->where(function ($query) {
          //  $query->whereNotDeleted();
        })->count();

        #skip and query
        $this->loadedMessages = Message::where('conversation_id', $this->conversation->id)
            ->where(function ($query) {
              //  $query->whereNotDeleted();
            })
            ->with('parent')
            ->skip($count - $this->paginate_var)
            ->take($this->paginate_var)
            ->get();

        // Calculate whether more messages can be loaded
        $this->canLoadMore = $count > count($this->loadedMessages);



        return $this->loadedMessages;
    }

    /* to generate color auth message background color */
    public function getAuthMessageBodyColor(): string
    {

        $color = config('wirechat.theme', 'blue');

        return 'bg-' . $color . '-500';
    }

    public function mount()
    {
        //auth 
        abort_unless(auth()->check(), 401);

        //assign converstion
        $this->conversation = Conversation::where('id', $this->conversation)->first();


        //Abort if not made 
        abort_unless($this->conversation, 404);


        //dd( $this->conversation);
        // Check if the user belongs to the conversation
        $belongsToConversation = $this->conversation->participants()
        ->where('user_id', auth()->id())
        ->exists();

        abort_unless($belongsToConversation, 403);

        $this->receiver = $this->conversation->getReceiver();

        $this->authMessageBodyColor = $this->getAuthMessageBodyColor();

        $this->loadMessages();
    }

    public function render()
    {
        $conversation = Conversation::first();

        return view('wirechat::livewire.chat.chat-box');
    }
}
