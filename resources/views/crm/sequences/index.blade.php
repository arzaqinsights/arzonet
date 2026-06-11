@extends('layouts.app')
@section('title', 'Sales Sequences')
@section('heading', 'Sales Email Sequences (Drip Campaigns)')

@section('content')
<div class="space-y-6 animate-slide-up">
    <!-- Header Summary Card -->
    <div class="glass-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">Active Workspace List: {{ $emailList->name }}</h3>
            <p class="text-xs text-surface-500 mt-1">Design automated multi-step drip email campaigns for this audience workspace list.</p>
        </div>
        @if($emailList->canPerformAction('edit_contact'))
            <a href="{{ route('admin.sequences.create') }}" class="btn btn-primary rounded-md px-5 py-2.5 text-xs font-black uppercase tracking-widest flex items-center gap-2 self-start md:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Create Sequence
            </a>
        @endif
    </div>

    <!-- Sequences Table -->
    @if($sequences->isEmpty())
        <div class="glass-card p-16 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-brand/10 flex items-center justify-center text-brand">
                <i class="fa-solid fa-paper-plane text-3xl"></i>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-2">No Sequences Configured</h3>
            <p class="text-surface-500 text-sm max-w-md mx-auto mb-6">Create your first automated email sequence to engage leads automatically after subscription or import.</p>
            @if($emailList->canPerformAction('edit_contact'))
                <a href="{{ route('admin.sequences.create') }}" class="btn btn-primary rounded-md px-6 py-3 text-xs font-black uppercase tracking-widest">
                    Create Drip Sequence
                </a>
            @endif
        </div>
    @else
        <div class="glass-card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sequence Name</th>
                        <th class="text-center">Drip Steps</th>
                        <th class="text-center">Active Enrolled</th>
                        <th>Created Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sequences as $seq)
                        <tr class="group hover:bg-surface-50/50 transition-colors">
                            <td class="font-bold text-surface-900">
                                <a href="{{ route('admin.sequences.show', $seq) }}" class="hover:text-brand transition-colors">
                                    {{ $seq->name }}
                                </a>
                            </td>
                            <td class="text-center font-bold text-surface-900">
                                <span class="badge badge-brand">{{ $seq->steps_count }} Steps</span>
                            </td>
                            <td class="text-center font-bold text-indigo-600">
                                <span class="badge bg-indigo-50 text-indigo-600">{{ $seq->active_enrollments_count }} Enrolled</span>
                            </td>
                            <td class="text-surface-500 text-xs font-semibold">
                                {{ $seq->created_at->format('M d, Y') }}
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('admin.sequences.show', $seq) }}" class="btn btn-sm btn-ghost">Configure Steps</a>
                                    <a href="{{ route('admin.sequences.edit', $seq) }}" class="btn btn-sm btn-ghost">Edit</a>
                                    <form action="{{ route('admin.sequences.destroy', $seq) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this sequence? All step records and active enrollments will be deleted permanently.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost text-red-600">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $sequences->links() }}
        </div>
    @endif
</div>
@endsection
