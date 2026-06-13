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
        Schema::create('form_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signup_form_id')->constrained('signup_forms')->onDelete('cascade');
            $table->string('session_id');
            $table->string('ip_address')->nullable();
            $table->text('referrer')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signup_form_id')->constrained('signup_forms')->onDelete('cascade');
            $table->string('session_id');
            $table->string('email')->nullable();
            $table->integer('abandoned_step')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_views');
    }
};
