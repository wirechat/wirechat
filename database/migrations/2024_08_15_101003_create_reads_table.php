<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Read;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create((new Read())->getTable(), function (Blueprint $table) {
            $table->id();
            
            $table->morphs('readable'); // Polymorphic relationship
            
            $table->unsignedBigInteger('conversation_id'); // Reference to the conversation
            $table->foreign('conversation_id')->references('id')->on((new Conversation())->getTable())->cascadeOnDelete();
            
            $table->timestamp('read_at')->nullable();
            
            // Indexes for optimization
            $table->index(['conversation_id']);
            $table->index(['readable_id', 'readable_type']);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Read())->getTable());
    }
};
