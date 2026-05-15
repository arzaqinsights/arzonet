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
        Schema::table('email_logs', function (Blueprint $table) {
            // 1. Drop existing foreign key
            $table->dropForeign(['campaign_id']);
            
            // 2. Make column nullable and add new foreign key with set null
            $table->unsignedBigInteger('campaign_id')->nullable()->change();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->unsignedBigInteger('campaign_id')->nullable(false)->change();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }
};
