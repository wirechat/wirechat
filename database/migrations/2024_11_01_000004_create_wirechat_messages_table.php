<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Facades\Wirechat;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usesUuid = Wirechat::usesUuid();
        Schema::create(Wirechat::messageModelTable(), function (Blueprint $table) use ($usesUuid) {
            $table->id();

            if ($usesUuid) {
                $table->uuid('conversation_id');
            } else {
                $table->unsignedBigInteger('conversation_id');
            }
            $table->foreign('conversation_id')->references('id')->on(Wirechat::conversationModelTable())->cascadeOnDelete();

            $table->foreignId('participant_id')->references('id')->on(Wirechat::participantModelTable())->cascadeOnDelete();

            $table->unsignedBigInteger('reply_id')->nullable();
            $table->foreign('reply_id')->references('id')->on(Wirechat::messageModelTable())->nullOnDelete();

            $table->text('body')->nullable();
            $table->string('type')->default('text');

            $table->timestamp('kept_at')->nullable()->comment('filled when a message is kept from disappearing');

            $table->softDeletes();
            $table->timestamps();

            // Indexes for optimization
            $table->index(['conversation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Wirechat::messageModelTable());
    }
};
