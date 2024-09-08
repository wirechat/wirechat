<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('wirechat.actions_table','wirechat_actions'), function (Blueprint $table) {
            //The entity who deleted
            $table->id();
            // Actionable (the entity being acted upon, like message or conversation)
            $table->string('actionable_id');
            $table->string('actionable_type');
            
            // Actor (the one performing the action, like user or admin)
            $table->string('actor_id');
            $table->string('actor_type');
            
            // Type of action (e.g., delete, archive)
            $table->string('type');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('wirechat.actions_table','wirechat_actions'));
    }
};
