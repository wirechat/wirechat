<?php

namespace Wirechat\Wirechat\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wirechat\Wirechat\Enums\JoinRequestStatus;
use Wirechat\Wirechat\Facades\Wirechat;

/**
 * @property int $id
 * @property int $joinable_id
 * @property string $joinable_type
 * @property int $requester_id
 * @property string $requester_type
 * @property int|null $invite_id
 * @property JoinRequestStatus $status
 * @property int|null $reviewed_by_id
 * @property string|null $reviewed_by_type
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property array<string, mixed>|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
class JoinRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'joinable_id',
        'joinable_type',
        'requester_id',
        'requester_type',
        'invite_id',
        'status',
        'reviewed_by_id',
        'reviewed_by_type',
        'reviewed_at',
        'data',
    ];

    protected $casts = [
        'status' => JoinRequestStatus::class,
        'reviewed_at' => 'datetime',
        'data' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = Wirechat::formatTableName('join_requests');

        parent::__construct($attributes);
    }

    public function joinable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): MorphTo
    {
        return $this->morphTo();
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(Invite::class);
    }

    public function reviewedBy(): MorphTo
    {
        return $this->morphTo('reviewedBy', 'reviewed_by_type', 'reviewed_by_id');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', JoinRequestStatus::PENDING);
    }

    public function scopeAccepted(Builder $query): void
    {
        $query->where('status', JoinRequestStatus::ACCEPTED);
    }

    public function scopeDismissed(Builder $query): void
    {
        $query->where('status', JoinRequestStatus::DISMISSED);
    }

    public function scopeWhereRequester(Builder $query, Model|Authenticatable $requester): void
    {
        $query
            ->where('requester_id', $requester->getKey())
            ->where('requester_type', $requester->getMorphClass());
    }

    public function approve(Model|Authenticatable|null $reviewedBy = null): void
    {
        $this->forceFill([
            'status' => JoinRequestStatus::ACCEPTED,
            'reviewed_by_id' => $reviewedBy?->getKey(),
            'reviewed_by_type' => $reviewedBy?->getMorphClass(),
            'reviewed_at' => now(),
        ])->save();
    }

    public function dismiss(Model|Authenticatable|null $reviewedBy = null): void
    {
        $this->forceFill([
            'status' => JoinRequestStatus::DISMISSED,
            'reviewed_by_id' => $reviewedBy?->getKey(),
            'reviewed_by_type' => $reviewedBy?->getMorphClass(),
            'reviewed_at' => now(),
        ])->save();
    }
}
