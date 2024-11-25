<?php

namespace Namu\WireChat\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Namu\WireChat\Enums\Actions;

class WithoutClearedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user(); // Get the authenticated user

        // dd($model->id);
        // Apply the scope only if the user is authenticated
        if ($user) {
            $builder->whereHas('messages', function ($q) use ($user) {
                $q->whereDoesntHave('actions', function ($q) use ($user) {
                    $q->where('actor_id', $user->id)
                        ->where('actor_type', get_class($user)) // Safe since $user is authenticated
                        ->where('type', Actions::DELETE);
                });
            });
            // we dont add orWhereDoesntHave because we don't want to show blank conversations in chatlist
            //
            //  ->orWhereDoesntHave('messages');
        }

    }
}
