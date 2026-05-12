<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wirechat\Wirechat\Models\Invite;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create((new Invite)->getTable(), function (Blueprint $table) {
            $table->id();

            $table->string('panel_id')->index();

            $table->unsignedBigInteger('inviteable_id');
            $table->string('inviteable_type');

            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();

            $table->string('token')->unique();
            $table->string('name')->nullable();
            $table->unsignedInteger('limit')->nullable();
            $table->unsignedInteger('usages')->default(0);
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['inviteable_id', 'inviteable_type']);
            $table->index(['created_by_id', 'created_by_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Invite)->getTable());
    }
};
