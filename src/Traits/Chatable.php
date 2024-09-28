<?php

namespace Namu\WireChat\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Facades\WireChat;
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

    // public function __construct()
    // {
    //     dd('Chatable trait loaded');
    // }
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

        // Check if this is a self-conversation (both participants are the authenticated user)
        $selfConversationCheck = $participantId === $authenticatedUserId && $participantType === $authenticatedUserType;

        // Define the base query for finding conversations
        $existingConversationQuery = Conversation::withoutGlobalScope(WithoutClearedScope::class)->where('type', ConversationType::PRIVATE);

        // If it's a self-conversation, adjust the query to check for two identical participants
        if ($selfConversationCheck) {
            $existingConversationQuery->whereHas('participants', function ($query) use ($authenticatedUserId, $authenticatedUserType) {
                $query->select('conversation_id')
                    ->where('participantable_id', $authenticatedUserId)
                    ->where('participantable_type', $authenticatedUserType)
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(*) = 2'); // Ensuring two participants in the conversation
            });
        } else {
            // If it's a conversation between two different participants, adjust the query accordingly
            $existingConversationQuery->whereHas('participants', function ($query) use ($authenticatedUserId, $authenticatedUserType, $participantId, $participantType) {
                $query->select('conversation_id')
                    ->whereIn('participantable_id', [$authenticatedUserId, $participantId])
                    ->whereIn('participantable_type', [$authenticatedUserType, $participantType])
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(DISTINCT participantable_id) = 2'); // Ensure both participants are different
            });
        }

        // Execute the query and get the first matching conversation
        $existingConversation = $existingConversationQuery->first();

        // If a conversation is found, return it
        if ($existingConversation) {
            return $existingConversation;
        }

        // Otherwise, create a new conversation
        $existingConversation = Conversation::create([
            'type' => ConversationType::PRIVATE,
            'user_id' => $authenticatedUserId,
        ]);

        // Add the participants
        Participant::create([
            'conversation_id' => $existingConversation->id,
            'participantable_id' => $authenticatedUserId,
            'participantable_type' => $authenticatedUserType,
        ]);

        Participant::create([
            'conversation_id' => $existingConversation->id,
            'participantable_id' => $participantId,
            'participantable_type' => $participantType,
        ]);


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
                'sendable_id' => $this->id, // Polymorphic sender ID
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
     * Accessor Returns the URL for the user's cover image (used as an avatar).
     * Customize this based on your avatar field.
     *
     * @return string|null
     */
    public function getCoverUrlAttribute(): ?string
    {
        return  null;  // Adjust 'avatar_url' to your field
    }

    /**
     * Accessor Returns the URL for the user's profile page.
     * Customize this based on your routing or profile setup.
     *
     * @return string|null
     */
    public function getProfileUrlAttribute(): ?string
    {
        return null;  // Adjust 'profile' route as needed
    }

    /**
     * Accessor Returns the display name for the user.
     * Customize this based on your display name field.
     *
     * @return string|null
     */
    public function getDisplayNameAttribute(): ?string
    {
        return $this->name ?? 'user';  // Adjust 'name' field if needed
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
        })->where('sendable_id', '!=', $this->id)->where('sendable_type', get_class($this));

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
        // Check if participants are already loaded
        if ($conversation->relationLoaded('participants')) {
            // If loaded, simply check the existing collection
            return $conversation->participants->contains(function ($participant) {
                return $participant->participantable_id == $this->id &&
                    $participant->participantable_type == get_class($this);
            });
        }

        // If not loaded, perform the query
        return $conversation->participants()
            ->where('participantable_id', $this->id)
            ->where('participantable_type', get_class($this))
            ->exists();
    }

    /**
     * Delete a conversation
     */
    public function deleteConversation(Conversation $conversation)
    {


        //use already created methods inside conversation model 
        $conversation->deleteFor($this);
    }

    /**
     * Check if the user has a private conversation with another user.
     *
     * @param Model $user
     * @return bool
     */
    public function hasConversationWith(Model $user): bool
    {

        $participantId = $user->id;
        $participantType = get_class($user);

        $authenticatedUserId = $this->id;
        $authenticatedUserType = get_class($this);

        // Check if this is a self-conversation (both participants are the authenticated user)
        $selfConversationCheck = $participantId === $authenticatedUserId && $participantType === $authenticatedUserType;

        // Define the base query for finding conversations
        $existingConversationQuery = Conversation::withoutGlobalScope(WithoutClearedScope::class)->where('type', ConversationType::PRIVATE);

        // If it's a self-conversation, adjust the query to check for two identical participants
        if ($selfConversationCheck) {
            $existingConversationQuery->whereHas('participants', function ($query) use ($authenticatedUserId, $authenticatedUserType) {
                $query->select('conversation_id')
                    ->where('participantable_id', $authenticatedUserId)
                    ->where('participantable_type', $authenticatedUserType)
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(*) = 2'); // Ensuring two participants in the conversation
            });
        } else {
            // If it's a conversation between two different participants, adjust the query accordingly
            $existingConversationQuery->whereHas('participants', function ($query) use ($authenticatedUserId, $authenticatedUserType, $participantId, $participantType) {
                $query->select('conversation_id')
                    ->whereIn('participantable_id', [$authenticatedUserId, $participantId])
                    ->whereIn('participantable_type', [$authenticatedUserType, $participantType])
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(DISTINCT participantable_id) = 2'); // Ensure both participants are different
            });
        }

        // Execute the query and get the first matching conversation
        return $existingConversationQuery->exists();

      
    }



    /**
     * Search for users when creating a conversation.
     * This method can be overridden and customized to apply additional filtering logic.
     *
     * @param string $query The search input to match against user fields.
     * @return Collection|null A collection of users matching the query, or null if no matches are found.
     */
    public function searchUsers(string $query): ?Collection
    {
        // Retrieve the fields that are searchable for users.
        $searchableFields = WireChat::searchableFields();

        // Get the user model from the config, defaulting to the App\Models\User class.
        $userModel = app(config('wirechat.user_model', \App\Models\User::class));

        // If the search query or user model is empty, return null.
        if (blank($query) || !$userModel) {
            return null;
        }

        // Perform the search by matching the query against any of the searchable fields.
        // Limit the results to 20 users.
        return $userModel::where(function ($queryBuilder) use ($searchableFields, $query) {
            foreach ($searchableFields as $field) {
                $queryBuilder->orWhere($field, 'LIKE', '%' . $query . '%');
            }
        })
            //  ->where('id', '!=', $this->id) // Exclude the authenticated user
            ->limit(20)
            ->get();
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
