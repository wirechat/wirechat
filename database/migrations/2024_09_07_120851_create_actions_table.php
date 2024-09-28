<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        Schema::create(WireChat::formatTableName('actions'), function (Blueprint $table) {

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
        Schema::dropIfExists(WireChat::formatTableName('actions'));
    }
};
