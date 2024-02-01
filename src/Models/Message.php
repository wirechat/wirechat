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
        'attachment_id'
    ];


    protected $dates=['read_at','receiver_deleted_at','sender_deleted_at'];


    public function __construct(array $attributes = [])
    {
        $this->table = \config('wirechat.messages_table');

        parent::__construct($attributes);
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
}
