<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Namu\WireChat\Facades\WireChat;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(WireChat::formatTableName('conversations'), function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('Private is 1-1 and room is group or channel'); 
            $table->softDeletes();
        
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('wirechat.table_prefix').'conversations');
    }
};
