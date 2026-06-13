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
        Schema::table('segments', function (Blueprint $table) {
            $table->unsignedInteger('contact_count')->default(0)->after('rules');
            $table->timestamp('last_refreshed_at')->nullable()->after('contact_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->dropColumn(['contact_count', 'last_refreshed_at']);
        });
    }
};
