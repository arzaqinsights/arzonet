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
        Schema::table('signup_forms', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('custom_fields');
            $table->boolean('allow_topic_selection')->default(false)->after('double_opt_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signup_forms', function (Blueprint $table) {
            $table->dropColumn(['tags', 'allow_topic_selection']);
        });
    }
};
