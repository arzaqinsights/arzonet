<?php

namespace App\Jobs;

use App\Models\WorkflowRun;
use App\Models\Workflow;
use App\Models\Email;
use App\Models\Template;
use App\Models\Sender;
use App\Models\ContactNote;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessAutomationWorkflowsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(MailService $mailService): void
    {
        $runs = WorkflowRun::where('status', 'active')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($runs as $run) {
            try {
                $workflow = $run->workflow;
                $contact = $run->contact;

                if (!$workflow || !$workflow->is_active || !$contact || $contact->is_archived) {
                    $run->update(['status' => 'failed']);
                    continue;
                }

                if ($contact->subscription_status !== 'subscribed') {
                    $run->update(['status' => 'failed']);
                    continue;
                }

                $nodes = $workflow->nodes ?? [];
                $currentNodeId = $run->current_node_id;

                if (!$currentNodeId || !isset($nodes[$currentNodeId])) {
                    $run->update(['status' => 'completed']);
                    continue;
                }

                $node = $nodes[$currentNodeId];
                $type = $node['type'] ?? null;
                $details = $node['details'] ?? [];
                
                $nextNodeId = null;

                if ($type === 'wait') {
                    $delay = (int)($details['delay'] ?? 1);
                    $unit = $details['unit'] ?? 'days';
                    $nextScheduledAt = now();

                    if ($unit === 'minutes') {
                        $nextScheduledAt->addMinutes($delay);
                    } elseif ($unit === 'hours') {
                        $nextScheduledAt->addHours($delay);
                    } else {
                        $nextScheduledAt->addDays($delay);
                    }

                    $nextNodeId = $node['next'] ?? null;

                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => $nextScheduledAt,
                        'last_executed_at' => now(),
                    ]);

                } elseif ($type === 'send_email') {
                    $templateId = $details['template_id'] ?? null;
                    $template = Template::find($templateId);

                    if ($template) {
                        $sender = Sender::where('user_id', $workflow->user_id)->first();
                        
                        $recipientData = $contact->toArray();
                        $recipientData['name'] = $contact->name ?? 'Contact';
                        
                        $html = $mailService->replaceVariables($template->html_content, $recipientData);
                        $subjectLine = $details['subject'] ?? ($template->name ?? "Notification");
                        $subject = $mailService->replaceVariables($subjectLine, $recipientData, false);

                        if ($sender) {
                            try {
                                $mailService->send($sender, $contact->email, $subject, $html, $contact);
                            } catch (\Exception $e) {
                                Log::error("Workflow email send failed for run {$run->id}: " . $e->getMessage());
                                Mail::html($html, function ($message) use ($contact, $subject) {
                                    $message->to($contact->email)->subject($subject);
                                });
                            }
                        } else {
                            Mail::html($html, function ($message) use ($contact, $subject) {
                                $message->to($contact->email)->subject($subject);
                            });
                        }
                    }

                    $nextNodeId = $node['next'] ?? null;
                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => now(),
                        'last_executed_at' => now(),
                    ]);

                } elseif ($type === 'add_tag') {
                    $tag = $details['tag'] ?? '';
                    if (!empty($tag)) {
                        $tags = $contact->tags ?? [];
                        if (!in_array($tag, $tags)) {
                            $tags[] = $tag;
                            $contact->tags = $tags;
                            $contact->save();
                        }
                    }

                    $nextNodeId = $node['next'] ?? null;
                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => now(),
                        'last_executed_at' => now(),
                    ]);
                } elseif ($type === 'remove_tag') {
                    $tag = $details['tag'] ?? '';
                    if (!empty($tag)) {
                        $tags = $contact->tags ?? [];
                        $tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
                        $contact->tags = $tags;
                        $contact->save();
                    }

                    $nextNodeId = $node['next'] ?? null;
                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => now(),
                        'last_executed_at' => now(),
                    ]);
                } elseif ($type === 'add_note') {
                    $note = $details['note'] ?? '';
                    if (!empty($note)) {
                        ContactNote::create([
                            'contact_id' => $contact->id,
                            'note' => $note,
                            'created_by' => $workflow->user_id
                        ]);
                    }

                    $nextNodeId = $node['next'] ?? null;
                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => now(),
                        'last_executed_at' => now(),
                    ]);
                } elseif ($type === 'create_deal') {
                    $stageId = $details['stage_id'] ?? null;
                    if (!empty($stageId)) {
                        \App\Models\Deal::create([
                            'pipeline_stage_id' => $stageId,
                            'email_id' => $contact->id,
                            'email_list_id' => $contact->email_list_id,
                            'title' => $details['title'] ?? (($contact->name ?: $contact->email) . ' Deal'),
                            'value' => $details['value'] ?? 0,
                            'currency' => 'USD',
                            'status' => 'open',
                            'user_id' => $workflow->user_id,
                            'order' => \App\Models\Deal::where('pipeline_stage_id', $stageId)->count(),
                        ]);
                    }

                    $nextNodeId = $node['next'] ?? null;
                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => now(),
                        'last_executed_at' => now(),
                    ]);
                } elseif ($type === 'create_task') {
                    $title = $details['title'] ?? 'Workflow Task';
                    \App\Models\ContactTask::create([
                        'email_id' => $contact->id,
                        'user_id' => $workflow->user_id,
                        'title' => $title,
                        'description' => $details['description'] ?? null,
                        'due_date' => isset($details['due_in_days']) ? now()->addDays((int)$details['due_in_days']) : null,
                        'status' => 'pending'
                    ]);

                    $nextNodeId = $node['next'] ?? null;
                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => now(),
                        'last_executed_at' => now(),
                    ]);
                } elseif ($type === 'if_else') {
                    $conditionType = $details['condition_type'] ?? '';
                    $value = $details['value'] ?? '';
                    $isTrue = false;

                    if ($conditionType === 'has_tag') {
                        $tags = $contact->tags ?? [];
                        $isTrue = in_array($value, $tags);
                    } elseif ($conditionType === 'does_not_have_tag') {
                        $tags = $contact->tags ?? [];
                        $isTrue = !in_array($value, $tags);
                    } elseif ($conditionType === 'has_topic') {
                        // Assuming topics are handled via related models or an array. 
                        // For now we check the topics array if it exists.
                        $topics = $contact->topics ?? [];
                        $isTrue = in_array($value, is_array($topics) ? $topics : (json_decode($topics, true) ?? []));
                    }

                    $nextNodeId = $isTrue ? ($node['next_true'] ?? null) : ($node['next_false'] ?? null);
                    
                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => now(),
                        'last_executed_at' => now(),
                    ]);
                } else {
                    $nextNodeId = $node['next'] ?? null;
                    $run->update([
                        'current_node_id' => $nextNodeId,
                        'scheduled_at' => now(),
                        'last_executed_at' => now(),
                    ]);
                }

                $updatedRun = $run->fresh();
                if ($updatedRun && $updatedRun->status === 'active' && !$updatedRun->current_node_id) {
                    $updatedRun->update(['status' => 'completed']);
                }

            } catch (\Exception $e) {
                Log::error("WorkflowRun {$run->id} failed: " . $e->getMessage());
                $run->update(['status' => 'failed']);
            }
        }
    }
}
