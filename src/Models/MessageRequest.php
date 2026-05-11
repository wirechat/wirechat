<?php

namespace Wirechat\Wirechat\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wirechat\Wirechat\Enums\MessageRequestStatus;
use Wirechat\Wirechat\Facades\Wirechat;

/**
 * @property int $id
 * @property int|string|null $conversation_id
 * @property int $sender_id
 * @property string $sender_type
 * @property int $recipient_id
 * @property string $recipient_type
 * @property MessageRequestStatus $status
 * @property int|null $reviewed_by_id
 * @property string|null $reviewed_by_type
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property array<string, mixed>|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Wirechat\Wirechat\Models\Conversation|null $conversation
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $recipient
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $reviewedBy
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $sender
 *
 * @mixin \Eloquent
 */
class MessageRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'recipient_id',
        'recipient_type',
        'status',
        'reviewed_by_id',
        'reviewed_by_type',
        'reviewed_at',
        'data',
    ];

    protected $casts = [
        'status' => MessageRequestStatus::class,
        'reviewed_at' => 'datetime',
        'data' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = Wirechat::formatTableName('message_requests');

        parent::__construct($attributes);
    }

    protected static function newFactory()
    {
        return \Wirechat\Wirechat\Workbench\Database\Factories\MessageRequestFactory::new();
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Wirechat::conversationModelClass());
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewedBy(): MorphTo
    {
        return $this->morphTo('reviewedBy', 'reviewed_by_type', 'reviewed_by_id');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', MessageRequestStatus::PENDING);
    }

    public function scopeAccepted(Builder $query): void
    {
        $query->where('status', MessageRequestStatus::ACCEPTED);
    }

    public function scopeDismissed(Builder $query): void
    {
        $query->where('status', MessageRequestStatus::DISMISSED);
    }

    public function scopeWhereSender(Builder $query, Model|Authenticatable $sender): void
    {
        $query
            ->where('sender_id', $sender->getKey())
            ->where('sender_type', $sender->getMorphClass());
    }

    public function scopeWhereRecipient(Builder $query, Model|Authenticatable $recipient): void
    {
        $query
            ->where('recipient_id', $recipient->getKey())
            ->where('recipient_type', $recipient->getMorphClass());
    }

    public function scopeWhereInvolvesPair(Builder $query, Model|Authenticatable $first, Model|Authenticatable $second): void
    {
        $query->where(function (Builder $pairQuery) use ($first, $second) {
            $pairQuery
                ->where(function (Builder $forward) use ($first, $second) {
                    $forward->whereSender($first)->whereRecipient($second);
                })
                ->orWhere(function (Builder $reverse) use ($first, $second) {
                    $reverse->whereSender($second)->whereRecipient($first);
                });
        });
    }

    public function approve(Model|Authenticatable|null $reviewedBy = null): void
    {
        $this->forceFill([
            'status' => MessageRequestStatus::ACCEPTED,
            'reviewed_by_id' => $reviewedBy?->getKey(),
            'reviewed_by_type' => $reviewedBy?->getMorphClass(),
            'reviewed_at' => now(),
        ])->save();
    }

    public function dismiss(Model|Authenticatable|null $reviewedBy = null): void
    {
        $this->forceFill([
            'status' => MessageRequestStatus::DISMISSED,
            'reviewed_by_id' => $reviewedBy?->getKey(),
            'reviewed_by_type' => $reviewedBy?->getMorphClass(),
            'reviewed_at' => now(),
        ])->save();
    }
}
