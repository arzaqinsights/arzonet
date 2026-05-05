<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('emails_sent')->default(0);
            $table->unsignedInteger('emails_failed')->default(0);
            $table->decimal('cost', 10, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_stats');
    }
};
