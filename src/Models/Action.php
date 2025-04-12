<?php

namespace Namu\WireChat\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Facades\WireChat;

/**
 * @property int $id
 * @property int $actionable_id
 * @property string $actionable_type
 * @property int $actor_id
 * @property string $actor_type
 * @property string $type
 * @property string $data
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
    public function actionable()
    {
        return $this->morphTo(null, 'actionable_type', 'actionable_id', 'id');
    }

    // Polymorphic relationship to the actor (User, Admin, etc.)
    public function actor()
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
