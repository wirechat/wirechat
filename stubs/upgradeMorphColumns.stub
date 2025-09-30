<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Models\Action;
use Wirechat\Wirechat\Models\Attachment;

return new class extends Migration
{
    public function up(): void
    {
        $actionsTable = (new Action)->getTable();
        $attachmentsTable = (new Attachment)->getTable();

        // Actions table
        if (Schema::hasTable($actionsTable)) {
            Schema::table($actionsTable, function (Blueprint $table) use ($actionsTable) {
                if (Schema::hasColumn($actionsTable, 'actionable_id')) {
                    $table->string('actionable_id', 64)->change();
                }
                if (Schema::hasColumn($actionsTable, 'actionable_type')) {
                    $table->string('actionable_type', 100)->change();
                }
                if (Schema::hasColumn($actionsTable, 'actor_id')) {
                    $table->string('actor_id', 64)->change();
                }
                if (Schema::hasColumn($actionsTable, 'actor_type')) {
                    $table->string('actor_type', 100)->change();
                }
            });
        }

        // Attachments table
        if (Schema::hasTable($attachmentsTable)) {
            Schema::table($attachmentsTable, function (Blueprint $table) use ($attachmentsTable) {
                if (Schema::hasColumn($attachmentsTable, 'attachable_id')) {
                    $table->string('attachable_id', 64)->change();
                }
                if (Schema::hasColumn($attachmentsTable, 'attachable_type')) {
                    $table->string('attachable_type', 100)->change();
                }
            });
        }
    }

    public function down(): void
    {
        $actionsTable = (new Action)->getTable();
        $attachmentsTable = (new Attachment)->getTable();

        // Revert Actions table
        if (Schema::hasTable($actionsTable)) {
            Schema::table($actionsTable, function (Blueprint $table) use ($actionsTable) {
                if (Schema::hasColumn($actionsTable, 'actionable_id')) {
                    $table->unsignedBigInteger('actionable_id')->change();
                }
                if (Schema::hasColumn($actionsTable, 'actionable_type')) {
                    $table->string('actionable_type', 255)->change();
                }
                if (Schema::hasColumn($actionsTable, 'actor_id')) {
                    $table->unsignedBigInteger('actor_id')->change();
                }
                if (Schema::hasColumn($actionsTable, 'actor_type')) {
                    $table->string('actor_type', 255)->change();
                }
            });
        }

        // Revert Attachments table
        if (Schema::hasTable($attachmentsTable)) {
            Schema::table($attachmentsTable, function (Blueprint $table) use ($attachmentsTable) {
                if (Schema::hasColumn($attachmentsTable, 'attachable_id')) {
                    $table->unsignedBigInteger('attachable_id')->change();
                }
                if (Schema::hasColumn($attachmentsTable, 'attachable_type')) {
                    $table->string('attachable_type', 255)->change();
                }
            });
        }
    }
};
