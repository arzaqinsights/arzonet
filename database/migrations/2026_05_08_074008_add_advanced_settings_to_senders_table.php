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
            // Change existing type to enum
            $table->enum('type', ['smtp', 'ses', 'sendgrid'])->default('ses')->change();
            
            // Throughput Limits
            $table->integer('emails_per_second')->default(1)->after('type');
            $table->integer('emails_per_minute')->default(30)->after('emails_per_second');
            $table->integer('daily_limit')->default(1000)->after('emails_per_minute');

            // SES Settings (if not already there)
            $table->string('ses_key')->nullable()->after('smtp_encryption');
            $table->string('ses_secret')->nullable()->after('ses_key');
            $table->string('ses_region')->default('us-east-1')->after('ses_secret');

            // SendGrid Settings
            $table->string('sendgrid_api_key')->nullable()->after('ses_region');
        });
    }

    public function down(): void
    {
        Schema::table('senders', function (Blueprint $table) {
            $table->string('type')->default('ses')->change();
            $table->dropColumn([
                'emails_per_second', 'emails_per_minute', 'daily_limit',
                'ses_key', 'ses_secret', 'ses_region',
                'sendgrid_api_key'
            ]);
        });
    }
};
