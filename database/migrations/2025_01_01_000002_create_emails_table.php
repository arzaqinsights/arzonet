<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_list_id')->constrained('email_lists')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->enum('status', ['valid', 'invalid', 'duplicate'])->default('valid');
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('status');
            $table->index(['email_list_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
