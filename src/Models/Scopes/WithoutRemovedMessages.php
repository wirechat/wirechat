<?php

namespace Wirechat\Wirechat\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;

class WithoutRemovedMessages implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * This scope filters out messages that are considered "removed" for the authenticated user.
     * "Removed" messages can be those that are deleted, cleared, or otherwise excluded based on
     * user-specific actions or participant conditions.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $messagesTableName = (new Message)->getTable();
        $participantTableName = (new Participant)->getTable();

        if (auth()->check()) {
            $sendable = Wirechat::getSendable();

            $builder->whereDoesntHave('actions', function ($q) use ($sendable) {
                $q->where('actor_id', $sendable->getKey())
                    ->where('actor_type', $sendable->getMorphClass())
                    ->where('type', Actions::DELETE);
            })
                ->where(function ($query) use ($sendable, $messagesTableName, $participantTableName) {
                    $query->whereHas('conversation.participants', function ($q) use ($sendable, $messagesTableName, $participantTableName) {
                        $q->where('participantable_id', $sendable->getKey())
                            ->where('participantable_type', $sendable->getMorphClass())
                            ->where(function ($q) use ($messagesTableName, $participantTableName) {
                                $q->orWhere(function ($q) {
                                    $q->whereNull('conversation_cleared_at')
                                        ->whereNull('conversation_deleted_at');
                                })
                                    ->orWhere(function ($query) use ($messagesTableName, $participantTableName) {
                                        $query->whereColumn("$messagesTableName.created_at", '>', "$participantTableName.conversation_cleared_at")
                                            ->orWhereColumn("$messagesTableName.created_at", '>', "$participantTableName.conversation_deleted_at");
                                    });
                            });
                    });
                });
        }
    }
}
