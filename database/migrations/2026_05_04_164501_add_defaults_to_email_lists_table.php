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
        Schema::table('email_lists', function (Blueprint $table) {
            $table->string('signup_source')->nullable();
            $table->string('segment_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_lists', function (Blueprint $table) {
            $table->dropColumn(['signup_source', 'segment_name']);
        });
    }
};
