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
        Schema::create(WireChat::formatTableName('conversations'), function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private', 'group'])->default('private'); // Single for 1-1, Group for group chats
        
            // Use user_id to track the user who created the conversation (relevant for groups/rooms)
            $table->unsignedBigInteger('user_id')
                  ->nullable()
                  ->comment('The user who created the conversation (relevant for groups/rooms)'); 
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->softDeletes();
        
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('wirechat.table_prefix').'conversations');
    }
};
