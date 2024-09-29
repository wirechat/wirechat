<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        Schema::create(WireChat::formatTableName('reads'), function (Blueprint $table) {
            $table->id();
            $table->morphs('readable'); // Polymorphic relationship
            $table->unsignedBigInteger('conversation_id'); // Reference to the message
            $table->foreign('conversation_id')->references('id')->on((new Conversation())->getTable())->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(WireChat::formatTableName('reads'));
    }
};
