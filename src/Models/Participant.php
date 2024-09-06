<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $userModel;

    protected $fillable=[
        'conversation_id',
        'participantable_id',
        'participantable_type'
    ];


    public function __construct(array $attributes = [])
    {
        $this->table = config('wirechat.participants_table','wirechat_participants');

      //  dd($this->table);

        //Set up the user model 
        $this->userModel =app(config('wirechat.user_model',\App\Models\User::class));

      //  dd($this->userModel);
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
     * Define a relationship to fetch the user of this model.
     */
      public function user()
      {
          return $this->belongsTo($this->userModel::class);
      }
  

}
