<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Enums\JoinRequestStatus;
use Wirechat\Wirechat\Models\JoinRequest;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create((new JoinRequest)->getTable(), function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('joinable_id');
            $table->string('joinable_type');

            $table->unsignedBigInteger('requester_id');
            $table->string('requester_type');

            $table->unsignedBigInteger('invite_id')->nullable();
            $table->string('status')->default(JoinRequestStatus::PENDING->value)->index();

            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->string('reviewed_by_type')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['joinable_id', 'joinable_type']);
            $table->index(['joinable_id', 'joinable_type', 'status']);
            $table->index(['requester_id', 'requester_type']);
            $table->index(['reviewed_by_id', 'reviewed_by_type']);
            $table->index('invite_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new JoinRequest)->getTable());
    }
};
