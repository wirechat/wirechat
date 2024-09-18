<?php

namespace Namu\WireChat\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Models\Scopes\WithoutClearedScope;

/**
 * Trait Chatable
 *
 * This trait defines the behavior for models that can participate in conversations within the WireChat system.
 * It provides methods to establish relationships with conversations, define cover images for avatars,
 * and specify the route for redirecting to the user's profile page.
 *
 * @package Namu\WireChat\Traits
 */
trait Chatable
{
    /**
     * Establishes a relationship between the user and conversations.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function conversations()
    {
        return $this->morphToMany(
            Conversation::class, // The related model
            'participantable',   // The polymorphic field (participantable_id & participantable_type)
            config('wirechat.participants_table', 'wirechat_participants'), // The participants table
            'participantable_id', // The foreign key on the participants table for the User model
            'conversation_id'     // The foreign key for the Conversation model
        )->withPivot('conversation_id'); // Optionally load conversation_id from the pivot table
    }
    
    /**
     * Creates a private conversation with another participant and adds participants.
     *
     * @param Model $participant The participant to create a conversation with
     * @param string|null $message The initial message (optional)
     * @return Conversation|null
     */
    public function createConversationWith(Model $participant, ?string $message = null)
    {
        $participantId = $participant->id;
        $participantType = get_class($participant);


        $authenticatedUserId = $this->id;
        $authenticatedUserType = get_class($this);

        # Check if a private conversation already exists with these two participants
        $existingConversation = Conversation::withoutGlobalScope(WithoutClearedScope::class)->where('type', ConversationType::PRIVATE)
            ->whereHas('participants', function ($query) use ($authenticatedUserId, $authenticatedUserType, $participantId, $participantType) {
               
                $query->select('conversation_id')
                    ->whereIn('participantable_id', [$authenticatedUserId, $participantId])
                    ->whereIn('participantable_type', [$authenticatedUserType, $participantType])
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(DISTINCT participantable_id) = 2');
            })->first();

        # If the conversation does not exist, create a new one
        if (!$existingConversation) {
            $existingConversation = Conversation::create([
                'type' => ConversationType::PRIVATE,
                'user_id' => $authenticatedUserId, // Assuming the authenticated user is the creator
            ]);

            # Add participants
           
             # Add participants using create
            // dd($authenticatedUserType);
            Participant::create([
                'conversation_id' => $existingConversation->id,
                'participantable_id' => $authenticatedUserId,
                'participantable_type' => $authenticatedUserType, // explicitly set type
            ]);
            
            Participant::create([
                'conversation_id' => $existingConversation->id,
                'participantable_id' => $participantId,
                'participantable_type' => $participantType, // explicitly set type
            ]);
     //   dd($existingConversation->participants()->get());
            
        }


        # Create the initial message if provided
        if (!empty($message) && $existingConversation != null) {
            Message::create([
                'sendable_id' => $authenticatedUserId,
                'sendable_type' => $authenticatedUserType,
                'conversation_id' => $existingConversation->id,
                'body' => $message
            ]);
        }

        return $existingConversation;
    }


    /**
     * Creates a conversation if one doesnt not already exists
     * And sends the attached message 
     * @return Message|null
     */

    function sendMessageTo(Model $user, string $message)
    {

        //Create or get converstion with user 
        $conversation = $this->createConversationWith($user);

        if ($conversation != null) {
            //create message
            $createdMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sendable_type' => get_class($this), // Polymorphic sender type
                'sendable_id' =>$this->id, // Polymorphic sender ID
                'body' => $message
            ]);
            // dd($createdMessage);

            /** 
             * update conversation :we use this in to show the conversation
             *  with the latest message at the top of the chatlist  */
            $conversation->updated_at = now();
            $conversation->save();

            return $createdMessage;
        }

        //make sure user belong to conversation

    }

 
    /**
     * Returns the URL for the cover image to be used as an avatar.
     *
     * @return string|null
     */
    public function wireChatCoverUrl(): ?string
    {
        return null;
    }

    /**
     * Returns the URL for the user's profile page.
     *
     * @return string|null
     */
    public function wireChatProfileUrl(): ?string
    {
        return null;
    }

    /**
     * Returns the display name for the user.
     *
     * @return string|null
     */
    public function wireChatDisplayName(): ?string
    {
        return $this->name ?? 'user';
    }

    /**
     * Get unread messages count.for user excluding user owned messages
     *
     * @param Conversation|null $conversation
     * @return int
     */
    public function getUnreadCount(Conversation $conversation = null): int
    {
        $query = Message::whereDoesntHave('reads', function ($q) {
               $q->where('readable_id', $this->id)
                 ->where('readable_type', get_class($this));
             })->where('sendable_id','!=', $this->id)->where('sendable_type', get_class($this));

        if ($conversation) {
            $query->where('conversation_id', $conversation->id);
        }

        return $query->count();
    }



    /**
     * Check if the user belongs to a conversation.
     */
    public function belongsToConversation(Conversation $conversation): bool
    {
        return $conversation->participants()
        ->where('participantable_id',$this->id)
        ->where('participantable_type',get_class($this))
        ->exists();
    }
    
    public function deleteConversation(Conversation $conversation)
    {

        $userId = $this->id;

        //Stop if user does not belong to conversation
        if (! $this->belongsToConversation($conversation)) {
            return null;
        }
        
        // Update the messages based on the current user
        $conversation->messages()->each(function ($message) use ($userId) {
            if ($message->sender_id === $userId) {
                $message->update(['sender_deleted_at' => now()]);
            } elseif ($message->receiver_id === $userId) {
                $message->update(['receiver_deleted_at' => now()]);
            }
        });

        // Delete the conversation and messages if all messages from the other user are also deleted
        if ($conversation->messages()
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->where(function ($query) {
                $query->whereNull('sender_deleted_at')
                    ->orWhereNull('receiver_deleted_at');
            })
            ->doesntExist()
        ) {

            // $conversation->messages()->delete();
            $conversation->forceDelete();
        }
    }


    /**
     * Check if the user has a private conversation with another user.
     *
     * @param Model $user
     * @return bool
     */
    public function hasConversationWith(Model $user): bool
    {
        $authenticatedUser= $this;
        $user = $user;


       return     Conversation::withoutGlobalScope(WithoutClearedScope::class)->where('type', ConversationType::PRIVATE)
                 ->whereHas('participants', function ($query) use ($authenticatedUser, $user) {
               
                $query->select('conversation_id')
                    ->whereIn('participantable_id', [$authenticatedUser->id, $user->id])
                    ->whereIn('participantable_type', [get_class($authenticatedUser), get_class($user)])
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(DISTINCT participantable_id) = 2');
            })->exists();
    }






    /**
     * Retrieve the searchable fields defined in configuration
     * and check if they exist in the database table schema.
     *
     * @return array|null The array of searchable fields or null if none found.
     */
    public function getWireSearchableFields(): ?array
    {
        // Define the fields specified as searchable in the configuration
        $fieldsToCheck = config('wirechat.user_searchable_fields');

        // Get the table name associated with the model
        $tableName = $this->getTable();

        // Get the list of columns in the database table
        $tableColumns = Schema::getColumnListing($tableName);

        // Filter the fields to include only those that exist in the table schema
        $searchableFields = array_intersect($fieldsToCheck, $tableColumns);

        return $searchableFields ?: null;
    }


    /**
     * Get all of the reads for the model (polymorphic).
     */
    // public function reads(): MorphMany
    // {
    //     return $this->morphMany(Read::class, 'readable');
    // }

}
