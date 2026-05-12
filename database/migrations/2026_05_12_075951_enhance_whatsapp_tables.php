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
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_conversations', 'agent_id')) {
                $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('whatsapp_conversations', 'last_message_preview')) {
                $table->text('last_message_preview')->nullable();
            }
            if (!Schema::hasColumn('whatsapp_conversations', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_messages', 'metadata')) {
                $table->json('metadata')->nullable(); // For media info (caption, mime, etc)
            }
        });

        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_accounts', 'quality_rating')) {
                $table->string('quality_rating')->nullable();
            }
            if (!Schema::hasColumn('whatsapp_accounts', 'messaging_limit_tier')) {
                $table->string('messaging_limit_tier')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropColumn(['agent_id', 'last_message_preview', 'metadata']);
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });

        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropColumn(['quality_rating', 'messaging_limit_tier']);
        });
    }
};
