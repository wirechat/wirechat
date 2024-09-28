<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Namu\WireChat\Facades\WireChat;

class Read extends Model
{
    use HasFactory;


    public $timestamps = false;

    protected $fillable = ['read_at', 'message_id','readable_id','readable_type'];



    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('reads');
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
