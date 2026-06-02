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
        Schema::create('email_list_suppressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_list_id')->constrained()->cascadeOnDelete();
            $table->string('identifier')->index(); // can be email or phone
            $table->string('reason')->nullable();
            $table->timestamps();
            
            // Prevent same identifier from being banned multiple times per list
            $table->unique(['email_list_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_list_suppressions');
    }
};
