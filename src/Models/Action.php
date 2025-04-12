<?php

namespace Namu\WireChat\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Facades\WireChat;

/**
 * @property int $id
 * @property int $actionable_id
 * @property string $actionable_type
 * @property int $actor_id
 * @property string $actor_type
 * @property Actions $type
 * @property string|null $data Some additional information about the action
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $actionable
 * @property-read Model|\Eloquent $actor
 *
 * @method static Builder|Action newModelQuery()
 * @method static Builder|Action newQuery()
 * @method static Builder|Action query()
 * @method static Builder|Action whereActionableId($value)
 * @method static Builder|Action whereActionableType($value)
 * @method static Builder|Action whereActor(\Illuminate\Database\Eloquent\Model $actor)
 * @method static Builder|Action whereActorId($value)
 * @method static Builder|Action whereActorType($value)
 * @method static Builder|Action whereCreatedAt($value)
 * @method static Builder|Action whereData($value)
 * @method static Builder|Action whereId($value)
 * @method static Builder|Action whereType($value)
 * @method static Builder|Action whereUpdatedAt($value)
 * @method static Builder|Action withoutActor(\Illuminate\Database\Eloquent\Model $user)
 *
 * @mixin \Eloquent
 */
class Action extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'actor_type',
        'actionable_id',
        'actionable_type',
        'type',
        'data',
    ];

    public function __construct(array $attributes = [])
    {

        $this->table = WireChat::formatTableName('actions');

        parent::__construct($attributes);
    }

    protected $casts = [
        'type' => Actions::class,
    ];

    /**
     * since you have a non-standard namespace;
     * the resolver cannot guess the correct namespace for your Factory class.
     * so we exlicilty tell it the correct namespace
     */
    protected static function newFactory()
    {
        return \Namu\WireChat\Workbench\Database\Factories\ActionFactory::new();
    }

    // Polymorphic relationship to the entity being acted upon (message, conversation, etc.)
    public function actionable(): MorphTo
    {
        return $this->morphTo(null, 'actionable_type', 'actionable_id', 'id');
    }

    // Polymorphic relationship to the actor (User, Admin, etc.)
    public function actor(): MorphTo
    {
        return $this->morphTo('actor', 'actor_type', 'actor_id', 'id');
    }

    // scope by Actor
    public function scopeWhereActor(Builder $query, Model $actor)
    {

        $query->where('actor_id', $actor->getKey())->where('actor_type', $actor->getMorphClass());

    }

    /**
     * Exclude participant passed as parameter
     */
    public function scopeWithoutActor($query, Model $user): Builder
    {

        return $query->where(function ($query) use ($user) {
            $query->where('actor_id', '<>', $user->getKey())
                ->orWhere('actor_type', '<>', $user->getMorphClass());
        });

        //  return $query->where(function ($query) use ($user) {
        //      $query->whereNot('participantable_id', $user->id)
        //            ->orWhereNot('participantable_type', $user->getMorphClass());
        //  });
    }
}
