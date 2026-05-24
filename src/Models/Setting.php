<?php

namespace Wirechat\Wirechat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Wirechat\Wirechat\Facades\Wirechat;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $owner_type
 * @property array<string, mixed>|null $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|\Eloquent $owner
 *
 * @mixin \Eloquent
 */
class Setting extends Model
{
    protected $fillable = [
        'owner_id',
        'owner_type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = Wirechat::formatTableName('settings');

        parent::__construct($attributes);
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
