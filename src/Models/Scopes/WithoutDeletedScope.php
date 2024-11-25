<?php

namespace Namu\WireChat\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

class WithoutDeletedScope implements Scope
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
        /**
         **For Future referene
         *Query Logic for Handling conversation_deleted_at and updated_at:
         *Use <: Exclude conversations if conversation_deleted_at is earlier than updated_at. Ensures deleted conversations are excluded even if timestamps match.
         *Use <=: Include conversations where conversation_deleted_at equals updated_at. Useful for "soft deletion" where both timestamps align.
         */
        $user = auth()->user();

        if ($user) {
            // Get the table name for conversations dynamically to avoid hardcoding.
            $conversationsTableName = (new Conversation)->getTable();

            // Apply the "without deleted conversations" scope
            $builder->whereHas('participants', function ($query) use ($user, $conversationsTableName) {
                $query->where('participantable_id', $user->id)
                    ->whereRaw("
                        (conversation_deleted_at IS NULL OR conversation_deleted_at < {$conversationsTableName}.updated_at)
                    ");
            });

        }
    }
}
