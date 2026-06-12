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
        // Align emails.user_id with the respective email_lists.user_id
        // to resolve visibility issues under the global user_id scope.
        $connection = Schema::connection(null)->getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE emails 
                SET user_id = (SELECT user_id FROM email_lists WHERE email_lists.id = emails.email_list_id)
                WHERE user_id != (SELECT user_id FROM email_lists WHERE email_lists.id = emails.email_list_id)
            ");
        } else {
            DB::statement("
                UPDATE emails 
                JOIN email_lists ON emails.email_list_id = email_lists.id 
                SET emails.user_id = email_lists.user_id 
                WHERE emails.user_id != email_lists.user_id
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: cannot easily undo user_id alignment
    }
};
