<?php

namespace App\Http\Controllers;

use App\Models\ContactTask;
use App\Models\Email;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $workspaceId = session('last_opened_list_id');

        $tasks = ContactTask::with('contact')
            ->whereHas('contact', function ($q) use ($workspaceId) {
                $q->where('email_list_id', $workspaceId);
            })
            ->orderByRaw("is_completed ASC, FIELD(priority, 'high', 'medium', 'low'), due_date ASC")
            ->get();

        $contacts = Email::where('email_list_id', $workspaceId)
            ->select('id', 'name', 'email')
            ->limit(500)
            ->get();

        return view('crm.tasks.index', compact('tasks', 'contacts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
            'priority'    => 'required|in:low,medium,high',
            'email_id'    => 'required|exists:emails,id',
        ]);

        ContactTask::create($request->only([
            'title', 'description', 'due_date', 'priority', 'email_id'
        ]));

        return redirect()
            ->route('admin.tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function update(Request $request, ContactTask $task)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
            'priority'    => 'required|in:low,medium,high',
            'email_id'    => 'required|exists:emails,id',
        ]);

        $task->update($request->only([
            'title', 'description', 'due_date', 'priority', 'email_id'
        ]));

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Toggle task completion.
     */
    public function toggle(ContactTask $task)
    {
        $task->update(['is_completed' => !$task->is_completed]);

        return response()->json([
            'success'      => true,
            'is_completed' => $task->is_completed,
        ]);
    }

    public function destroy(ContactTask $task)
    {
        $task->delete();
        return response()->json(['success' => true]);
    }

    /**
     * AJAX: FullCalendar JSON event feed.
     */
    public function calendarEvents()
    {
        $workspaceId = session('last_opened_list_id');

        $tasks = ContactTask::with('contact')
            ->whereHas('contact', function ($q) use ($workspaceId) {
                $q->where('email_list_id', $workspaceId);
            })
            ->whereNotNull('due_date')
            ->get()
            ->map(function ($task) {
                $colors = [
                    'high'   => ['bg' => '#ef4444', 'border' => '#dc2626'],
                    'medium' => ['bg' => '#f59e0b', 'border' => '#d97706'],
                    'low'    => ['bg' => '#6366f1', 'border' => '#4f46e5'],
                ];
                $c = $colors[$task->priority] ?? $colors['medium'];

                return [
                    'id'              => $task->id,
                    'title'           => $task->title,
                    'start'           => $task->due_date->format('Y-m-d'),
                    'backgroundColor' => $task->is_completed ? '#d1d5db' : $c['bg'],
                    'borderColor'     => $task->is_completed ? '#9ca3af' : $c['border'],
                    'textColor'       => $task->is_completed ? '#6b7280' : '#ffffff',
                    'extendedProps'   => [
                        'priority'     => $task->priority,
                        'is_completed' => $task->is_completed,
                        'contact'      => $task->contact?->name ?? $task->contact?->email ?? null,
                        'description'  => $task->description,
                    ],
                ];
            });

        return response()->json($tasks);
    }
}
