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
        Schema::table('emails', function (Blueprint $table) {
            $table->string('whatsapp_number')->nullable()->after('email');
            $table->boolean('whatsapp_opt_in')->default(false)->after('whatsapp_number');
            $table->timestamp('whatsapp_last_message_at')->nullable()->after('whatsapp_opt_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_number', 'whatsapp_opt_in', 'whatsapp_last_message_at']);
        });
    }
};
