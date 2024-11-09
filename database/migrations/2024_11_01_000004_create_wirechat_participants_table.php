<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Participant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        Schema::create((new Participant())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->foreign('conversation_id')->references('id')->on((new Conversation())->getTable())->cascadeOnDelete();
            $table->string('role');
            $table->string('participantable_id');
            $table->string('participantable_type');
            $table->timestamp('exited_at')->nullable(); // Track when a participant exits
            $table->timestamp('conversation_deleted_at')->nullable(); // Track when a participant exits
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Participant())->getTable());
    }
};
