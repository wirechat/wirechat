<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Message;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usesUuid = WireChat::usesUuid();
        Schema::create((new Message)->getTable(), function (Blueprint $table) use ($usesUuid) {
            $table->id();

            if ($usesUuid) {
                $table->uuid('conversation_id');
            } else {
                $table->unsignedBigInteger('conversation_id');
            }
            $table->foreign('conversation_id')->references('id')->on((new Conversation)->getTable())->cascadeOnDelete();

            $table->unsignedBigInteger('sendable_id');
            $table->string('sendable_type');

            $table->unsignedBigInteger('reply_id')->nullable();
            $table->foreign('reply_id')->references('id')->on((new Message)->getTable())->nullOnDelete();

            $table->text('body')->nullable();
            $table->string('type')->default('text');

            $table->timestamp('kept_at')->nullable()->comment('filled when a message is kept from disappearing');

            $table->softDeletes();
            $table->timestamps();

            // Indexes for optimization
            $table->index(['conversation_id']);
            $table->index(['sendable_id', 'sendable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Message)->getTable());
    }
};
