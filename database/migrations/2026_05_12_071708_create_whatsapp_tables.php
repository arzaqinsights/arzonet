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
        // 1. WhatsApp Accounts
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('business_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('whatsapp_business_account_id')->nullable();
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 2. WhatsApp Templates
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->onDelete('cascade');
            $table->string('meta_template_id')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('language')->default('en_US');
            $table->text('body')->nullable();
            $table->string('status')->default('draft');
            $table->json('components')->nullable();
            $table->timestamps();
        });

        // 3. WhatsApp Campaigns
        Schema::create('whatsapp_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_template_id')->constrained('whatsapp_templates')->onDelete('cascade');
            $table->string('name');
            $table->string('status')->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamps();
        });

        // 4. WhatsApp Messages
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('emails')->onDelete('cascade');
            $table->string('wa_message_id')->nullable()->index();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('type')->default('text');
            $table->text('message_body')->nullable();
            $table->string('status')->default('sent');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        // 5. WhatsApp Conversations
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('emails')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->integer('unread_count')->default(0);
            $table->timestamps();

            $table->unique(['whatsapp_account_id', 'contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_campaigns');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_accounts');
    }
};
