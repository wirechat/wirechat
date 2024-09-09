<?php

namespace Namu\WireChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Enums\ConversationType;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'user_id'

    ];

    protected $userModel;


    protected $casts = [
        'type' => ConversationType::class
    ];


    public function __construct(array $attributes = [])
    {
        $this->table = \config('wirechat.conversations_table');


        $this->userModel = app(config('wirechat.user_model', \App\Models\User::class));

        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

          //Add scope if authenticated
          static::addGlobalScope('excludeDeleted', function (Builder $builder) {
            if (auth()->check()) {
                $builder->whereDoesntHave('actions', function ($q) {
                    $q->where('actor_id', auth()->id())
                      ->where('actor_type', get_class(auth()->user()))
                      ->where('type', Actions::DELETE);
                });
            }
        });
        //DELETED
        static::deleted(function ($conversation) {

         // Use a DB transaction to ensure atomicity
         DB::transaction(function () use ($conversation) {
            // Delete associated participants 
            $conversation->participants()->delete();

            // Delete associated messages 
            $conversation->messages()->delete();
         });

        });

      
    

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

    /**
     * Define a relationship to fetch participants for this conversation.
     */
    public function participants()
    {
        return $this->hasMany(Participant::class, 'conversation_id', 'id');
    }

    /**
     * Add a new participant to the conversation.
     *
     * @param Model $participant
     * @return void
     */
    public function addParticipant(Model $participant)
    {
        // Check if the participant is already in the conversation
        abort_if(
            $this->participants()
                ->where('participantable_id', $participant->id)
                ->where('participantable_type', get_class($participant))
                ->exists(),
            422, 
            'Participant is already in the conversation.'
        );

        // If the conversation is private, ensure it doesn't exceed two participants
        if ($this->isPrivate()) {
            abort_if(
                $this->participants()->count() >= 2,
                422, 
                'Private conversations cannot have more than two participants.'
            );
        }

        // Attach the participant to the conversation
        $this->participants()->create([
            'participantable_id' => $participant->id,
            'participantable_type' => get_class($participant),
        ]);
    }
    
    

    public function isPrivate(): bool
    {
        return $this->type == ConversationType::PRIVATE;
    }

    public function isGroup(): bool
    {
        return $this->type == ConversationType::GROUP;
    }


    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getReceiver()
    {
        // Check if the conversation is private
        if ($this->type != ConversationType::PRIVATE) {
            return null;
        }

        // Get the participant who is not the authenticated user
        $receiverParticipant = $this->participants()
            ->where('participantable_id','!=', auth()->id())
            ->where('participantable_type', get_class(auth()->user()))
            ->first();

        if ($receiverParticipant) {
            // Return the associated  model via the participant's relationship
            return $receiverParticipant->participantable;
        }

        return null;
    }




    public function scopeWhereNotDeleted($query)
    {
        $userId = auth()->id();

        return $query->where(function ($query) use ($userId) {

            #where message is not deleted
            $query->whereHas('messages', function ($query) use ($userId) {

                $query->where(function ($query) use ($userId) {
                    $query->where('sender_id', $userId)
                        ->whereNull('sender_deleted_at');
                })->orWhere(function ($query) use ($userId) {

                    $query->where('receiver_id', $userId)
                        ->whereNull('receiver_deleted_at');
                });
            })
                #include conversations without messages
                ->orWhereDoesntHave('messages');
        });
    }


    /**
     * Mark all messages in the conversation as read by the authenticated user.
     *
     * @return void
     */
    public function markAsRead()
    {
        abort_unless(auth()->check(), 401);
        $authUserId = auth()->id();

        // Get all messages in the conversation that are not already read by the authenticated user
        $messages = $this->messages()->whereDoesntHave('reads', function ($query) use ($authUserId) {
            $query->where('readable_id', $authUserId)
                  ->where('readable_type', get_class(auth()->user()));
        })->get();

        foreach ($messages as $message) {
            // Create a read record if it doesn't already exist
            $message->reads()->firstOrCreate([
                'readable_id' => $authUserId, 
                'readable_type'=>get_class(auth()->user())
            ], [
                'read_at' => now(),
            ]);
        }
    }


    /**
     * Get unread messages count for the specified user.
     *
     * @param Model  $model
     * @return int
     */
    public function getUnreadCountFor(Model $model): int
    {
        return $this->messages()
                    ->where('sendable_id', '!=', $model->id)
                    ->where('sendable_type', get_class($model))
                    ->whereDoesntHave('reads', function ($query) use ($model) {
                        $query->where('readable_id', $model->id)
                            ->where('readable_type', get_class($this));
                    })
                    ->count();
    }

    // public  function isLastMessageReadByUser():bool {

    //     $user=Auth()->User();
    //     $lastMessage= $this->messages()->latest()->first();

    //     if($lastMessage){
    //         return  $lastMessage->read_at !==null && $lastMessage->sender_id == $user->id;
    //     }

    // }


    //    public  function unreadMessagesCount() : int {


    //     return $unreadMessages= Message::where('conversation_id','=',$this->id)
    //                                 ->where('receiver_id',auth()->user()->id)
    //                                 ->whereNull('read_at')->count();

    //     }



    /**
     * Deletes 
     */

     // Relationship to the Delete model (a message can have many deletions by different users)
    //  public function deletes()
    // {
    //     return $this->morphMany(Delete::class, 'deletable');
    // }

    // Scope to exclude deleted conversations (default)
    public function scopeWithoutDeleted($query)
    {
        return $query->whereDoesntHave('deletes', function ($q) {
            $q->where('deleter_id', auth()->id())
              ->where('deleter_type', get_class(auth()->user()));
        });
    }

    // Scope to include all conversations, even deleted ones
    public function scopeWithDeleted($query)
    {
        return $query->with('deletes');
    }

    // Scope to retrieve only deleted conversations
    public function scopeOnlyDeleted($query)
    {
        return $query->whereHas('deletes', function ($q) {
            $q->where('deleter_id', auth()->id())
              ->where('deleter_type', get_class(auth()->user()));
        });
    }



    /**
     * ----------------------------------------
     * ----------------------------------------
     * Actions 
     * A message can have many actions by different users)
     * --------------------------------------------
     */

     public function actions()
     {
        return $this->morphMany(Action::class, 'actionable', 'actionable_type', 'actionable_id', 'id');
     }
 
     /**
      * Delete for me 
      * This will delete the message only for the auth user meaning other participants will be able to see it
      */
     public function deleteForMe()
     {
         abort_unless(auth()->check(), 401);
 
         //make sure auth belongs to conversation for this message
         abort_unless(auth()->user()->belongsToConversation($this), 403);


         //Trigger Delete messages forMe
         $this->messages()->each(function($message){
            $message->deleteForMe();
         });
 
         //Delete conversation forMe
         $this->actions()->create([
             'actor_id' => auth()->id(),
             'actor_type' => get_class(auth()->user()),
             'type' => Actions::DELETE
         ]);


     }



}
