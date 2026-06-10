<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionTopic extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'email_list_id',
        'name',
        'description',
    ];

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public static function seedDefaultsFor(int $emailListId, int $userId): void
    {
        $defaultTopics = [
            [
                'name' => 'Newsletters',
                'description' => 'Regular updates, digests, and featured articles.'
            ],
            [
                'name' => 'Marketing',
                'description' => 'Promotions, special offers, and new campaigns.'
            ],
            [
                'name' => 'Communications',
                'description' => 'General announcements, service updates, and direct messages.'
            ],
        ];

        foreach ($defaultTopics as $topic) {
            self::create([
                'user_id'       => $userId,
                'email_list_id' => $emailListId,
                'name'          => $topic['name'],
                'description'   => $topic['description'],
            ]);
        }
    }
}
