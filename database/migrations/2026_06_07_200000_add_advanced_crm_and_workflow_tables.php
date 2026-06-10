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
        Schema::table('email_lists', function (Blueprint $table) {
            $table->boolean('double_opt_in')->default(false);
            $table->string('signup_form_token')->nullable()->unique();
        });

        Schema::table('segments', function (Blueprint $table) {
            $table->foreignId('email_list_id')->nullable()->constrained('email_lists')->onDelete('cascade');
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('email_list_id')->nullable()->constrained('email_lists')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type'); // e.g. 'list_signup', 'topic_subscribe', 'tag_added'
            $table->string('trigger_value')->nullable(); // e.g. topic ID or tag name
            $table->json('steps'); // ordered array of steps: wait, send_email, tag
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->onDelete('cascade');
            $table->foreignId('email_id')->constrained('emails')->onDelete('cascade');
            $table->integer('current_step_index')->default(0);
            $table->string('status')->default('active'); // active, completed, failed
            $table->timestamp('scheduled_at')->useCurrent();
            $table->timestamp('last_executed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('preference_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained('emails')->onDelete('cascade');
            $table->string('action'); // subscribe, unsubscribe, preference_update
            $table->json('details')->nullable(); // IP, User Agent, custom metadata, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preference_logs');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflows');

        Schema::table('segments', function (Blueprint $table) {
            $table->dropForeign(['email_list_id']);
            $table->dropColumn('email_list_id');
        });

        Schema::table('email_lists', function (Blueprint $table) {
            $table->dropColumn(['double_opt_in', 'signup_form_token']);
        });
    }
};
