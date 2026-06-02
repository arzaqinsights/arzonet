<?php 
try { 
    \App\Models\EmailListSuppression::upsert([
        ['email_list_id' => 63, 'identifier' => 'test@example.com', 'reason' => 'test', 'created_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString()]
    ], ['email_list_id', 'identifier'], ['reason', 'updated_at']); 
    dump('success'); 
} catch (\Throwable $e) { 
    dump($e->getMessage()); 
}
