<?php

namespace Namu\WireChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    

    protected $fillable=[
        'body',
        'sender_id',
        'receiver_id',
        'conversation_id',
        'read_at',
        'receiver_deleted_at',
        'sender_deleted_at',
        'attachment_id',
        'reply_id'
    ];


    protected $dates=['read_at','receiver_deleted_at','sender_deleted_at'];


    public function __construct(array $attributes = [])
    {
        $this->table = \config('wirechat.messages_table');

        parent::__construct($attributes);
    }

         /** 
     * since you have a non-standard namespace; 
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\MessageFactory::new();
    }


    /* relationship */

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }


    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function hasAttachment()
    {
        return $this->attachment()->exists();
    }



    public function isRead():bool
    {

         return $this->read_at != null;
    }

  

    function belongsToAuth() : bool {
        
        return $this->sender_id==auth()->id();
    }



    // Relationship for the parent message
    public function parent()
    {
        return $this->belongsTo(Message::class, 'reply_id');
    }

    // Relationship for the reply
    public function reply()
    {
        return $this->hasOne(Message::class, 'reply_id');
    }

    // Method to check if the message has a reply
    public function hasReply(): bool
    {
        return $this->reply()->exists();
    }

      // Method to check if the message has a parent
      public function hasParent(): bool
      {
          return $this->parent()->exists();
      }

}
