<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Namu\WireChat\Facades\WireChat;

class Group extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'conversation_id',
        'title',
        'description',
        'avatar_url'
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('group');
        parent::__construct($attributes);
    }

    /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\GroupFactory::new();
    }


    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }



}
