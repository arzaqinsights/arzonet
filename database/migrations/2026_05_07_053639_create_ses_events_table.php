<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ses_events', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('type'); // bounce, complaint, delivery
            $table->string('sub_type')->nullable(); // e.g., Permanent, Transient
            $table->string('message_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ses_events');
    }
};
