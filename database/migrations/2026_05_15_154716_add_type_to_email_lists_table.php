<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_lists', function (Blueprint $table) {
            // 'email' = Email only, 'whatsapp' = WhatsApp only, 'dual' = Both
            $table->string('list_type')->default('email')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('email_lists', function (Blueprint $table) {
            $table->dropColumn('list_type');
        });
    }
};
