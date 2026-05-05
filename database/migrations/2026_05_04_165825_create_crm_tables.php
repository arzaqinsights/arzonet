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
        Schema::create('contact_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // sent, opened, clicked, unsubscribed, bounced
            $table->string('url')->nullable(); // For click tracking
            $table->json('meta')->nullable(); // Browser, IP, etc.
            $table->timestamps();
        });

        Schema::create('contact_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
        });

        Schema::table('emails', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('meta');
            $table->timestamp('last_active_at')->nullable()->after('tags');
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['tags', 'last_active_at']);
        });
        Schema::dropIfExists('contact_notes');
        Schema::dropIfExists('contact_activities');
    }
};
