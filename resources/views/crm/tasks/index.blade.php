@extends('layouts.app')
@section('title', 'Tasks & Calendar')
@section('heading', 'Tasks & Calendar')

@section('content')
<div x-data="taskManager()" class="animate-slide-up">

    {{-- Split Screen Container --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Left Panel: Task Checklist --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xs font-black text-surface-900 uppercase tracking-widest">Task Checklist</h2>
                <button @click="showAddTask = true"
                    class="px-4 py-2 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">
                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                    Add Task
                </button>
            </div>

            {{-- Task Stats --}}
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="bg-white p-3 rounded-sm border border-gray-100 text-center">
                    <p class="text-[9px] font-black text-surface-400 uppercase">Total</p>
                    <p class="text-xl font-black text-surface-900">{{ $tasks->count() }}</p>
                </div>
                <div class="bg-white p-3 rounded-sm border border-gray-100 text-center">
                    <p class="text-[9px] font-black text-emerald-500 uppercase">Done</p>
                    <p class="text-xl font-black text-emerald-600">{{ $tasks->where('is_completed', true)->count() }}</p>
                </div>
                <div class="bg-white p-3 rounded-sm border border-gray-100 text-center">
                    <p class="text-[9px] font-black text-amber-500 uppercase">Pending</p>
                    <p class="text-xl font-black text-amber-600">{{ $tasks->where('is_completed', false)->count() }}</p>
                </div>
            </div>

            {{-- Task List --}}
            <div class="glass-card overflow-hidden max-h-[600px] overflow-y-auto scrollbar">
                @forelse($tasks as $task)
                    @php
                        $priorityCls = match($task->priority) {
                            'high'   => 'border-l-red-500 bg-red-50/30',
                            'medium' => 'border-l-amber-500 bg-amber-50/30',
                            'low'    => 'border-l-indigo-500 bg-indigo-50/30',
                            default  => '',
                        };
                        $priorityBadge = match($task->priority) {
                            'high'   => 'badge-danger',
                            'medium' => 'badge-warning',
                            'low'    => 'badge-info',
                            default  => 'badge-neutral',
                        };
                    @endphp
                    <div class="flex items-start gap-4 p-4 border-b border-surface-100 border-l-4 {{ $priorityCls }} group hover:bg-surface-50/50 transition-colors"
                         id="task-row-{{ $task->id }}">
                        {{-- Checkbox --}}
                        <button @click="toggleTask({{ $task->id }})"
                            class="mt-0.5 w-5 h-5 rounded-sm border-2 flex items-center justify-center shrink-0 cursor-pointer transition-all
                            {{ $task->is_completed ? 'bg-emerald-500 border-emerald-500' : 'border-surface-300 hover:border-brand' }}"
                            id="task-check-{{ $task->id }}">
                            @if($task->is_completed)
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </button>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm {{ $task->is_completed ? 'line-through text-surface-400' : 'text-surface-900' }}"
                               id="task-title-{{ $task->id }}">
                                {{ $task->title }}
                            </p>
                            @if($task->description)
                                <p class="text-xs text-surface-500 mt-1 truncate">{{ $task->description }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-2">
                                <span class="badge {{ $priorityBadge }} text-[9px]">{{ ucfirst($task->priority) }}</span>
                                @if($task->due_date)
                                    <span class="text-[10px] font-medium text-surface-400 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ $task->due_date->format('M d, Y') }}
                                    </span>
                                @endif
                                @if($task->contact)
                                    <span class="text-[10px] font-medium text-surface-400">
                                        → {{ $task->contact->name ?? $task->contact->email }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Delete --}}
                        <button @click="deleteTask({{ $task->id }})"
                            class="opacity-0 group-hover:opacity-100 text-surface-300 hover:text-red-500 transition-all cursor-pointer p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <svg class="w-12 h-12 text-surface-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <p class="text-sm font-medium text-surface-400">No tasks yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right Panel: Calendar --}}
        <div class="lg:col-span-3">
            <div class="glass-card p-6">
                <h2 class="text-xs font-black text-surface-900 uppercase tracking-widest mb-4">Calendar</h2>
                <div id="task-calendar" style="min-height: 550px;"></div>
            </div>
        </div>
    </div>

    {{-- Add Task Modal --}}
    <div x-show="showAddTask" x-cloak class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" @click.self="showAddTask = false">
        <div class="bg-white rounded-sm shadow-2xl w-full max-w-lg animate-slide-up" @keydown.escape.window="showAddTask = false">
            <div class="p-6 border-b border-surface-100">
                <h3 class="text-lg font-black text-surface-900">New Task</h3>
            </div>
            <form action="{{ route('admin.tasks.store') }}" method="POST">
                @csrf
                <div class="p-6 space-y-5">
                    <div>
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-input" placeholder="e.g. Follow up with John" required>
                    </div>
                    <div>
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input" rows="3" placeholder="Task details..."></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Priority *</label>
                            <select name="priority" class="form-select" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Link Contact</label>
                        <select name="email_id" class="form-select">
                            <option value="">— No contact —</option>
                            @foreach($contacts as $c)
                                <option value="{{ $c->id }}">{{ $c->name ?? $c->email }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="p-6 border-t border-surface-100 flex justify-end gap-3">
                    <button type="button" @click="showAddTask = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('head')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
function taskManager() {
    return {
        showAddTask: false,

        init() {
            this.$nextTick(() => {
                const calendarEl = document.getElementById('task-calendar');
                if (!calendarEl) return;

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek'
                    },
                    height: 550,
                    events: '{{ route("admin.tasks.calendar-events") }}',
                    eventDisplay: 'block',
                    dayMaxEvents: 3,
                    eventDidMount: function(info) {
                        if (info.event.extendedProps.is_completed) {
                            info.el.style.opacity = '0.5';
                            info.el.style.textDecoration = 'line-through';
                        }
                    }
                });
                calendar.render();
            });
        },

        toggleTask(taskId) {
            fetch('/tasks/' + taskId + '/toggle', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const check = document.getElementById('task-check-' + taskId);
                    const title = document.getElementById('task-title-' + taskId);

                    if (data.is_completed) {
                        check.classList.add('bg-emerald-500', 'border-emerald-500');
                        check.classList.remove('border-surface-300');
                        check.innerHTML = '<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
                        title.classList.add('line-through', 'text-surface-400');
                        title.classList.remove('text-surface-900');
                    } else {
                        check.classList.remove('bg-emerald-500', 'border-emerald-500');
                        check.classList.add('border-surface-300');
                        check.innerHTML = '';
                        title.classList.remove('line-through', 'text-surface-400');
                        title.classList.add('text-surface-900');
                    }
                }
            });
        },

        deleteTask(taskId) {
            if (!confirm('Delete this task?')) return;

            fetch('/tasks/' + taskId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('task-row-' + taskId)?.remove();
                }
            });
        }
    };
}
</script>
@endpush
@endsection
