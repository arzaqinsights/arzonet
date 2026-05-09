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
        if (!Schema::hasColumn('activity_logs', 'user_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            });
        }
        if (!Schema::hasColumn('email_logs', 'user_id')) {
            Schema::table('email_logs', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            });
        }
        if (!Schema::hasColumn('contact_activities', 'user_id')) {
            Schema::table('contact_activities', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            });
        }
        if (!Schema::hasColumn('contact_notes', 'user_id')) {
            Schema::table('contact_notes', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            });
        }
        if (!Schema::hasColumn('blacklisted_emails', 'user_id')) {
            Schema::table('blacklisted_emails', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) { $table->dropForeign(['user_id']); $table->dropColumn('user_id'); });
        Schema::table('email_logs', function (Blueprint $table) { $table->dropForeign(['user_id']); $table->dropColumn('user_id'); });
        Schema::table('contact_activities', function (Blueprint $table) { $table->dropForeign(['user_id']); $table->dropColumn('user_id'); });
        Schema::table('contact_notes', function (Blueprint $table) { $table->dropForeign(['user_id']); $table->dropColumn('user_id'); });
        Schema::table('blacklisted_emails', function (Blueprint $table) { $table->dropForeign(['user_id']); $table->dropColumn('user_id'); });
    }
};
