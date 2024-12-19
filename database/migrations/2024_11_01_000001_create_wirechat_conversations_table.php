<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Models\Conversation;

return new class extends Migration
{
    /*** Run the migrations */
    public function up(): void
    {
        Schema::create((new Conversation)->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('Private is 1-1 , group or channel');
            $table->timestamp('disappearing_started_at')->nullable();
            $table->integer('disappearing_duration')->nullable();
            $table->index('type');
            $table->timestamps();
        });
    }

    /*** Reverse the migrations */
    public function down(): void
    {
        Schema::dropIfExists((new Conversation)->getTable());
    }
};
