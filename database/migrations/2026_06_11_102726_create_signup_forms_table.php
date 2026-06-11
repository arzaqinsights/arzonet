<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signup_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_list_id')->constrained('email_lists')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('button_text')->default('Subscribe');
            $table->text('success_message')->nullable();
            $table->boolean('double_opt_in')->default(false);
            $table->json('subscribed_topics')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('theme_color', 10)->default('#5850ec');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_forms');
    }
};
