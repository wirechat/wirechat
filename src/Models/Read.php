<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Read extends Model
{
    use HasFactory;


    public $timestamps = false;

    protected $fillable = ['read_at', 'message_id','readable_id','readable_type'];



    public function __construct(array $attributes = [])
    {
        $this->table = config('wirechat.reads_table','wirechat_reads');

        //Set up the user model 
       // $this->userModel =app(config('wirechat.user_model',\App\Models\User::class));

        parent::__construct($attributes);
    }


    public function readable()
    {
        return $this->morphTo();
    }

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
    


}
