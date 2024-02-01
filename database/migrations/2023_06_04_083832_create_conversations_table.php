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
        Schema::create(config('wirechat.conversations_table'), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');// or uuid()
            $table->foreign('sender_id')->references('id')->on('users');

            $table->unsignedBigInteger('receiver_id');// or uuid()
            $table->foreign('receiver_id')->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('wirechat.conversations_table'));
    }
};
