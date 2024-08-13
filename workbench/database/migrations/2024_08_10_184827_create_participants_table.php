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
        Schema::create(config('wirechat.participants_table','wirechat_participants'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->foreign('conversation_id')->references('id')->on(config('wirechat.conversations_table'))->cascadeOnDelete();
    
            $table->unsignedBigInteger('user_id'); // Reference to the user
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    
            $table->timestamps();
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('wirechat.participants_table','wirechat_participants'));
    }
};
