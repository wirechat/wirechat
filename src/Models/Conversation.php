<?php

namespace Namu\WireChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * Get the user that owns the Conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    // public function owner(): BelongsTo|null
    // {
    //     return $this->belongsTo($this->userModel, 'user_id','id');
    // }

    /**
     * Define a relationship to fetch participants for this conversation.
     */
    public function participants()
    {
        return $this->hasMany(Participant::class, 'conversation_id', 'id');
    }

    // Conversation model
    public function users()
    {
       // dd(User::class);
        return $this->belongsToMany(
            $this->userModel::class,  // User model
            config('wirechat.participants_table', 'wirechat_participants'), // Pivot table
            'conversation_id',  // Foreign key on the pivot table (Participant)
            'user_id'           // Foreign key on the pivot table (Participant)
        );
    }


    public function isPrivate(): bool
    {
        return $this->type == ConversationType::PRIVATE->value;
    }

    public function isGroup(): bool
    {
        return $this->type == 'group';
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
            ->where('user_id', '!=', auth()->id())
            ->first();

        if ($receiverParticipant) {
            // Return the associated User model via the participant's user relationship
            return $receiverParticipant->user;
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
                'readable_type' => get_class(auth()->user()),
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
                    ->where('user_id', '!=', $model->id)
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



}
