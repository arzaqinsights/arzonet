<?php

namespace App\Http\Controllers;

use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\SequenceEnrollment;
use App\Models\EmailList;
use App\Models\Template;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SequenceController extends Controller
{
    protected function getActiveWorkspaceId()
    {
        $workspaceId = session('last_opened_list_id');
        if (!$workspaceId) {
            abort(400, 'Please select or create a workspace first.');
        }
        return $workspaceId;
    }

    public function index()
    {
        $workspaceId = $this->getActiveWorkspaceId();
        $emailList = EmailList::findOrFail($workspaceId);

        $sequences = Sequence::where('email_list_id', $workspaceId)
            ->withCount(['steps', 'enrollments as active_enrollments_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->latest()
            ->paginate(15);

        return view('crm.sequences.index', compact('sequences', 'emailList'));
    }

    public function create()
    {
        $workspaceId = $this->getActiveWorkspaceId();
        $emailList = EmailList::findOrFail($workspaceId);
        return view('crm.sequences.create', compact('emailList'));
    }

    public function store(Request $request)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $sequence = Sequence::create([
            'email_list_id' => $workspaceId,
            'user_id' => Auth::user()->getOwnerId(),
            'name' => $request->name,
        ]);

        return redirect()
            ->route('admin.sequences.show', $sequence)
            ->with('success', 'Sequence created successfully. Now add some steps!');
    }

    public function show(Sequence $sequence)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $sequence->load(['steps.template', 'enrollments.contact']);
        $templates = Template::latest()->get();

        // Query available contacts in list that are not currently active in this sequence
        $enrolledIds = $sequence->enrollments()->pluck('email_id')->toArray();
        $availableContacts = Email::where('email_list_id', $workspaceId)
            ->where('is_archived', false)
            ->where('subscription_status', 'subscribed')
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('name')
            ->take(100)
            ->get();

        return view('crm.sequences.show', compact('sequence', 'templates', 'availableContacts'));
    }

    public function edit(Sequence $sequence)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        return view('crm.sequences.edit', compact('sequence'));
    }

    public function update(Request $request, Sequence $sequence)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $sequence->update([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('admin.sequences.index')
            ->with('success', 'Sequence updated successfully.');
    }

    public function destroy(Sequence $sequence)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $sequence->delete();

        return redirect()
            ->route('admin.sequences.index')
            ->with('success', 'Sequence deleted.');
    }

    // --- Sequence Steps Management ---
    public function storeStep(Request $request, Sequence $sequence)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'template_id' => 'required|exists:templates,id',
            'delay_days' => 'required|integer|min:0',
            'subject' => 'required|string|max:255',
        ]);

        $nextStepNumber = $sequence->steps()->count() + 1;

        $sequence->steps()->create([
            'step_number' => $nextStepNumber,
            'template_id' => $request->template_id,
            'delay_days' => $request->delay_days,
            'subject' => $request->subject,
        ]);

        return back()->with('success', 'Step added successfully.');
    }

    public function updateStep(Request $request, SequenceStep $step)
    {
        $sequence = $step->sequence;
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'template_id' => 'required|exists:templates,id',
            'delay_days' => 'required|integer|min:0',
            'subject' => 'required|string|max:255',
        ]);

        $step->update([
            'template_id' => $request->template_id,
            'delay_days' => $request->delay_days,
            'subject' => $request->subject,
        ]);

        return back()->with('success', 'Step updated successfully.');
    }

    public function destroyStep(SequenceStep $step)
    {
        $sequence = $step->sequence;
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $deletedNumber = $step->step_number;
        $step->delete();

        // Reorder subsequent steps
        $sequence->steps()
            ->where('step_number', '>', $deletedNumber)
            ->decrement('step_number');

        return back()->with('success', 'Step removed.');
    }

    // --- Enrollment Management ---
    public function enrollContact(Request $request, Sequence $sequence)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'email_ids' => 'required|array',
            'email_ids.*' => 'exists:emails,id',
        ]);

        $firstStep = $sequence->steps()->where('step_number', 1)->first();
        if (!$firstStep) {
            return back()->with('error', 'Cannot enroll contacts: this sequence has no steps configured.');
        }

        $delay = $firstStep->delay_days;
        $scheduledAt = now()->addDays($delay);

        $enrolledCount = 0;
        foreach ($request->email_ids as $emailId) {
            // Check if already enrolled in this sequence
            $existing = SequenceEnrollment::where('sequence_id', $sequence->id)
                ->where('email_id', $emailId)
                ->first();

            if ($existing) {
                if ($existing->status !== 'active') {
                    $existing->update([
                        'status' => 'active',
                        'current_step_number' => 1,
                        'scheduled_at' => $scheduledAt,
                    ]);
                    $enrolledCount++;
                }
            } else {
                SequenceEnrollment::create([
                    'sequence_id' => $sequence->id,
                    'email_id' => $emailId,
                    'current_step_number' => 1,
                    'status' => 'active',
                    'scheduled_at' => $scheduledAt,
                ]);
                $enrolledCount++;
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Enrolled {$enrolledCount} contact(s) into the sequence."]);
        }
        return back()->with('success', "Enrolled {$enrolledCount} contact(s) into the sequence.");
    }

    public function unenrollContact(Request $request, Sequence $sequence)
    {
        $workspaceId = $this->getActiveWorkspaceId();
        if ($sequence->email_list_id !== $workspaceId) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'email_id' => 'required|exists:emails,id',
        ]);

        SequenceEnrollment::where('sequence_id', $sequence->id)
            ->where('email_id', $request->email_id)
            ->update([
                'status' => 'cancelled',
                'scheduled_at' => null,
            ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Contact unenrolled from sequence.']);
        }
        return back()->with('success', 'Contact unenrolled from sequence.');
    }
}
