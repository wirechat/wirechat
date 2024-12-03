<?php

namespace Namu\WireChat\Livewire\Chat;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
//use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\MessageType;
use Namu\WireChat\Events\BroadcastMessageEvent;
use Namu\WireChat\Events\MessageCreated;
use Namu\WireChat\Events\MessageDeleted;
use Namu\WireChat\Events\NotifyParticipant;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Jobs\NotifyParticipants;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Models\Scopes\WithoutDeletedScope;
use Namu\WireChat\Notifications\NewMessageNotification;

class Chat extends Component
{
    use WithFileUploads;
    use WithPagination;

    public  $conversation;

    public $conversationId;

    #[Locked]
    public $TYPE;

    public $receiver;

    public $body;

    public $loadedMessages;

    public int $paginate_var = 10;

    public bool $canLoadMore;
    #[Locked]
    public $totalMessageCount;
    public array $media = [];

    public array $files = [];

    public ?Participant $authParticipant;

   // #[Locked]
    public ?Participant $receiverParticipant;

    //Theme
    public $replyMessage = null;

    public function getListeners()
    {
        // dd($this->conversation);
        return [
            'refresh' => '$refresh',
            //  'echo-private:conversation.' .$this->conversation->id. ',.Namu\\WireChat\\Events\\MessageDeleted' => 'removeDeletedMessage',
        ];
    }

    /**
     * Method to remove message from group key
     */
    public function removeDeletedMessage($event)
    {
        //before appending message make sure it belong to this conversation
        if ($event['message']['conversation_id'] == $this->conversation->id) {

            //Make sure message does not belong to auth
            // Make sure message does not belong to auth
            if ($event['message']['sendable_id'] == auth()->id() && $event['message']['sendable_type'] === get_class(auth()->user())) {
                return null;
            }

            // The $messageId is the ID of the message you want to remove
            $messageId = $event['message']['id'];

            foreach ($this->loadedMessages as $groupKey => $messages) {
                // Remove the message from the specific group
                $this->loadedMessages[$groupKey] = $messages->reject(function ($loadedMessage) use ($messageId) {
                    return $loadedMessage->id == $messageId;
                })->values();

                // Optionally, remove the group if it's empty
                if ($this->loadedMessages[$groupKey]->isEmpty()) {
                    $this->loadedMessages->forget($groupKey);
                }
            }

            // Dispatch refresh event
            $this->dispatch('refresh')->to(Chats::class);

        }
    }

    //handle incomming broadcasted message event
    public function appendNewMessage($event)
    {

        //before appending message make sure it belong to this conversation
        if ($event['message']['conversation_id'] == $this->conversation->id) {

            //scroll to bottom
            $this->dispatch('scroll-bottom');

            $newMessage = Message::find($event['message']['id']);

            //Make sure message does not belong to auth
            // Make sure message does not belong to auth
            if ($newMessage->sendable_id == auth()->id() && $newMessage->sendable_type === get_class(auth()->user())) {
                return null;
            }

            //push message
            $this->pushMessage($newMessage);

            //mark as read
            $this->conversation->markAsRead();

            //refresh chatlist
            //dispatch event 'refresh ' to chatlist
            $this->dispatch('refresh')->to(Chats::class);

            //broadcast
            // $this->selectedConversation->getReceiver()->notify(new MessageRead($this->selectedConversation->id));
        }
    }

    //   function testable($event)  {

    //     dd($event);

    //   }

    /**
     * Todo: Authorize the property
     * Todo: or lock it
     * todo:Check if user can reply to this message
     * Set replyMessage as Message Model
     *  */
    public function setReply(Message $message)
    {
        //check if user belongs to message
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);

        //abort if message does not belong to this conversation or is not owned by any participant
        abort_unless($message->conversation_id == $this->conversation->id, 403);

        //Set owner as Id we are replying to
        $this->replyMessage = $message;

        //dispatch event to focus input field
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
     * todo:uncomment if used this in fronend
     */
    public function _finishUpload($name, $tmpPath, $isMultiple)
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

    public function resetAttachmentErrors()
    {

        $this->resetErrorBag(['media', 'files']);

    }

    public function listenBroadcastedMessage($event)
    {

        // dd('reached');
        $this->dispatch('scroll-bottom');
        $newMessage = Message::find($event['message_id']);

        //push message
        $this->pushMessage($newMessage);

        //mark as read
        $newMessage->read_at = now();
        $newMessage->save();
    }

