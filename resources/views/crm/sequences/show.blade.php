@extends('layouts.app')
@section('title', $sequence->name)
@section('heading', $sequence->name)

@section('content')
<div class="space-y-6 animate-slide-up" x-data="{
    activeTab: 'steps',
    showAddStepModal: false,
    showEditStepModal: false,
    editingStep: { id: null, template_id: '', delay_days: 0, subject: '' },
    openEditStep(step) {
        this.editingStep = {
            id: step.id,
            template_id: step.template_id || '',
            delay_days: step.delay_days,
            subject: step.subject
        };
        this.showEditStepModal = true;
    }
}">
    <!-- Top Back Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.sequences.index') }}" class="inline-flex items-center text-xs font-black uppercase tracking-widest text-surface-400 hover:text-surface-700 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Sequences
        </a>
    </div>

    <!-- Tabs Header -->
    <div class="bg-white border-b border-color flex items-center gap-8 -mx-6 -mt-6 mb-6 px-6">
        <button @click="activeTab = 'steps'"
            :class="activeTab === 'steps' ? 'border-brand text-brand' : 'border-transparent text-surface-700 hover:text-surface-600'"
            class="pb-3 pt-4 px-1 border-b-4 text-xs tracking-widest transition-all focus:outline-none cursor-pointer font-bold uppercase">
            Drip Steps Flow
        </button>
        <button @click="activeTab = 'enrollments'"
            :class="activeTab === 'enrollments' ? 'border-brand text-brand' : 'border-transparent text-surface-700 hover:text-surface-600'"
            class="pb-3 pt-4 px-1 border-b-4 text-xs tracking-widest transition-all focus:outline-none flex items-center gap-2 cursor-pointer font-bold uppercase">
            Enrolled Contacts
            <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded-full text-[10px]">{{ $sequence->enrollments()->count() }}</span>
        </button>
    </div>

    <!-- Drip Steps Tab -->
    <div x-show="activeTab === 'steps'" class="space-y-6">
        <div class="flex justify-between items-center bg-surface-50 border border-surface-150 p-4 rounded-md shadow-sm">
            <div>
                <h3 class="text-sm font-black text-surface-900 uppercase">Sequence Drip Flow</h3>
                <p class="text-xs text-surface-500 mt-0.5">Contacts will receive these email steps consecutively according to delays.</p>
            </div>
            <button @click="showAddStepModal = true" class="btn btn-primary rounded-md px-4 py-2 text-xs font-black uppercase tracking-widest flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Drip Step
            </button>
        </div>

        @if($sequence->steps->isEmpty())
            <div class="glass-card p-16 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-brand/5 text-brand rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-list-ol text-2xl"></i>
                </div>
                <h3 class="text-base font-black text-surface-900 mb-1">No Steps Added Yet</h3>
                <p class="text-sm text-surface-500 max-w-sm mx-auto mb-6">Add steps to this drip sequence. Each step uses an email template and dispatches after a defined delay.</p>
                <button @click="showAddStepModal = true" class="btn btn-primary rounded-md px-5 py-2.5 text-xs font-black uppercase tracking-widest">
                    Add Step 1
                </button>
            </div>
        @else
            <!-- Vertical Timeline Flow -->
            <div class="relative pl-8 space-y-8 before:absolute before:inset-y-0 before:left-4 before:w-0.5 before:bg-brand/20">
                @foreach($sequence->steps as $index => $step)
                    <div class="relative flex flex-col md:flex-row gap-4 items-start group">
                        <!-- Step Circle Marker -->
                        <div class="absolute -left-8 top-1.5 w-8 h-8 rounded-full bg-white border-2 border-brand text-brand font-black text-xs flex items-center justify-center shadow-sm z-10">
                            {{ $step->step_number }}
                        </div>

                        <!-- Step Card -->
                        <div class="flex-1 glass-card p-6 relative border-l-4 border-l-brand">
                            <div class="flex justify-between items-start gap-4">
                                <div class="space-y-1">
                                    <h4 class="text-sm font-black text-surface-900">Step {{ $step->step_number }}: {{ $step->subject }}</h4>
                                    <div class="flex flex-wrap gap-2 text-xs font-semibold text-surface-500 pt-1">
                                        <span class="badge badge-gray flex items-center gap-1">
                                            <i class="fa-regular fa-clock"></i> Delay: {{ $step->delay_days }} day(s)
                                        </span>
                                        <span class="badge badge-brand flex items-center gap-1">
                                            <i class="fa-regular fa-file-code"></i> Template: {{ $step->template ? $step->template->name : 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEditStep({{ json_encode($step) }})" class="p-1.5 text-surface-400 hover:text-brand hover:bg-brand/5 rounded-sm transition-colors border border-surface-150 bg-white" title="Edit Step">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <form action="{{ route('admin.sequences.steps.destroy', $step) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this step? Consecutive steps will be re-ordered.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-surface-400 hover:text-red-500 hover:bg-red-50 rounded-sm transition-colors border border-surface-150 bg-white" title="Delete Step">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Enrollments Tab -->
    <div x-show="activeTab === 'enrollments'" class="space-y-6" style="display: none;">
        <!-- Enrollment Controls -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Enroll New Lead Form -->
            <div class="glass-card p-6 h-fit">
                <h3 class="text-xs font-black text-surface-900 uppercase tracking-tight mb-4">Enroll New Contact</h3>
                @if($sequence->steps->isEmpty())
                    <p class="text-xs text-red-500 font-bold">Please add at least 1 drip step to this sequence before enrolling contacts.</p>
                @elseif($availableContacts->isEmpty())
                    <p class="text-xs text-surface-500">No additional contacts available in this list that are eligible for enrollment.</p>
                @else
                    <form action="{{ route('admin.sequences.enroll', $sequence) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-surface-500 uppercase tracking-widest">Select Contact(s)</label>
                            <select name="email_ids[]" multiple required class="w-full px-3 py-2 border border-surface-200 bg-surface-50 text-surface-900 rounded-sm text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all h-40">
                                @foreach($availableContacts as $contact)
                                    <option value="{{ $contact->id }}">{{ $contact->name ?: $contact->email }} ({{ $contact->email }})</option>
                                @endforeach
                            </select>
                            <p class="text-[9px] text-surface-400">Hold Command/Ctrl to select multiple contacts for bulk enrollment.</p>
                        </div>
                        <button type="submit" class="w-full btn btn-primary rounded-md py-2.5 text-xs font-black uppercase tracking-widest">
                            Enroll Selected Contacts
                        </button>
                    </form>
                @endif
            </div>

            <!-- Enrollments Table List -->
            <div class="lg:col-span-2 glass-card overflow-hidden">
                <div class="p-6 border-b border-surface-100 bg-surface-50/50">
                    <h3 class="text-xs font-black text-surface-900 uppercase tracking-tight">Active & Historical Enrollments</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>Contact</th>
                                <th class="text-center font-bold">Current Step</th>
                                <th class="text-center">Status</th>
                                <th>Scheduled Send</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sequence->enrollments as $enrollment)
                                <tr class="hover:bg-surface-50/50 transition-colors">
                                    <td class="font-bold text-surface-900">
                                        {{ $enrollment->contact->name ?: 'Unnamed Contact' }}
                                        <span class="block text-[10px] font-semibold text-surface-500 mt-0.5">{{ $enrollment->contact->email }}</span>
                                    </td>
                                    <td class="text-center font-extrabold text-surface-800 text-sm">
                                        Step {{ $enrollment->current_step_number }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $enrollment->status === 'active' ? 'badge-success' : ($enrollment->status === 'completed' ? 'badge-brand bg-indigo-50 text-indigo-600' : 'badge-gray text-red-500 bg-red-50') }}">
                                            {{ ucfirst($enrollment->status) }}
                                        </span>
                                    </td>
                                    <td class="text-surface-600 text-xs font-semibold">
                                        {{ $enrollment->scheduled_at ? $enrollment->scheduled_at->format('M d, Y H:i') : '—' }}
                                    </td>
                                    <td class="text-right">
                                        @if($enrollment->status === 'active')
                                            <form action="{{ route('admin.sequences.unenroll', $sequence) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel enrollment for this contact?')">
                                                @csrf
                                                <input type="hidden" name="email_id" value="{{ $enrollment->email_id }}">
                                                <button type="submit" class="btn btn-sm btn-ghost text-red-600">Unenroll</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-surface-400 font-bold">Closed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-12 text-surface-500">No contacts are enrolled in this campaign sequence yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Step Modal -->
    <div x-show="showAddStepModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80" style="display: none;" x-cloak>
        <div class="bg-white rounded-lg w-full max-w-md overflow-hidden shadow-2xl flex flex-col border border-surface-200" @click.away="showAddStepModal = false">
            <div class="p-5 border-b border-surface-150 bg-surface-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">Add Drip Step</h3>
                <button type="button" @click="showAddStepModal = false" class="text-surface-400 hover:text-surface-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form action="{{ route('admin.sequences.steps.store', $sequence) }}" method="POST" class="p-6 space-y-4">
                @csrf
                <!-- Subject Line -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest">Subject Line</label>
                    <input type="text" name="subject" required class="form-input rounded-md bg-surface-50 border-surface-200 py-2.5 text-sm font-semibold w-full focus:border-brand focus:ring-0" placeholder="e.g. Welcome to the workspace!">
                </div>

                <!-- Template -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest">Email Template</label>
                    <select name="template_id" required class="form-input rounded-md bg-surface-50 border-surface-200 py-2.5 text-sm font-semibold w-full focus:border-brand focus:ring-0">
                        <option value="">-- Choose Template --</option>
                        @foreach($templates as $tmpl)
                            <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Delay Days -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest">Delay Days (Wait period before sending)</label>
                    <input type="number" name="delay_days" value="0" min="0" required class="form-input rounded-md bg-surface-50 border-surface-200 py-2.5 text-sm font-semibold w-full focus:border-brand focus:ring-0" placeholder="0 = immediately, 1 = wait 1 day">
                    <p class="text-[9px] text-surface-400">Delay is applied relative to when the previous step completes.</p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-surface-100">
                    <button type="button" @click="showAddStepModal = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-md px-5 py-2 text-xs font-black uppercase tracking-widest">
                        Save Step
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Step Modal -->
    <div x-show="showEditStepModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80" style="display: none;" x-cloak>
        <div class="bg-white rounded-lg w-full max-w-md overflow-hidden shadow-2xl flex flex-col border border-surface-200" @click.away="showEditStepModal = false">
            <div class="p-5 border-b border-surface-150 bg-surface-50/50 flex justify-between items-center">
                <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">Configure Step</h3>
                <button type="button" @click="showEditStepModal = false" class="text-surface-400 hover:text-surface-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form :action="'{{ route('admin.sequences.steps.update', ':id') }}'.replace(':id', editingStep.id)" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <!-- Subject Line -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest">Subject Line</label>
                    <input type="text" name="subject" x-model="editingStep.subject" required class="form-input rounded-md bg-surface-50 border-surface-200 py-2.5 text-sm font-semibold w-full focus:border-brand focus:ring-0" placeholder="e.g. Welcome to the workspace!">
                </div>

                <!-- Template -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest">Email Template</label>
                    <select name="template_id" x-model="editingStep.template_id" required class="form-input rounded-md bg-surface-50 border-surface-200 py-2.5 text-sm font-semibold w-full focus:border-brand focus:ring-0">
                        <option value="">-- Choose Template --</option>
                        @foreach($templates as $tmpl)
                            <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Delay Days -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest">Delay Days (Wait period before sending)</label>
                    <input type="number" name="delay_days" x-model="editingStep.delay_days" min="0" required class="form-input rounded-md bg-surface-50 border-surface-200 py-2.5 text-sm font-semibold w-full focus:border-brand focus:ring-0">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-surface-100">
                    <button type="button" @click="showEditStepModal = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-md px-5 py-2 text-xs font-black uppercase tracking-widest">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
