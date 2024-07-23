<?php

namespace Namu\WireChat\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'sender_id')->orWhere('receiver_id', $this->id);
    }

    /**
     * Creates a conversation with another user
     *
     * @return Conversation|null
     */
    public function createConversationWith(Model $user,?string $message=null)
    {

      $userId= $user->id;
      $authenticatedUserId = $this->id;


      # Check if conversation already exists
      $existingConversation = Conversation::where(function ($query) use ($authenticatedUserId, $userId) {
                $query->where('sender_id', $authenticatedUserId)
                    ->where('receiver_id', $userId);
                })
            ->orWhere(function ($query) use ($authenticatedUserId, $userId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', $authenticatedUserId);
            })->first();
      #if conversation does not exists then create a new one
      if (!$existingConversation) {
        # Create new conversation
       $existingConversation= Conversation::updateOrCreate([
            'sender_id' => $authenticatedUserId,
            'receiver_id' => $userId,
        ]);

      }

      if((!empty($message)|| $message!=null) && $existingConversation!= null){

       $createdMessage= Message::create([
            'sender_id'=>$authenticatedUserId,
            'receiver_id'=>$userId,
            'conversation_id'=>$existingConversation->id,
            'body'=>$message

        ]);
       // dd($createdMessage);
      }


      return $existingConversation;
  

    }


    /**
     * Creates a conversation if one doesnt not already exists
     * And sends the attached message 
     * @return Message|null
     */

     function sendMessageTo(Model $user,string $message)  {

        //Get or create new conversation
        $conversation=  $this->createConversationWith($user);




        if ($conversation!=null) {
            //dd($this->id,$user->id);
       // dd($conversation);
            
            //create message
            $createdMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $this->id,
                'receiver_id' => $user->id,
                'body' => $message
            ]);
           // dd($createdMessage);

            /** 
             * update conversation :we use this in to show the conversation
             *  with the latest message at the top of the chatlist  */
            $conversation->updated_at=now();
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

}
