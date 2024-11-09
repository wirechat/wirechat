<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Namu\WireChat\Enums\ParticipantRole;
use Namu\WireChat\Facades\WireChat;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'participantable_id',
        'participantable_type',
        'role',
        'exited_at',
        'conversation_deleted_at'
    ];


    protected $casts=[
        'role'=>ParticipantRole::class,
        'exited_at'=>'date',
        'conversation_deleted_at'=>'date'
    ];


    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('participants');


        parent::__construct($attributes);
    }


      /**
     * Scope to exclude exited participants by default.
     */
    protected static function booted()
    {
        static::addGlobalScope('withoutExited', function ($query) {
            $query->whereNull('exited_at');
        });
    }

    /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\ParticipantFactory::new();
    }

    

    /**
     * Polymorphic relation to the participantable model.
     */
    public function participantable()
    {
        return $this->morphTo();
    }

    /**
     * Define a relationship to fetch the conversation.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }


    /**
     * Check if participant is admin
     **/
    function isAdmin()  {

        return $this->role ==ParticipantRole::OWNER || $this->role ==ParticipantRole::ADMIN;

    }

    /**
     * Check if participant is owner of conversation
     **/
    function isOwner()  {

        return $this->role ==ParticipantRole::OWNER;

    }


    /**
     * Mark the participant as exited from the conversation.
     *
     * @param null
     * @return bool
     */
    public function exitConversation(): bool
    {
        #make sure conversation is not private
        abort_if($this->conversation->isPrivate(),403,"Participant cannot exited a private conversation");

        #make sure owner if group cannot be removed from chat
        abort_if($this->isOwner(),403,"Owner cannot exit conversation");


  #delete messages|conversation
  $this->participantable->deleteConversation($this->conversation);


        if (!$this->hasExited()) {
            $this->exited_at = now();
            return $this->save();
        }
        
      
        return false; // Already exited or conversation mismatch
    }
    
    /**
     * Check if the participant has exited the conversation.
     */
    public function hasExited(): bool
    {
        return self::withoutGlobalScopes()
                ->where('id', $this->id)
                ->whereNotNull('exited_at')
                ->exists();
    }



}