    /**
     * Delete conversation  */
    public function deleteConversation()
    {
        abort_unless(auth()->check(), 401);

        //delete conversation
        $this->conversation->deleteFor(auth()->user());

        //redirect to chats page
        $this->redirectRoute(WireChat::indexRouteName());
    }

    /**
     * Delete conversation  */
    public function clearConversation()
    {
        abort_unless(auth()->check(), 401);

        //delete conversation
        $this->conversation->clearFor(auth()->user());

        $this->reset('loadedMessages', 'media', 'files', 'body');

        //redirect to chats page
        $this->redirectRoute(WireChat::indexRouteName());
    }

    public function exitConversation()
    {
        abort_unless(auth()->check(), 401);

        $auth = auth()->user();

        //make sure owner if group cannot be removed from chat
        abort_if($auth->isOwnerOf($this->conversation), 403, 'Owner cannot exit conversation');

        //delete conversation
        $auth->exitConversation($this->conversation);

        //redirect to chats page
        $this->redirectRoute(WireChat::indexRouteName());
    }

    protected function rateLimit()
    {
        $perMinute = 60;

        if (RateLimiter::tooManyAttempts('send-message:'.auth()->id(), $perMinute)) {

            return abort(429, 'Too many attempts!, Please slow down');
        }

        RateLimiter::increment('send-message:'.auth()->id());
    }

