<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->foreignId('activity_log_id')->nullable()->constrained('activity_logs')->onDelete('cascade')->after('email_list_id');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('batch_id')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_log_id');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
};
