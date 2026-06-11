<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Segment extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'email_list_id',
        'name',
        'description',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'email_list_id' => 'integer',
        ];
    }

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    /**
     * Apply the segment rules to a given Eloquent builder.
     */
    public static function applyRulesToQuery($query, array $rules)
    {
        foreach ($rules as $rule) {
            $field    = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? null;
            $value    = $rule['value'] ?? null;

            if (!$field || !$operator) continue;

            // Standard fields in emails table
            $standardFields = [
                'name', 'email', 'engagement_score', 'status', 'email_status',
                'subscription_status', 'whatsapp_subscription_status',
                'is_role_based', 'is_disposable', 'is_catch_all', 'has_typo',
                'bounce_count', 'complaint_count', 'email_score',
                'last_engaged_at', 'last_active_at', 'whatsapp_number',
                'email_lead_score', 'whatsapp_lead_score', 'last_campaign_status', 'last_bounce_type'
            ];

            if (in_array($field, $standardFields)) {
                match ($operator) {
                    'equals'       => $query->where($field, '=', $value),
                    'not_equals'   => $query->where($field, '!=', $value),
                    'contains'     => $query->where($field, 'LIKE', "%{$value}%"),
                    'greater_than' => $query->where($field, '>', $value),
                    'less_than'    => $query->where($field, '<', $value),
                    'is_empty'     => $query->where(function ($q) use ($field) {
                                          $q->whereNull($field)->orWhere($field, '');
                                      }),
                    'is_not_empty' => $query->whereNotNull($field)->where($field, '!=', ''),
                    'recent_days'  => $query->where($field, '>=', now()->subDays((int)$value)),
                    default        => null,
                };
            } elseif ($field === 'tag') {
                match ($operator) {
                    'equals', 'contains' => $query->whereJsonContains('tags', $value),
                    'not_equals'         => $query->whereJsonDoesntContain('tags', $value),
                    'is_empty'           => $query->whereNull('tags')->orWhereJsonLength('tags', 0),
                    'is_not_empty'       => $query->whereNotNull('tags')->whereJsonLength('tags', '>', 0),
                    default              => null,
                };
            } elseif ($field === 'topic') {
                match ($operator) {
                    'equals', 'contains' => $query->whereJsonContains('subscribed_topics', (string)$value),
                    'not_equals'         => $query->whereJsonDoesntContain('subscribed_topics', (string)$value),
                    default              => null,
                };
            } elseif ($field === 'last_sent_at') {
                match ($operator) {
                    'recent_days' => $query->whereExists(function ($q) use ($value) {
                        $q->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('email_logs')
                          ->whereColumn('email_logs.email_id', 'emails.id')
                          ->where('email_logs.sent_at', '>=', now()->subDays((int)$value));
                    }),
                    default => null,
                };
            } else {
                // Custom field — stored in meta JSON
                $jsonPath = "$.{$field}";
                match ($operator) {
                    'equals'       => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) = ?", [$jsonPath, $value]),
                    'not_equals'   => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) != ?", [$jsonPath, $value]),
                    'contains'     => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) LIKE ?", [$jsonPath, "%{$value}%"]),
                    'greater_than' => $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) AS DECIMAL) > ?", [$jsonPath, $value]),
                    'less_than'    => $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) AS DECIMAL) < ?", [$jsonPath, $value]),
                    'is_empty'     => $query->whereRaw("JSON_EXTRACT(meta, ?) IS NULL", [$jsonPath])
                                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) = ''", [$jsonPath]),
                    'is_not_empty' => $query->whereRaw("JSON_EXTRACT(meta, ?) IS NOT NULL", [$jsonPath])
                                            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, ?)) != ''", [$jsonPath]),
                    default        => null,
                };
            }
        }
        return $query;
    }

    /**
     * Check if a single email contact matches this segment's rules in memory.
     */
    public function matchesContact(Email $email): bool
    {
        $rules = $this->rules ?? [];
        if (empty($rules)) return false;

        foreach ($rules as $rule) {
            $field    = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? null;
            $value    = $rule['value'] ?? null;

            if (!$field || !$operator) continue;

            $contactValue = null;

            $standardFields = [
                'name', 'email', 'engagement_score', 'status', 'email_status',
                'subscription_status', 'whatsapp_subscription_status',
                'is_role_based', 'is_disposable', 'is_catch_all', 'has_typo',
                'bounce_count', 'complaint_count', 'email_score',
                'last_engaged_at', 'last_active_at', 'whatsapp_number',
                'email_lead_score', 'whatsapp_lead_score', 'last_campaign_status', 'last_bounce_type'
            ];

            if (in_array($field, $standardFields)) {
                $contactValue = $email->getAttribute($field);
            } elseif ($field === 'tag') {
                $tags = $email->tags ?? [];
                $hasTag = in_array($value, $tags);
                if ($operator === 'equals' || $operator === 'contains') {
                    if (!$hasTag) return false;
                } elseif ($operator === 'not_equals') {
                    if ($hasTag) return false;
                } elseif ($operator === 'is_empty') {
                    if (count($tags) > 0) return false;
                } elseif ($operator === 'is_not_empty') {
                    if (count($tags) === 0) return false;
                }
                continue;
            } elseif ($field === 'topic') {
                $topics = $email->subscribed_topics ?? [];
                $hasTopic = in_array((string)$value, array_map('strval', $topics));
                if ($operator === 'equals' || $operator === 'contains') {
                    if (!$hasTopic) return false;
                } elseif ($operator === 'not_equals') {
                    if ($hasTopic) return false;
                }
                continue;
            } elseif ($field === 'last_sent_at') {
                $lastLog = $email->logs()->latest('sent_at')->first();
                $contactValue = $lastLog ? $lastLog->sent_at : null;
            } else {
                $meta = $email->meta ?? [];
                $contactValue = $meta[$field] ?? null;
            }

            switch ($operator) {
                case 'equals':
                    if (strval($contactValue) !== strval($value)) return false;
                    break;
                case 'not_equals':
                    if (strval($contactValue) === strval($value)) return false;
                    break;
                case 'contains':
                    if (stripos(strval($contactValue), strval($value)) === false) return false;
                    break;
                case 'greater_than':
                    if (floatval($contactValue) <= floatval($value)) return false;
                    break;
                case 'less_than':
                    if (floatval($contactValue) >= floatval($value)) return false;
                    break;
                case 'is_empty':
                    if ($contactValue !== null && $contactValue !== '') return false;
                    break;
                case 'is_not_empty':
                    if ($contactValue === null || $contactValue === '') return false;
                    break;
                case 'recent_days':
                    if (!$contactValue) return false;
                    $date = \Carbon\Carbon::parse($contactValue);
                    if ($date->lt(now()->subDays((int)$value))) return false;
                    break;
                default:
                    return false;
            }
        }

        return true;
    }

    /**
     * Seed default segments for an email list.
     */
    public static function seedDefaultsFor(int $emailListId, int $userId): void
    {
        $defaults = [
            [
                'name' => 'Recent Openers',
                'description' => 'Contacts who engaged in the last 7 days.',
                'rules' => [
                    ['field' => 'last_engaged_at', 'operator' => 'recent_days', 'value' => 7]
                ]
            ],
            [
                'name' => 'Recent Clickers',
                'description' => 'Contacts who interacted recently.',
                'rules' => [
                    ['field' => 'last_active_at', 'operator' => 'recent_days', 'value' => 7]
                ]
            ],
            [
                'name' => 'Without Name',
                'description' => 'Contacts with missing names.',
                'rules' => [
                    ['field' => 'name', 'operator' => 'is_empty', 'value' => '']
                ]
            ],
            [
                'name' => 'Unsubscribed',
                'description' => 'Contacts who opted out.',
                'rules' => [
                    ['field' => 'subscription_status', 'operator' => 'equals', 'value' => 'unsubscribed']
                ]
            ],
            [
                'name' => 'Valid Contacts',
                'description' => 'Contacts with verified valid email addresses.',
                'rules' => [
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'valid']
                ]
            ],
            [
                'name' => 'Invalid Contacts',
                'description' => 'Contacts with invalid or bouncing addresses.',
                'rules' => [
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'invalid']
                ]
            ]
        ];

        foreach ($defaults as $default) {
            static::firstOrCreate([
                'user_id' => $userId,
                'email_list_id' => $emailListId,
                'name' => $default['name'],
            ], [
                'description' => $default['description'],
                'rules' => $default['rules']
            ]);
        }
    }
}
