<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('email_id')->nullable()->constrained('emails')->nullOnDelete();
            $table->string('email_address');
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced', 'complained'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
