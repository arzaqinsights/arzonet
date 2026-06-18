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
        Schema::table('usage_stats', function (Blueprint $table) {
            $table->dropUnique('usage_stats_date_unique');
            $table->unique(['date', 'user_id'], 'usage_stats_date_user_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_stats', function (Blueprint $table) {
            $table->dropUnique('usage_stats_date_user_id_unique');
            $table->unique('date', 'usage_stats_date_unique');
        });
    }
};
