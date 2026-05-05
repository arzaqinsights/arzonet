@extends('layouts.app')
@section('title', 'Blacklist')
@section('heading', 'Email Blacklist')

@section('content')
<div class="space-y-6 animate-fade-in" x-data="{ showBulk: false }">

    {{-- ── Add Form ── --}}
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Add to Blacklist</h3>
            <button @click="showBulk = !showBulk" class="btn-ghost btn-sm">
                <span x-text="showBulk ? 'Single Add' : 'Bulk Add'"></span>
            </button>
        </div>

        {{-- Single Add --}}
        <form x-show="!showBulk" action="{{ route('blacklist.store') }}" method="POST" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="spam@example.com" required>
            </div>
            <div class="flex-1">
                <label class="form-label">Reason (optional)</label>
                <input type="text" name="reason" class="form-input" placeholder="e.g. Spam complaint">
            </div>
            <button type="submit" class="btn-danger">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Block
            </button>
        </form>

        {{-- Bulk Add --}}
        <form x-show="showBulk" x-cloak action="{{ route('blacklist.bulk-store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Email Addresses (one per line)</label>
                <textarea name="emails" class="form-input h-32" placeholder="spam1@example.com&#10;spam2@example.com&#10;spam3@example.com" required></textarea>
            </div>
            <div>
                <label class="form-label">Reason (optional)</label>
                <input type="text" name="reason" class="form-input" placeholder="e.g. Imported blacklist">
            </div>
            <button type="submit" class="btn-danger">Block All</button>
        </form>
    </div>

    {{-- ── Blacklist Table ── --}}
    <div class="glass-card overflow-hidden">
        <div class="p-4 border-b border-surface-700/50">
            <p class="text-sm text-surface-400">{{ $blacklist->total() }} blocked emails</p>
        </div>
        @if($blacklist->count())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Reason</th>
                    <th>Blocked At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($blacklist as $entry)
                <tr>
                    <td class="text-white font-medium">{{ $entry->email }}</td>
                    <td class="text-surface-400">{{ $entry->reason ?? '—' }}</td>
                    <td class="text-xs text-surface-400">{{ $entry->created_at->diffForHumans() }}</td>
                    <td>
                        <form action="{{ route('blacklist.destroy', $entry) }}" method="POST" onsubmit="return confirm('Unblock this email?')">
                            @csrf @method('DELETE')
                            <button class="btn-ghost btn-sm text-emerald-400 hover:text-emerald-300">Unblock</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4 border-t border-surface-700/50">
            {{ $blacklist->links() }}
        </div>
        @else
        <div class="p-12 text-center">
            <p class="text-surface-400">No blocked emails</p>
        </div>
        @endif
    </div>
</div>
@endsection
