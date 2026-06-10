<?php
$keys = Illuminate\Support\Facades\Redis::keys('list_filters:*');
foreach ($keys as $key) {
    // If you are using a prefix, you might need to strip it, but let's just delete by exact name
    // actually keys() returns keys without prefix sometimes depending on the client.
    Illuminate\Support\Facades\Redis::del($key);
}

// In case it has laravel_database_ prefix
$dbKeys = Illuminate\Support\Facades\Redis::keys('laravel_database_list_filters:*');
foreach ($dbKeys as $key) {
    $stripped = str_replace('laravel_database_', '', $key);
    Illuminate\Support\Facades\Redis::del($stripped);
}

echo "Cache cleared.";
