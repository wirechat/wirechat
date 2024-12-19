<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Models\Action;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create((new Action)->getTable(), function (Blueprint $table) {
            $table->id();

            // Actionable (the entity being acted upon)
            $table->unsignedBigInteger('actionable_id');
            $table->string('actionable_type');

            // Actor (the one performing the action
            $table->unsignedBigInteger('actor_id');
            $table->string('actor_type');

            // Type of action (e.g., delete, archive)
            $table->string('type');

            $table->string('data')->nullable()->comment('Some additional information about the action');

            $table->timestamps();

            $table->index(['actionable_id', 'actionable_type']);
            $table->index(['actor_id', 'actor_type']);
            $table->index('type');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Action)->getTable());
    }
};
