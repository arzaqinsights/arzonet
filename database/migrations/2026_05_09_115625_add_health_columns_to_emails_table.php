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
            $table->string('email_status')->nullable()->after('status')->index();
            $table->integer('email_score')->default(3)->after('email_status')->index();
            $table->string('email_risk_level')->nullable()->after('email_score');
            $table->timestamp('last_validation_at')->nullable()->after('email_risk_level');
            $table->integer('bounce_count')->default(0)->after('last_validation_at');
            $table->integer('complaint_count')->default(0)->after('bounce_count');
            $table->string('last_bounce_type')->nullable()->after('complaint_count');
            $table->boolean('is_role_based')->default(false)->after('last_bounce_type');
            $table->boolean('is_disposable')->default(false)->after('is_role_based');
            $table->boolean('is_catch_all')->default(false)->after('is_disposable');
            $table->boolean('has_typo')->default(false)->after('is_catch_all');
            $table->text('validation_reason')->nullable()->after('has_typo');
            $table->string('last_campaign_status')->nullable()->after('validation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn([
                'email_status', 'email_score', 'email_risk_level', 'last_validation_at',
                'bounce_count', 'complaint_count', 'last_bounce_type', 'is_role_based',
                'is_disposable', 'is_catch_all', 'has_typo', 'validation_reason',
                'last_campaign_status'
            ]);
        });
    }
};
