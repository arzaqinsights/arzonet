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
        Schema::table('emails', function (Blueprint $table) {
            // Composite indexes to prevent full-table scans for dashboard & list overview queries
            $table->index(['email_list_id', 'is_archived', 'subscription_status'], 'emails_list_archived_sub_status_idx');
            $table->index(['email_list_id', 'is_archived', 'email_status'], 'emails_list_archived_email_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex('emails_list_archived_sub_status_idx');
            $table->dropIndex('emails_list_archived_email_status_idx');
        });
    }
};
