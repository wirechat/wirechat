<?php

namespace Namu\WireChat\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Namu\WireChat\Enums\ConversationType;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Enums\RoomType;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Group;
use Namu\WireChat\Models\Message;
use Namu\WireChat\Models\Participant;
use Namu\WireChat\Models\Read;
use Namu\WireChat\Models\Room;
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
            (new Participant)->getTable(), // The participants table
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
        $existingConversationQuery = Conversation::withoutGlobalScopes()->where('type', ConversationType::PRIVATE);

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
            'type' => ConversationType::PRIVATE
        ]);

        // Add the participants
        Participant::create([
            'conversation_id' => $existingConversation->id,
            'participantable_id' => $authenticatedUserId,
            'participantable_type' => $authenticatedUserType,
            'role' => ParticipantRole::OWNER
        ]);

        Participant::create([
            'conversation_id' => $existingConversation->id,
            'participantable_id' => $participantId,
            'participantable_type' => $participantType,
            'role' => ParticipantRole::OWNER
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
     * Room configuration
     *
     */




    /**
     * Create group
     */
    public function createGroup(string $name, string $description = null, UploadedFile $photo = null): Conversation
    {


        //create rooom
        #Otherwise, create a new conversation
        $conversation = Conversation::create([
            'type' => ConversationType::GROUP
        ]);

        #create room 
        $group = $conversation->group()->create([
            'name' => $name,
            'description' => $description
        ]);


        #create and save photo is present
        if ($photo) {
            #save photo to disk 
            $path =  $photo->store(WireChat::storageFolder(), WireChat::storageDisk());

            #create attachment
            $group->cover()->create([
                'file_path' => $path,
                'file_name' => basename($path),
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
                'url' =>  Storage::url($path)
            ]);
        }

        #create participant as owner
        Participant::create([
            'conversation_id' => $conversation->id,
            'participantable_id' => $this->id,
            'participantable_type' => get_class($this),
            'role' => ParticipantRole::OWNER
        ]);


        return $conversation;
    }


    /**
     * Exit a chat:group|channel by marking the user's participant record as exited.
     *
     * @param Conversation $conversation
     * @return bool
     */
    public function exitConversation(Conversation $conversation): bool
    {

        #get participant
        $participant = $conversation->participant($this);

        return $participant ? $participant->exitConversation() : false;
    }





    /**
     * Creates a conversation if one doesn't already exist with the recipient model,
     * or uses an existing conversation directly, and sends the attached message.
     * Works with both private and group conversations in a polymorphic manner.
     * 
     * @param Model $model - The recipient model or conversation instance
     * @param string $message - The message content to send
     * @return Message|null
     */

    public  function sendMessageTo(Model $model, string $message)
    {
        // Check if the recipient is a model (polymorphic) and not a conversation
        if (!$model instanceof Conversation) {
            // Ensure the model has the required trait
            if (!in_array(Chatable::class, class_uses($model))) {
                return null;
            }
            // Create or get a private conversation with the recipient
            $conversation = $this->createConversationWith($model);
        } else {
            // If it's a Conversation, use it directly
            $conversation = $model;

            // Optionally, check that the current model is part of the conversation
            if (!$this->belongsToConversation($conversation)) {

                return null; // Exit if not a participant
            }
        }

        // Proceed to create the message if a valid conversation is found or created
        if ($conversation) {

            $createdMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sendable_type' => get_class($this), // Polymorphic sender type
                'sendable_id' => $this->id, // Polymorphic sender ID
                'body' => $message
            ]);


            // Update the conversation timestamp
            $conversation->updated_at = now();
            $conversation->save();


            return $createdMessage;
        }

        return null;
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
     * Get unread messages count for the user, across all conversations or within a specific conversation.
     *
     * @param Conversation|null $conversation
     * @return int
     */
    public function getUnreadCount(Conversation $conversation = null): int
    {
        // If a specific conversation is provided, use the conversation's getUnreadCountFor method
        if ($conversation) {
            return $conversation->getUnreadCountFor($this);
        }

        // If no conversation is provided, calculate unread messages across all user conversations
        $totalUnread = 0;

        foreach ($this->conversations as $conv) {
            $totalUnread += $conv->getUnreadCountFor($this);
        }

        return $totalUnread;
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


    public function clearConversation(Conversation $conversation)
    {

        //use already created methods inside conversation model 
        $conversation->clearFor($this);
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
     * Check if the user has deleted a conversation.
     *
     * @param Conversation $conversation The conversation to check for deletion status.
     * @param bool $checkDeletionExpired Optional. When true, checks if the deletion has "expired."
     *     Deletion is considered expired if the conversation has been updated after it was deleted by the user.
     *     Default is false, which checks only if the conversation has been deleted, regardless of updates.
     *
     * @return bool True if the conversation is deleted, false otherwise.
     */
    function hasDeletedConversation(Conversation $conversation, bool $checkDeletionExpired = false): bool
    {
        $participant = $conversation->participant($this);
        return $participant?->hasDeletedConversation($checkDeletionExpired);
    }



    function conversationDeletionExpired(Conversation $conversation) : bool {

     return   $this->hasDeletedConversation($conversation,true);
        
    }


    /**
     * Search for users who are eligible to participate in a conversation.
     * This method can be customized to include additional filtering logic, 
     * such as limiting results to friends, followers, or other specific groups.
     *
     * @param string $query The search term to match against user fields.
     * @return Collection|null A collection of users matching the search criteria, 
     *                         or null if no matches are found.
     */
    public function searchChatables(string $query): ?Collection
    {
        // Retrieve the fields that are searchable for users.
        $searchableFields = WireChat::searchableFields();

        // Get the user model from the configuration, defaulting to App\Models\User.
        $userModel = app(config('wirechat.user_model', \App\Models\User::class));

        // Return null if the search query is blank or the user model is unavailable.
        if (blank($query) || !$userModel) {
            return null;
        }

        // Initialize cache for column checks.
        $columnCache = [];

        return $userModel::where(function ($queryBuilder) use ($searchableFields, $query, &$columnCache) {
            // Get the table name for the user model.
            $table = $queryBuilder->getModel()->getTable();

            // Iterate over searchable fields.
            foreach ($searchableFields as $field) {
                // Check if column existence is already cached for the table.
                if (!isset($columnCache[$table])) {
                    $columnCache[$table] = Schema::getColumnListing($table);
                }

                // Only perform the search if the field exists in the table.
                if (in_array($field, $columnCache[$table])) {
                    $queryBuilder->orWhere($field, 'LIKE', '%' . $query . '%');
                }
            }
        })
            //  ->where('id', '!=', $this->id) // Optionally exclude the current user.
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



    /* Checking roles in conversation */

    /**
     * Check if the user is an admin in a specific conversation.
     */
    public function isAdminInGroup(Group $group): bool
    {
        $conversation = $group->conversation;
        // Check if participants are already loaded
        if ($conversation->relationLoaded('participants')) {
            // If loaded, simply check the existing collection
            return $conversation->participants->contains(function ($participant) {
                return $participant->participantable_id == $this->id &&
                    $participant->participantable_type == get_class($this) &&
                    in_array($participant->role, [ParticipantRole::OWNER, ParticipantRole::ADMIN]);
            });
        }

        // If not loaded, perform the query
        return $conversation->participants()
            ->where('participantable_id', $this->id)
            ->where('participantable_type', get_class($this))
            ->whereIn('role', [ParticipantRole::OWNER, ParticipantRole::ADMIN])
            ->exists();
    }

    /**
     * Check if the user is the owner of a specific conversation.
     */
    public function isOwnerOfConversation(Conversation $conversation): bool
    {
        // Check if participants are already loaded
        // if ($conversation->relationLoaded('participants')) {
        //     // If loaded, simply check the existing collection
        //     return $conversation->participants()->withoutGlobalScopes()->contains(function ($participant) {
        //         return $participant->participantable_id == $this->id &&
        //             $participant->participantable_type == get_class($this) &&
        //             $participant->role == ParticipantRole::OWNER;
        //     });
        // }

        // If not loaded, perform the query
        return $conversation->participants()
            ->withoutGlobalScopes()
            ->where('participantable_id', $this->id)
            ->where('participantable_type', get_class($this))
            ->where('role', ParticipantRole::OWNER)
            ->exists();
    }


    /**
     * Check if the user is the owner of a specific conversation.
     */
    public function isOwnerOfGroup(Group $group): bool
    {
        $conversation = $group->conversation;

        // Check if participants are already loaded
        if ($conversation->relationLoaded('participants')) {
            // If loaded, simply check the existing collection
            return $conversation->participants->contains(function ($participant) {
                return $participant->participantable_id == $this->id &&
                    $participant->participantable_type == get_class($this) &&
                    $participant->role == ParticipantRole::OWNER;
            });
        }

        // If not loaded, perform the query
        return $conversation->participants()
            ->where('participantable_id', $this->id)
            ->where('participantable_type', get_class($this))
            ->where('role', ParticipantRole::OWNER)
            ->exists();
    }







    /**
     * Actions Permissions
     * You can override the following to determine if user can perform these actions
     */

    /**
     *  Check if user is allowd to send messages or interact with conversatoin
     */
    function canInteractWithConversation(Conversation $conversation): bool
    {

        return $this->belongsToConversation($conversation);
    }

    function canCreateNewGroups(): bool
    {

        return true;
    }

    function canCreateNewChats(): bool
    {

        return true;
    }
}
