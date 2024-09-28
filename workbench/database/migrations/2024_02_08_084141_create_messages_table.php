<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Attachment;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(WireChat::formatTableName('messages'), function (Blueprint $table) {
        
        $table->id();

        $table->unsignedBigInteger('conversation_id')->nullable();
        $table->foreign('conversation_id')->references('id')->on((new Conversation)->getTable())->cascadeOnDelete();

        $table->string('sendable_id'); // ID of the sender
        $table->string('sendable_type'); // Model type of the sender

        $table->unsignedBigInteger('reply_id')->nullable();
        $table->foreign('reply_id')->references('id')->on((new Message)->getTable())->nullOnDelete();

        $table->unsignedBigInteger('attachment_id')->nullable();
        $table->foreign('attachment_id')->references('id')->on((new Attachment)->getTable())->nullOnDelete();

        $table->text('body')->nullable();

        $table->softDeletes();

        $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(WireChat::formatTableName('messages'));
    }
};