    /**
     * Send a message  */
    public function sendMessage()
    {

        //dd($this->body);

        abort_unless(auth()->check(), 401);

        //rate limit
        $this->rateLimit();

        /* If media is empty then conitnue to validate body , since media can be submited without body */
        // Combine media and files arrays

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
                    'files' => "max:$maxUploads|nullable",
                    'files.*' => "mimes:$fileMimes|max:$fileMaxUploadSize",
                    'media' => "max:$maxUploads|nullable",
                    'media.*' => "max:$mediaMaxUploadSize|mimes:$mediaMimes",

                ]);
            } catch (\Illuminate\Validation\ValidationException $th) {

                return $this->dispatch('wirechat-toast', type: 'warning', message: $th->getMessage());
            }

            //Combine media and files thne perform loop together

            $createdMessages = [];
            foreach ($attachments as $key => $attachment) {

                /**
                 * todo: Add url to table
                 */

                //save photo to disk
                $path = $attachment->store(config('wirechat.attachments.storage_folder', 'attachments'), config('wirechat.attachments.storage_disk', 'public'));

                // Determine the reply ID based on conditions
                $replyId = ($key === 0 && $this->replyMessage) ? $this->replyMessage->id : null;

                // Create the message
                $message = Message::create([
                    'reply_id' => $replyId,
                    'conversation_id' => $this->conversation->id,
                    'sendable_type' => get_class(auth()->user()), // Polymorphic sender type
                    'sendable_id' => auth()->id(), // Polymorphic sender ID
                    'type' => MessageType::ATTACHMENT,
                    // 'body' => $this->body, // Add body if required
                ]);

                // Create and associate the attachment with the message
                $message->attachment()->create([
                    'file_path' => $path,
                    'file_name' => basename($path),
                    'original_name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'url' => Storage::url($path),
                ]);

                //append message to createdMessages
                $createdMessages[] = $message;

                //update the conversation model - for sorting in chatlist
                $this->conversation->updated_at = now();
                $this->conversation->save();

                //dispatch event 'refresh ' to chatlist
                $this->dispatch('refresh')->to(Chats::class);

                //broadcast message
                $this->dispatchMessageCreatedEvent($message);
            }

            //push the message
            foreach ($createdMessages as $key => $message) {
                // code...

                $this->pushMessage($message);
            }

            //scroll to bottom
            $this->dispatch('scroll-bottom');
        }

        if ($this->body != null) {

            $createdMessage = Message::create([
                'reply_id' => $this->replyMessage?->id,
                'conversation_id' => $this->conversation->id,
                'sendable_type' => get_class(auth()->user()), // Polymorphic sender type
                'sendable_id' => auth()->id(), // Polymorphic sender ID
                'body' => $this->body,
                'type' => MessageType::TEXT,
            ]);

            //push the message
            $this->pushMessage($createdMessage);

            //update the conversation model - for sorting in chatlist

            $this->conversation->touch();

            //broadcast message
            $this->dispatchMessageCreatedEvent($createdMessage);

            //dispatch event 'refresh ' to chatlist
            $this->dispatch('refresh')->to(Chats::class);

        }

        //     dd('hoting');

        $this->reset('media', 'files', 'body');

        //scroll to bottom

        $this->dispatch('scroll-bottom');

        //remove reply just incase it is present
        $this->removeReply();

        //reset expred conversation deletion
        // $this->removeExpiredConversationDeletion();

    }

    /**
     * Delete for me means any participant of the conversation  can delete the message
     * and this will hide the message from them but other participants can still access/see it
     **/
    public function deleteForMe(Message $message)
    {

        //make sure user is authenticated
        abort_unless(auth()->check(), 401);

        //make sure user belongs to conversation from the message
        //We are checking the $message->conversation for extra security because the param might be tempered with
        abort_unless(auth()->user()->belongsToConversation($message->conversation), 403);

        //remove message from collection
        $this->removeMessage($message);

        //dispatch event 'refresh ' to chatlist
        $this->dispatch('refresh')->to(Chats::class);

        //delete For $user
        $message->deleteFor(auth()->user());
    }

    /**
     * Delete for eveyone means only owner of messages &  participant of the conversation  can delete the message
     * and this will completely delete the message from the database
     * Unless it has a foreign key child or parent :then it i will be soft deleted
     **/
    public function deleteForEveryone(Message $message)
    {

        $authParticipant = $this->conversation->participant(auth()->user());

        //make sure user is authenticated

        abort_unless(auth()->check(), 401);

        //make sure user owns message OR allow if is admin in group
        abort_unless($message->ownedBy(auth()->user()) || ($authParticipant->isAdmin() && $this->conversation->isGroup()), 403);

        //make sure user belongs to conversation from the message
        //We are checking the $message->conversation for extra security because the param might be tempered with
        abort_unless(auth()->user()->belongsToConversation($message->conversation), 403);

        //remove message from collection
        $this->removeMessage($message);

        //dispatch event 'refresh ' to chatlist
        $this->dispatch('refresh')->to(Chats::class);

        try {
            MessageDeleted::dispatch($message, $this->conversation);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
        }
        //event(new MessageDeleted($message,$this->conversation));
        // broadcast(new MessageDeleted($message,$this->conversation))->toOthers();
        //if message has reply then only soft delete it
        if ($message->hasReply()) {

            //delete message from database
            $message->delete();
        } else {

            //else Force delete message from database
            $message->forceDelete();
        }
    }

    //Helper method to get group key
    private function messageGroupKey(Message $message): string
    {

        $messageDate = $message->created_at;
        $groupKey = '';
        if ($messageDate->isToday()) {
            $groupKey = 'Today';
        } elseif ($messageDate->isYesterday()) {
            $groupKey = 'Yesterday';
        } elseif ($messageDate->greaterThanOrEqualTo(now()->subDays(7))) {
            $groupKey = $messageDate->format('l'); // Day name
        } else {
            $groupKey = $messageDate->format('d/m/Y'); // Older than 7 days, dd/mm/yyyy
        }

        return $groupKey;
    }

    //helper to push message to loadedMessages
    private function pushMessage(Message $message)
    {
        $groupKey = $this->messageGroupKey($message);

        // Ensure loadedMessages is a Collection
        $this->loadedMessages = collect($this->loadedMessages);

        // Use tap to create a new group if it doesn’t exist, then push the message
        $this->loadedMessages->put($groupKey, $this->loadedMessages->get($groupKey, collect())->push($message));
    }

    //Method to remove method from collection
    private function removeMessage(Message $message)
    {

        $groupKey = $this->messageGroupKey($message);

        // Remove the message from the correct group
        if ($this->loadedMessages->has($groupKey)) {
            $this->loadedMessages[$groupKey] = $this->loadedMessages[$groupKey]->reject(function ($loadedMessage) use ($message) {
                return $loadedMessage->id == $message->id;
            })->values();

            // Optionally, remove the group if it's empty
            if ($this->loadedMessages[$groupKey]->isEmpty()) {
                $this->loadedMessages->forget($groupKey)->values();
            }

            //  $this->loadedMessages;
        }
    }

    //used to broadcast message sent to receiver
    protected function dispatchMessageCreatedEvent(Message $message)
    {

        //Dont dispatch if it is a selfConversation

        if ($this->conversation->isSelfConversation(auth()->user())) {

            return null;
        }

        // send broadcast message only to others
        // we add try catch to avoid runtime error when broadcasting services are not connected
        // todo create a job to broadcast multiple messages
        try {

            // event(new BroadcastMessageEvent($message,$this->conversation));

            //!remove the receiver from the messageCreated and add it to the job instead
            //!also do not forget to exlude auth user or message owner from particpants
            // sleep(3);
            broadcast(new MessageCreated($message))->toOthers();

            //if conversation is private then Notify particpant immediately
            if ($this->conversation->isPrivate() || $this->conversation->isSelf()) {

                if ($this->conversation->isPrivate() && $this->receiverParticipant) {

                    broadcast(new NotifyParticipant($this->receiverParticipant, $message))->toOthers();
                    //    Notification::send($this->receiver, new NewMessageNotification($message));

                }

            } else {
                // code...
                NotifyParticipants::dispatch($this->conversation, $message);

            }

        } catch (\Throwable $th) {

            Log::error($th->getMessage());
        }
    }

    /** Send Like as  message */
    public function sendLike()
    {

        //sleep(2);

        //rate limit
        $this->rateLimit();

        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sendable_type' => get_class(auth()->user()), // Polymorphic sender type
            'sendable_id' => auth()->id(), // Polymorphic sender ID
            'body' => '❤️',
            'type' => MessageType::TEXT,
        ]);

        //update the conversation model - for sorting in chatlist
        $this->conversation->updated_at = now();
        $this->conversation->save();

        //push the message
        $this->pushMessage($message);

        //dispatch event 'refresh ' to chatlist
        $this->dispatch('refresh')->to(Chats::class);

        //scroll to bottom
        $this->dispatch('scroll-bottom');

        //dispatch event
        $this->dispatchMessageCreatedEvent($message);
    }

    // load more messages
    public function loadMore()
    {
        //increment
        $this->paginate_var += 10;
        //call loadMessage
        $this->loadMessages();

        //dispatch event- update height
        $this->dispatch('update-height');
    }

    public function loadMessages()
    {
        // Get total message count

        // Fetch paginated messages
        $messages = $this->conversation->messages()
            ->with('sendable', 'parent','attachment')
            ->orderBy('created_at', 'asc')
            ->skip( $this->totalMessageCount - $this->paginate_var)
            ->take($this->paginate_var)
            ->get();  // Fetch messages as Eloquent collection

        // Calculate whether more messages can be loaded
        // Group the messages
        $this->loadedMessages = $messages
            ->groupBy(fn ($message) => $this->messageGroupKey($message))  // Grouping by custom logic
            ->map->values();  // Re-index each group

        $this->canLoadMore =  $this->totalMessageCount > $messages->count();

        return $this->loadedMessages;

    }

    public function placeholder()
    {
        return view('wirechat::components.placeholders.chat');
    }

    public function mount()
    {
        $this->initializeConversation();
        $this->initializeParticipants();
        $this->finalizeConversationState();
        $this->loadMessages();
    }
    
    private function initializeConversation()
    {
        abort_unless(auth()->check(), 401);
    
        $this->conversation = Conversation::withoutGlobalScopes([WithoutDeletedScope::class])
            ->where('id', $this->conversation)
            ->firstOr(fn() => abort(404));
    
        $this->totalMessageCount = Message::where('conversation_id', $this->conversation->id)->count();
        abort_unless(auth()->user()->belongsToConversation($this->conversation), 403);
    
        $this->conversationId = $this->conversation->id;
    }
    
    private function initializeParticipants()
    {
        if (in_array($this->conversation->type, [ConversationType::PRIVATE, ConversationType::SELF])) {
            $this->conversation->load('participants');
            $participants = $this->conversation->participants;
    
            $this->authParticipant = $participants
                ->where('participantable_id', auth()->id())
                ->first();
    
            $this->receiverParticipant = $participants
                ->where('participantable_id', '!=', auth()->id())
                ->first();

            //If conversation is self then receiver is auth;
            if ($this->conversation->type== ConversationType::SELF) {
                $this->receiverParticipant = $this->authParticipant;
            }
    
            $this->receiver = $this->receiverParticipant
                ? $this->receiverParticipant->participantable
                : null;
        } else {
            $this->authParticipant = Participant::whereParticipantable(auth()->user())->first();
            $this->receiver = null;
        }
    }
    
    private function finalizeConversationState()
    {
        $this->conversation->markAsRead();
    
        if ($this->authParticipant) {
            $this->authParticipant->update(['last_active_at' => now()]);
    
            if ($this->authParticipant->hasDeletedConversation(true)) {
                $this->authParticipant->update(['conversation_deleted_at' => null]);
            }
        }
    }
    

    public function render()
    {
           // $this->conversation->turnOnDisappearing(3600);
        return view('wirechat::livewire.chat.chat');
    }
}
