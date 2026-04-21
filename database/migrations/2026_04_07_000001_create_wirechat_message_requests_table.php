<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Enums\MessageRequestStatus;
use Wirechat\Wirechat\Facades\Wirechat;

return new class extends Migration
{
    public function up(): void
    {
        $usesUuid = Wirechat::usesUuid();

        Schema::create(Wirechat::messageRequestModelTable(), function (Blueprint $table) use ($usesUuid) {
            $table->id();

            if ($usesUuid) {
                $table->uuid('conversation_id')->nullable();
            } else {
                $table->unsignedBigInteger('conversation_id')->nullable();
            }

            $table->foreign('conversation_id')
                ->references('id')
                ->on(Wirechat::conversationModelTable())
                ->nullOnDelete();

            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type');

            $table->unsignedBigInteger('recipient_id');
            $table->string('recipient_type');

            $table->string('status')->default(MessageRequestStatus::PENDING->value)->index();

            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->string('reviewed_by_type')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status'], 'wmr_conversation_status_idx');
            $table->index(['sender_id', 'sender_type', 'status'], 'wmr_sender_status_idx');
            $table->index(['recipient_id', 'recipient_type', 'status'], 'wmr_recipient_status_idx');
            $table->index(['sender_id', 'sender_type', 'recipient_id', 'recipient_type'], 'wirechat_message_requests_pair_index');
            $table->index(['reviewed_by_id', 'reviewed_by_type'], 'wmr_reviewed_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Wirechat::messageRequestModelTable());
    }
};
