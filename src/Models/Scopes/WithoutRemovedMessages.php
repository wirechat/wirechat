<?php

namespace Wirechat\Wirechat\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;
use Wirechat\Wirechat\Enums\Actions;
use Wirechat\Wirechat\Facades\Wirechat;

class WithoutRemovedMessages implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * This scope filters out messages that are considered "removed" for the authenticated user.
     * It now treats the actor as a Participant (preferred) but keeps legacy support for actor
     * being the auth user model.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $messagesTable = Wirechat::messageModelTable();
        $participantsTable = Wirechat::participantModelTable();

        if (! auth()->check()) {
            return;
        }

        $user = Wirechat::getParticipantable();
        $legacyActorType = $user->getMorphClass();
        $legacyActorId = $user->getKey();

        // Exclude messages that have a DELETE action performed by *this* authenticated actor
        $builder->whereDoesntHave('actions', function ($q) use (
            $legacyActorType,
            $legacyActorId,
            $messagesTable,
            $participantsTable
        ) {
            $q->where('type', Actions::DELETE)
                ->where(function ($sub) use (
                    $legacyActorType,
                    $legacyActorId,
                    $messagesTable,
                    $participantsTable
                ) {
                    // Case A: legacy user actor (actor stored as the user model)
                    $sub->where(function ($a) use ($legacyActorType, $legacyActorId) {
                        $a->where('actor_type', $legacyActorType)
                            ->where('actor_id', $legacyActorId);
                    });

                    // Case B: actor stored as Participant (either class name or morph alias) — but we must ensure
                    // the participant row represents the current user for the same conversation.
                    $sub->orWhere(function ($b) use ($messagesTable, $participantsTable, $legacyActorId, $legacyActorType) {
                        $participantClass = Wirechat::participantModelClass();
                        $participantMorphAlias = app($participantClass)->getMorphClass();

                        $b->where(function ($actorTypeQuery) use ($participantClass, $participantMorphAlias) {
                            $actorTypeQuery->where('actor_type', $participantClass)
                                ->orWhere('actor_type', $participantMorphAlias);
                        })
                            // Ensure there exists a participant row such that:
                            // participants.id = actions.actor_id
                            // participants.conversation_id = messages.conversation_id
                            // participants.participantable_{id,type} = current user
                            ->whereExists(function ($ex) use ($participantsTable, $messagesTable, $legacyActorId, $legacyActorType) {
                                $ex->select(DB::raw(1))
                                    ->from($participantsTable)
                                    ->whereColumn("$participantsTable.id", 'actor_id') // actor_id (actions) = participants.id
                                    ->whereColumn("$participantsTable.conversation_id", "$messagesTable.conversation_id")
                                    ->where("$participantsTable.participantable_id", $legacyActorId)
                                    ->where("$participantsTable.participantable_type", $legacyActorType);
                            });
                    });
                });
        });

        // Ensure the message is visible according to the current user's participant row
        $builder->whereHas('participant.conversation.participants', function ($q) use ($user, $messagesTable, $participantsTable) {
            $q->where('participantable_id', $user->getKey())
                ->where('participantable_type', $user->getMorphClass())
                ->where(function ($q2) use ($messagesTable, $participantsTable) {
                    $q2->where(function ($q3) {
                        $q3->whereNull('conversation_cleared_at')
                            ->whereNull('conversation_deleted_at');
                    })
                        ->orWhere(function ($q4) use ($messagesTable, $participantsTable) {
                            $q4->whereColumn("$messagesTable.created_at", '>', "$participantsTable.conversation_cleared_at")
                                ->orWhereColumn("$messagesTable.created_at", '>', "$participantsTable.conversation_deleted_at");
                        });
                });
        });
    }
}
