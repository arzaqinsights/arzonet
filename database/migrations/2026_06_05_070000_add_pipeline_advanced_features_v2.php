<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add monthly_target and rotting_days to pipelines
        Schema::table('pipelines', function (Blueprint $table) {
            $table->decimal('monthly_target', 12, 2)->default(0)->after('name');
            $table->unsignedInteger('rotting_days')->default(14)->after('monthly_target');
        });

        // Add tags to deals
        Schema::table('deals', function (Blueprint $table) {
            $table->text('tags')->nullable()->after('notes');
        });

        // Add automation columns to pipeline_stages
        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->string('automation_action')->nullable()->after('color');
            $table->string('automation_value')->nullable()->after('automation_action');
        });

        // Add deal_id to contact_tasks
        Schema::table('contact_tasks', function (Blueprint $table) {
            $table->foreignId('deal_id')->nullable()->after('email_id')->constrained('deals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('contact_tasks', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
            $table->dropColumn('deal_id');
        });

        Schema::table('pipeline_stages', function (Blueprint $table) {
            $table->dropColumn(['automation_action', 'automation_value']);
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('tags');
        });

        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn(['monthly_target', 'rotting_days']);
        });
    }
};
