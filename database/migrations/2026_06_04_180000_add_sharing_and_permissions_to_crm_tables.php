<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_lists', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('user_id');
            $table->foreignId('created_by_id')->nullable()->after('is_public')->constrained('users')->onDelete('set null');
            $table->json('team_permissions')->nullable()->after('created_by_id');
        });

        Schema::table('pipelines', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('user_id');
            $table->foreignId('created_by_id')->nullable()->after('is_public')->constrained('users')->onDelete('set null');
            $table->json('team_permissions')->nullable()->after('created_by_id');
        });

        // Seed created_by_id for existing records
        DB::table('email_lists')->update(['created_by_id' => DB::raw('user_id')]);
        DB::table('pipelines')->update(['created_by_id' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        Schema::table('email_lists', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropColumn(['is_public', 'created_by_id', 'team_permissions']);
        });

        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropForeign(['created_by_id']);
            $table->dropColumn(['is_public', 'created_by_id', 'team_permissions']);
        });
    }
};
