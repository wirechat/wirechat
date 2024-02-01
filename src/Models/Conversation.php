<?php

namespace Namu\WireChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable=[
        'receiver_id',
        'sender_id'
    ];

    

    public function __construct(array $attributes = [])
    {
        $this->table = \config('wirechat.conversations_table');

        parent::__construct($attributes);
    }



    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getReceiver()
    {
        
        if ($this->sender_id === auth()->id()) {

            return User::firstWhere('id',$this->receiver_id);

        } else {

            return User::firstWhere('id',$this->sender_id);
        }
    }



   public function scopeWhereNotDeleted($query) 
     {
        $userId=auth()->id();

        return $query->where(function ($query) use ($userId){

            #where message is not deleted
            $query->whereHas('messages',function($query) use($userId){

                $query->where(function ($query) use($userId){
                    $query->where('sender_id',$userId)
                        ->whereNull('sender_deleted_at');
                })->orWhere(function ($query) use ($userId){

                    $query->where('receiver_id',$userId)
                    ->whereNull('receiver_deleted_at');
                });

            })
             #include conversations without messages
              ->orWhereDoesntHave('messages');


        });
        
    }



  public  function isLastMessageReadByUser():bool {


        $user=Auth()->User();
        $lastMessage= $this->messages()->latest()->first();
        
        if($lastMessage){
            return  $lastMessage->read_at !==null && $lastMessage->sender_id == $user->id;
        }
        
    }




   public  function unreadMessagesCount() : int {


    return $unreadMessages= Message::where('conversation_id','=',$this->id)
                                ->where('receiver_id',auth()->user()->id)
                                ->whereNull('read_at')->count();

    }

    

}
