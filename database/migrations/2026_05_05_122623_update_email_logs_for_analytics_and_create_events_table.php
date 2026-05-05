<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update email_logs table
        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('message_id')->nullable()->after('email_address')->index();
            $table->string('tracking_token')->nullable()->after('message_id')->unique();
            
            // Stats
            $table->integer('open_count')->default(0)->after('status');
            $table->integer('click_count')->default(0)->after('open_count');
            
            // Timestamps
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('first_open_at')->nullable()->after('delivered_at');
            $table->timestamp('last_open_at')->nullable()->after('first_open_at');
            $table->timestamp('clicked_at')->nullable()->after('last_open_at');
            
            // Bounce Info
            $table->string('bounce_type')->nullable()->after('error_message'); // permanent, transient
            $table->string('bounce_reason')->nullable()->after('bounce_type');
        });

        // 2. Create email_events table for granular tracking
        Schema::create('email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_log_id')->constrained('email_logs')->cascadeOnDelete();
            $table->enum('type', ['open', 'click', 'bounce', 'complaint', 'unsubscribe', 'delivery']);
            $table->string('url')->nullable(); // For clicks
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // For device/geo info
            $table->timestamp('created_at')->useCurrent();

            $table->index(['email_log_id', 'type']);
        });

        // 3. Update emails table for engagement scoring
        Schema::table('emails', function (Blueprint $table) {
            $table->integer('engagement_score')->default(0);
            $table->timestamp('last_engaged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_events');
        
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn([
                'message_id', 'tracking_token', 'open_count', 'click_count',
                'delivered_at', 'first_open_at', 'last_open_at', 'clicked_at',
                'bounce_type', 'bounce_reason'
            ]);
        });

        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['engagement_score', 'last_engaged_at']);
        });
    }
};
