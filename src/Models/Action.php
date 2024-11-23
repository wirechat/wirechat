<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Facades\WireChat;

class Action extends Model
{
    use HasFactory;


    protected $fillable=[
        'actor_id',
        'actor_type',
        'actionable_id',
        'actionable_type',
        'type',
        'data'
    ];


    public function __construct(array $attributes = [])
    {

        $this->table = WireChat::formatTableName('actions');

        parent::__construct($attributes);
    }

    protected $casts =[
        'type'=>Actions::class
    ];

        /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\ActionFactory::new();
    }


       // Polymorphic relationship to the entity being acted upon (message, conversation, etc.)
       public function actionable()
       {
           return $this->morphTo();
       }
   
       // Polymorphic relationship to the actor (user, admin, etc.)
       public function actor()
       {
           return $this->morphTo();
       }
   

}
