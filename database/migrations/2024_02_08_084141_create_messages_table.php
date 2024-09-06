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
        Schema::create(config('wirechat.messages_table','wirechat_messages'), function (Blueprint $table) {
          $table->id();

        $table->unsignedBigInteger('conversation_id')->nullable();
        $table->foreign('conversation_id')->references('id')->on(config('wirechat.conversations_table'))->cascadeOnDelete();

        $table->unsignedBigInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

        $table->unsignedBigInteger('reply_id')->nullable();
        $table->foreign('reply_id')->references('id')->on(config('wirechat.messages_table'))->nullOnDelete();

        $table->unsignedBigInteger('attachment_id')->nullable();
        $table->foreign('attachment_id')->references('id')->on(config('wirechat.attachments_table'))->nullOnDelete();

        $table->timestamp('read_at')->nullable();
        $table->text('body')->nullable();

        $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('wirechat.messages_table','wirechat_messages'));
    }
};
