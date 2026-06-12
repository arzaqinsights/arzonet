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
            $table->unsignedBigInteger('unique_contacts_count')->default(0)->after('cross_duplicate_count');
        });

        // Pre-populate from existing data
        $lists = \App\Models\EmailList::all();
        foreach ($lists as $list) {
            $groupExpr = "CASE WHEN name IS NOT NULL AND TRIM(name) != '' THEN CONCAT('name_', LOWER(TRIM(name))) WHEN original_row_id IS NOT NULL AND TRIM(original_row_id) != '' THEN CONCAT('orig_', original_row_id) ELSE CONCAT('id_', id) END";
            $count = \Illuminate\Support\Facades\DB::table('emails')
                ->where('email_list_id', $list->id)
                ->where('is_archived', false)
                ->count(\Illuminate\Support\Facades\DB::raw('DISTINCT ' . $groupExpr));
            $list->update(['unique_contacts_count' => $count]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_lists', function (Blueprint $table) {
            $table->dropColumn('unique_contacts_count');
        });
    }
};
