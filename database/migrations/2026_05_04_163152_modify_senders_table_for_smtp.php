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
        Schema::table('senders', function (Blueprint $table) {
            $table->string('type')->default('ses'); // ses, smtp
            $table->string('from_name')->nullable();
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('senders', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'from_name',
                'smtp_host',
                'smtp_port',
                'smtp_username',
                'smtp_password',
                'smtp_encryption'
            ]);
        });
    }
};
