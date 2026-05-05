<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('email_list_id')->constrained('email_lists')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            $table->enum('status', [
                'draft', 'scheduled', 'sending', 'paused', 'completed', 'cancelled'
            ])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('emails_per_minute')->default(60);
            $table->unsignedInteger('batch_size')->default(50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
