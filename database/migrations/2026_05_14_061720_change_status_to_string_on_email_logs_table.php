<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to use a raw query or change the column using Doctrine DBAL
        // Changing ENUM to VARCHAR
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('status', 50)->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            // Note: Reverting back to enum might cause data loss if there are non-enum values
            // So we just leave it as string or change it back to the original enum
            DB::statement("ALTER TABLE email_logs MODIFY COLUMN status ENUM('pending', 'sent', 'failed', 'bounced', 'complained') DEFAULT 'pending'");
        });
    }
};
