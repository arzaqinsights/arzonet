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
            $table->string('subscription_status')->default('subscribed')->index(); // subscribed, unsubscribed, bounced
            $table->string('signup_source')->nullable()->index();
            $table->string('segment_name')->nullable()->index();
            $table->timestamp('unsubscribed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'signup_source', 'segment_name', 'unsubscribed_at']);
        });
    }
};
