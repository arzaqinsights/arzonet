<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_activities', function (Blueprint $table) {
            // Drop old constraint and make nullable
            $table->dropForeign(['email_id']);
            $table->unsignedBigInteger('email_id')->nullable()->change();
            
            // Re-add constraint with set null
            $table->foreign('email_id')
                  ->references('id')
                  ->on('emails')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('contact_activities', function (Blueprint $table) {
            $table->dropForeign(['email_id']);
            $table->unsignedBigInteger('email_id')->nullable(false)->change();
            $table->foreign('email_id')
                  ->references('id')
                  ->on('emails')
                  ->onDelete('cascade');
        });
    }
};
