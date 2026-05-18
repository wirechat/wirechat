<?php

namespace Wirechat\Wirechat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Panel;

/**
 * @property int $id
 * @property string $panel_id
 * @property int $inviteable_id
 * @property string $inviteable_type
 * @property int|null $created_by_id
 * @property string|null $created_by_type
 * @property string $token
 * @property string|null $name
 * @property int|null $limit
 * @property int $usages
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $inviteable
 *
 * @method static Builder|Invite active()
 * @method static Builder|Invite additional()
 * @method static Builder|Invite primary()
 * @method static Builder|Invite newModelQuery()
 * @method static Builder|Invite newQuery()
 * @method static Builder|Invite query()
 *
 * @mixin \Eloquent
 */
class Invite extends Model
{
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'inviteable_id',
        'inviteable_type',
        'created_by_id',
        'created_by_type',
        'token',
        'name',
        'limit',
        'usages',
        'is_primary',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'meta',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = Wirechat::formatTableName('invites');

        parent::__construct($attributes);
    }

    public function inviteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $query): void
    {
        $query
            ->whereNull('revoked_at')
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('limit')
                    ->orWhereColumn('usages', '<', 'limit');
            });
    }

    public function scopePrimary(Builder $query): void
    {
        $query->where('is_primary', true);
    }

    public function scopeAdditional(Builder $query): void
    {
        $query->where('is_primary', false);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->limit !== null && $this->usages >= $this->limit;
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && ! $this->isExpired() && ! $this->hasReachedUsageLimit();
    }

    public function revoke(): void
    {
        $this->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    public function markUsed(): void
    {
        $this->increment('usages');

        $this->forceFill([
            'last_used_at' => now(),
        ])->save();
    }

    public function url(Panel|string|null $panel = null, bool $absolute = true): string
    {
        $resolvedPanel = $panel instanceof Panel
            ? $panel
            : Wirechat::getPanel($panel ?: $this->panel_id);

        return $resolvedPanel->inviteRoute($this->token, $absolute);
    }

    public static function generateToken(int $length = 22): string
    {
        do {
            $token = Str::random($length);
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }
}
