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
        Schema::table('workflow_runs', function (Blueprint $table) {
            $table->string('current_node_id')->nullable()->after('email_id');
            $table->json('state')->nullable()->after('current_node_id');
            $table->dropColumn('current_step_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_runs', function (Blueprint $table) {
            $table->dropColumn('current_node_id');
            $table->dropColumn('state');
            $table->integer('current_step_index')->default(0);
        });
    }
};
