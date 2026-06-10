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
            $table->integer('email_lead_score')->default(1)->after('engagement_score')->index();
            $table->integer('whatsapp_lead_score')->default(1)->after('email_lead_score')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['email_lead_score', 'whatsapp_lead_score']);
        });
    }
};
