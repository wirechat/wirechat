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
        'role'
    ];


    protected $casts=[
        'role'=>ParticipantRole::class
    ];


    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('participants');


        parent::__construct($attributes);
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
     * Define a relationship to fetch the user of this model.
     */
    // public function user()
    // {
    //     return $this->belongsTo($this->userModel::class);
    // }

}
