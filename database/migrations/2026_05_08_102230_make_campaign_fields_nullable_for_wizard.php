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
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('email_list_id')->nullable()->change();
            $table->unsignedBigInteger('template_id')->nullable()->change();
            $table->unsignedBigInteger('sender_id')->nullable()->change();
            $table->string('subject')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('email_list_id')->nullable(false)->change();
            $table->unsignedBigInteger('template_id')->nullable(false)->change();
            $table->unsignedBigInteger('sender_id')->nullable(false)->change();
            $table->string('subject')->nullable(false)->change();
        });
    }
};
