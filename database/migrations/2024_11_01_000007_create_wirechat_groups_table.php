<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Models\Conversation;
use Namu\WireChat\Models\Group;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usesUuid = WireChat::usesUuid();
        Schema::create((new Group)->getTable(), function (Blueprint $table) use ($usesUuid) {
            $table->id();

            // Foreign key for conversation
            if ($usesUuid) {
                $table->uuid('conversation_id');
            } else {
                $table->unsignedBigInteger('conversation_id');
            }

            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('type')->default('private');

            // Permissions
            $table->boolean('allow_members_to_send_messages')->default(true);
            $table->boolean('allow_members_to_add_others')->default(true);
            $table->boolean('allow_members_to_edit_group_info')->default(false);
            $table->boolean('admins_must_approve_new_members')->default(false)->comment('when turned on, admins must approve anyone who wants to join group');

            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Group)->getTable());
    }
};
