<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Message;
use Wirechat\Wirechat\Models\Participant;

return new class extends Migration
{
    public function up(): void
    {
        $messagesTable = (new Message)->getTable();
        $partsTable    = (new Participant)->getTable();
        $tablePrefix   = Wirechat::tablePrefix();

        $participantIndex           = "{$tablePrefix}messages_participant_id_index";
        $conversationCreatedAtIndex = "{$tablePrefix}messages_conversation_id_created_at_index";

        // add participant_id
        Schema::table($messagesTable, function (Blueprint $t) use ($messagesTable) {
            if (! Schema::hasColumn($messagesTable, 'participant_id')) {
                $t->unsignedBigInteger('participant_id')
                    ->nullable()
                    ->after('conversation_id');
            }
        });

        // indexes (safe)
        if (! Schema::hasIndex($messagesTable, $participantIndex)) {
            Schema::table($messagesTable, fn (Blueprint $t) => $t->index('participant_id'));
        }

        if (! Schema::hasIndex($messagesTable, $conversationCreatedAtIndex)) {
            Schema::table(
                $messagesTable,
                fn (Blueprint $t) => $t->index(['conversation_id', 'created_at'])
            );
        }

        // backfill participant_id
        DB::table($messagesTable)
            ->select('id', 'conversation_id', 'sendable_type', 'sendable_id')
            ->whereNotNull('conversation_id')
            ->whereNotNull('sendable_type')
            ->whereNotNull('sendable_id')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($messagesTable, $partsTable) {
                foreach ($rows as $row) {
                    $participantId = DB::table($partsTable)
                        ->where('conversation_id', $row->conversation_id)
                        ->where('participantable_type', $row->sendable_type)
                        ->where('participantable_id', (string) $row->sendable_id)
                        ->value('id');

                    if ($participantId) {
                        DB::table($messagesTable)
                            ->where('id', $row->id)
                            ->update(['participant_id' => $participantId]);
                    }
                }
            });

        // make NOT NULL (best-effort)
        try {
            Schema::table($messagesTable, function (Blueprint $t) {
                $t->unsignedBigInteger('participant_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {}

        // drop legacy columns
        Schema::table($messagesTable, function (Blueprint $t) use ($messagesTable) {
            if (Schema::hasColumn($messagesTable, 'sendable_type')) {
                $t->dropColumn('sendable_type');
            }
            if (Schema::hasColumn($messagesTable, 'sendable_id')) {
                $t->dropColumn('sendable_id');
            }
        });

        // FK (best-effort)
        try {
            Schema::table($messagesTable, function (Blueprint $t) use ($partsTable) {
                $t->foreign('participant_id')
                    ->references('id')
                    ->on($partsTable)
                    ->cascadeOnDelete();
            });
        } catch (\Throwable $e) {}
    }

   public function down(): void
   {
       $messagesTable = (new Message)->getTable();
       $partsTable    = (new Participant)->getTable();
       $tablePrefix   = Wirechat::tablePrefix();

       $participantIndex = "{$tablePrefix}messages_participant_id_index";

       // 1. Restore legacy columns
       Schema::table($messagesTable, function (Blueprint $t) use ($messagesTable) {
           if (! Schema::hasColumn($messagesTable, 'sendable_type')) {
               $t->string('sendable_type')->nullable();
           }

           if (! Schema::hasColumn($messagesTable, 'sendable_id')) {
               $t->string('sendable_id')->nullable();
           }
       });

       // 2. Backfill sendable_* from participant
       DB::table($messagesTable)
           ->select('id', 'participant_id')
           ->whereNotNull('participant_id')
           ->orderBy('id')
           ->chunkById(1000, function ($rows) use ($messagesTable, $partsTable) {
               foreach ($rows as $row) {
                   $participant = DB::table($partsTable)
                       ->where('id', $row->participant_id)
                       ->first(['participantable_type', 'participantable_id']);

                   if ($participant) {
                       DB::table($messagesTable)
                           ->where('id', $row->id)
                           ->update([
                               'sendable_type' => $participant->participantable_type,
                               'sendable_id'   => (string) $participant->participantable_id,
                           ]);
                   }
               }
           });

       // 3. Drop FK (best-effort)
       try {
           Schema::table($messagesTable, fn (Blueprint $t) => $t->dropForeign(['participant_id']));
       } catch (\Throwable $e) {}

       // 4. Drop index before column (SQLite-safe)
       if (Schema::hasIndex($messagesTable, $participantIndex)) {
           Schema::table($messagesTable, fn (Blueprint $t) => $t->dropIndex($participantIndex));
       }

       // 5. Drop participant_id column
       Schema::table($messagesTable, function (Blueprint $t) use ($messagesTable) {
           if (Schema::hasColumn($messagesTable, 'participant_id')) {
               $t->dropColumn('participant_id');
           }
       });
   }

};
