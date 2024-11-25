<?php

namespace Namu\WireChat\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Namu\WireChat\Enums\Actions;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class WithoutRemovedActionScope implements Scope
{
    /**
     * Applies a scope to exclude conversations that the authenticated user has marked as "deleted"
     * unless new messages have been added after the "deletion" timestamp.
     *
     * The logic works as follows:
     * - If the conversation was deleted by the user, it will remain hidden.
     * - If a new message (indicated by the `updated_at` timestamp on the conversation) is added
     *   after the user deleted it, the conversation will reappear in their list.
     *
     * @param  Builder  $builder  The Eloquent query builder instance.
     * @param  Model  $model  The model instance on which the scope is applied.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereDoesntHave('actions', function (Builder $query) {
            $query->where('type', Actions::REMOVED_BY_ADMIN);  // Filter actions that are of type 'remove'
        });
    }
}
