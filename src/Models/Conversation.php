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

    protected $userModel;

    

    public function __construct(array $attributes = [])
    {
        $this->table = \config('wirechat.conversations_table');

        //Set up the user model 
        $this->userModel = config('wirechat.user_model');

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
        return \Namu\WireChat\Workbench\Database\Factories\ConversationFactory::new();
    }


    public function sender()
    {
        return $this->belongsTo($this->userModel, 'sender_id','id');
    }

    /**
     * Define a relationship to fetch the receiver user.
     */
    public function receiver()
    {
            return $this->belongsTo($this->userModel, 'receiver_id','id');

    }


    public function messages()
    {
        return $this->hasMany(Message::class);
        
    }

    public function getReceiver()
    {


        
        if ($this->sender_id === auth()->id()) {

            return  $this->userModel::firstWhere('id',$this->receiver_id);

        } else {

            return  $this->userModel::firstWhere('id',$this->sender_id);
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
