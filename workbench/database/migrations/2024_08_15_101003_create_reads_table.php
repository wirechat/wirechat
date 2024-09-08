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
        Schema::create(config('wirechat.reads_table','wirechat_reads'), function (Blueprint $table) {
            $table->id();
            $table->morphs('readable'); //Polymorphic relationship
            $table->unsignedBigInteger('message_id'); //Reference to the message
            $table->foreign('message_id')->references('id')->on(config('wirechat.messages_table','wirechat_messages'))->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('wirechat.reads_table','wirechat_reads'));
    }
};
