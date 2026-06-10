<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use \App\Traits\BelongsToUser;

    protected $fillable = [
        'user_id',
        'email_list_id',
        'name',
        'description',
        'trigger_type',
        'trigger_value',
        'nodes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nodes' => 'array',
            'is_active' => 'boolean',
            'email_list_id' => 'integer',
        ];
    }

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public static function trigger(string $type, $contact, $value = null): void
    {
        $workflows = self::where('email_list_id', $contact->email_list_id)
            ->where('is_active', true)
            ->where('trigger_type', $type)
            ->get();

        foreach ($workflows as $workflow) {
            if ($type === 'topic_subscribe' && (string)$workflow->trigger_value !== (string)$value) {
                continue;
            }
            if ($type === 'tag_added' && strtolower(trim($workflow->trigger_value)) !== strtolower(trim($value))) {
                continue;
            }

            $exists = WorkflowRun::where('workflow_id', $workflow->id)
                ->where('email_id', $contact->id)
                ->where('status', 'active')
                ->exists();

            if (!$exists) {
                WorkflowRun::create([
                    'workflow_id' => $workflow->id,
                    'email_id' => $contact->id,
                    'current_node_id' => 'start',
                    'status' => 'active',
                    'scheduled_at' => now(),
                ]);
            }
        }
    }
}
