<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Participant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create((new Participant)->getTable(), function (Blueprint $table) {
            $table->id();

            // Foreign key for conversation
            $table->unsignedBigInteger('conversation_id');
            $table->foreign('conversation_id')->references('id')->on((new Conversation)->getTable())->cascadeOnDelete();

            $table->string('role');
            $table->unsignedBigInteger('participantable_id');
            $table->string('participantable_type');

            // Timestamps for tracking participant activity
            $table->timestamp('exited_at')->nullable()->index();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('conversation_cleared_at')->nullable()->index();
            $table->timestamp('conversation_deleted_at')->nullable()->index();
            $table->timestamp('conversation_read_at')->nullable()->index();

            $table->softDeletes();
            $table->timestamps();

            // Unique constraint on conversation_id, participantable_id, and participantable_type
            $table->unique(['conversation_id', 'participantable_id', 'participantable_type'], 'conv_part_id_type_unique');

            $table->index(['role']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Participant)->getTable());
    }
};
