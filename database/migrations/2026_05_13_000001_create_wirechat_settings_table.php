<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Facades\Wirechat;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(Wirechat::conversationModelTable(), function (Blueprint $table) {
            $table->json('meta')->nullable();
        });

        Schema::table(Wirechat::groupModelTable(), function (Blueprint $table) {
            $table->json('meta')->nullable();
            $table->boolean('allow_members_to_invite_others_via_link')->default(false);
        });

        Schema::create(Wirechat::settingModelTable(), function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Wirechat::settingModelTable());

        $groupsTable = Wirechat::groupModelTable();
        if (Schema::hasTable($groupsTable)) {
            $groupColumns = array_values(array_filter(
                ['meta', 'allow_members_to_invite_others_via_link'],
                fn (string $column): bool => Schema::hasColumn($groupsTable, $column)
            ));

            if ($groupColumns !== []) {
                Schema::table($groupsTable, function (Blueprint $table) use ($groupColumns) {
                    $table->dropColumn($groupColumns);
                });
            }
        }

        $conversationsTable = Wirechat::conversationModelTable();
        if (Schema::hasTable($conversationsTable) && Schema::hasColumn($conversationsTable, 'meta')) {
            Schema::table($conversationsTable, function (Blueprint $table) {
                $table->dropColumn('meta');
            });
        }
    }
};
