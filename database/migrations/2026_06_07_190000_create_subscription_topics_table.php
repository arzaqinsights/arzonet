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
        Schema::create('subscription_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('email_list_id')->nullable()->constrained('email_lists')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('emails', function (Blueprint $table) {
            $table->json('subscribed_topics')->nullable();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('subscription_topic_id')->nullable()->constrained('subscription_topics')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['subscription_topic_id']);
            $table->dropColumn('subscription_topic_id');
        });

        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn('subscribed_topics');
        });

        Schema::dropIfExists('subscription_topics');
    }
};
