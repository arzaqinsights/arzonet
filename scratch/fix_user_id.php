<?php
$content = file_get_contents('app/Http/Controllers/EmailListController.php');
$content = str_replace(
    "'user_id' => auth()->id(),",
    "'user_id' => app()->has('team_user') ? app('team_user')->id : auth()->id(),",
    $content
);
$content = str_replace(
    "'user_id' => auth()->id()",
    "'user_id' => app()->has('team_user') ? app('team_user')->id : auth()->id()",
    $content
);
file_put_contents('app/Http/Controllers/EmailListController.php', $content);

echo "Fixed EmailListController.php\n";
