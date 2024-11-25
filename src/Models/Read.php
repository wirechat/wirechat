<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Namu\WireChat\Facades\WireChat;

class Read extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['read_at', 'conversation_id', 'readable_id', 'readable_type'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'read_at' => 'datetime',

    ];

    public function __construct(array $attributes = [])
    {
        $this->table = WireChat::formatTableName('reads');
        parent::__construct($attributes);
    }

    public function readable()
    {
        return $this->morphTo();
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
