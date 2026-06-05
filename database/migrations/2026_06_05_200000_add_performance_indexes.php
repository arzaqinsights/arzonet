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
            // Indexes to prevent full table scans on webhook and tracking lookups
            $table->index(['campaign_id', 'email_id']);
        });

        Schema::table('emails', function (Blueprint $table) {
            // Index to optimize campaign dispatch queries
            $table->index(['email_list_id', 'status', 'subscription_status'], 'emails_list_status_sub_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'email_id']);
        });

        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex('emails_list_status_sub_idx');
        });
    }
};
