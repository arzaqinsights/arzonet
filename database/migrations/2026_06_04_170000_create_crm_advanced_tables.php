<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pipelines
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Pipeline Stages
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color', 7)->default('#6366f1');
            $table->unsignedInteger('order')->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Deals
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_stage_id')->constrained()->onDelete('cascade');
            $table->foreignId('email_id')->nullable()->constrained('emails')->onDelete('set null');
            $table->string('title');
            $table->decimal('value', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['open', 'won', 'lost'])->default('open');
            $table->unsignedInteger('order')->default(0);
            $table->date('expected_close_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Contact Tasks
        Schema::create('contact_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->nullable()->constrained('emails')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 5. Custom Fields
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // slug
            $table->string('label');
            $table->enum('type', ['text', 'number', 'date', 'select'])->default('text');
            $table->json('choices')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // 6. Modify segments table
        if (!Schema::hasColumn('segments', 'user_id')) {
            Schema::table('segments', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->json('rules')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
        Schema::dropIfExists('contact_tasks');
        Schema::dropIfExists('custom_fields');

        if (Schema::hasColumn('segments', 'user_id')) {
            Schema::table('segments', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['user_id', 'name', 'description', 'rules']);
            });
        }
    }
};
