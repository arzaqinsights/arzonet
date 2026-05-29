<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Change emails table status column from enum to string
        Schema::table('emails', function (Blueprint $table) {
            $table->string('status', 50)->default('valid')->change();
        });

        // 2. Add cross_duplicate_count to email_lists table
        Schema::table('email_lists', function (Blueprint $table) {
            $table->integer('cross_duplicate_count')->default(0)->after('duplicate_count');
        });

        // 3. Add session_cross_duplicate_count to activity_logs table
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->integer('session_cross_duplicate_count')->default(0)->after('session_duplicate_count');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('session_cross_duplicate_count');
        });

        Schema::table('email_lists', function (Blueprint $table) {
            $table->dropColumn('cross_duplicate_count');
        });

        Schema::table('emails', function (Blueprint $table) {
            $table->enum('status', ['valid', 'invalid', 'duplicate'])->default('valid')->change();
        });
    }
};
