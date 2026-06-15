<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blacklisted_emails', function (Blueprint $table) {
            // Drop unique index on email
            $table->dropUnique(['email']);
            // Add composite unique index on user_id and email
            $table->unique(['user_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('blacklisted_emails', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'email']);
            $table->unique('email');
        });
    }
};
