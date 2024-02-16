<?php

namespace Namu\WireChat\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Namu\WireChat\Models\Conversation;

trait Chatable

{

     /* Relationship for user can have conversations */
     public function conversations() :HasMany {

        return $this->hasMany(Conversation::class,'sender_id')->orWhere('receiver_id',$this->id);
        
    }


    /* Define cover image that will be used as avatar*/

    public function wireChatCoverUrl():?string
    {
      return null;
    }

    /* used as redirect to user's profile page*/
   public function usersRoute() :?string {

      return null;
      
    }


 }