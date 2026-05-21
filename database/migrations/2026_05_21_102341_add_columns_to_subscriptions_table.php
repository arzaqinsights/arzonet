<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'selected_modules')) {
                $table->json('selected_modules')->nullable()->after('emails_limit');
            }
            if (!Schema::hasColumn('subscriptions', 'whatsapp_limit')) {
                $table->integer('whatsapp_limit')->default(0)->after('selected_modules');
            }
            if (!Schema::hasColumn('subscriptions', 'team_limit')) {
                $table->integer('team_limit')->default(0)->after('whatsapp_limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('subscriptions', 'selected_modules')) {
                $columnsToDrop[] = 'selected_modules';
            }
            if (Schema::hasColumn('subscriptions', 'whatsapp_limit')) {
                $columnsToDrop[] = 'whatsapp_limit';
            }
            if (Schema::hasColumn('subscriptions', 'team_limit')) {
                $columnsToDrop[] = 'team_limit';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
