<?php

namespace Namu\WireChat\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Namu\WireChat\Models\Conversation;

trait Chatable

{



     public function conversations() :HasMany {

        return $this->hasMany(Conversation::class,'sender_id')->orWhere('receiver_id',$this->id);
        
    }









 }