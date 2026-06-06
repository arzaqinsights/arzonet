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
        Schema::table('pipelines', function (Blueprint $table) {
            $table->foreignId('email_list_id')->nullable()->constrained('email_lists')->onDelete('set null');
        });

        Schema::table('senders', function (Blueprint $table) {
            $table->foreignId('email_list_id')->nullable()->constrained('email_lists')->onDelete('set null');
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->foreignId('email_list_id')->nullable()->constrained('email_lists')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropForeign(['email_list_id']);
            $table->dropColumn('email_list_id');
        });

        Schema::table('senders', function (Blueprint $table) {
            $table->dropForeign(['email_list_id']);
            $table->dropColumn('email_list_id');
        });

        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropForeign(['email_list_id']);
            $table->dropColumn('email_list_id');
        });
    }
};
