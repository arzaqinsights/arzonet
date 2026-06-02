<?php 
try { 
    $list = App\Models\EmailList::find(63); 
    $emails = clone $list->emails(); 
    $emails->where("id", 100); 
    dump("count before", clone $emails->count()); 
    $emails->chunkById(500, function() {}); 
    dump("count after", clone $emails->count()); 
} catch (\Throwable $e) { 
    dump($e->getMessage()); 
}
