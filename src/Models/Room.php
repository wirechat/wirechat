<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Namu\WireChat\Enums\RoomType;
use Namu\WireChat\Facades\WireChat;

class Room extends Model
{
    use HasFactory;



    protected $fillable = [
        'conversation_id',
        'type',
        'title',
        'description',
        'avatar_url',


    ];

    protected $casts = [
        'type' => RoomType::class
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('rooms');
        parent::__construct($attributes);
    }

    /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\RoomFactory::new();
    }


    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }


    function isGroup()  {

        $this->type==RoomType::GROUP;
        
    }

    function isChannel()  {

        $this->type==RoomType::CHANNEL;
        
    }



}
