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
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop old foreign key with cascade delete
            $table->dropForeign(['email_list_id']);
            
            // Re-create it with nullOnDelete()
            $table->foreign('email_list_id')
                ->references('id')
                ->on('email_lists')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop our new foreign key
            $table->dropForeign(['email_list_id']);
            
            // Re-create the cascade delete one
            $table->foreign('email_list_id')
                ->references('id')
                ->on('email_lists')
                ->cascadeOnDelete();
        });
    }
};
