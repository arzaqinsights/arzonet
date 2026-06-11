<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add email_list_id to pipelines if it doesn't exist
        if (!Schema::hasColumn('pipelines', 'email_list_id')) {
            Schema::table('pipelines', function (Blueprint $table) {
                $table->foreignId('email_list_id')->nullable()->after('id')->constrained('email_lists')->nullOnDelete();
            });
        }

        $firstList = DB::table('email_lists')->orderBy('id', 'asc')->first();
        if ($firstList) {
            DB::table('segments')->whereNull('email_list_id')->update(['email_list_id' => $firstList->id]);
            DB::table('pipelines')->whereNull('email_list_id')->update(['email_list_id' => $firstList->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            //
        });
    }
};
