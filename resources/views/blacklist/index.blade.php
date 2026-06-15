@extends('layouts.app')
@section('title', 'Blacklist')
@section('heading', 'Email Blacklist')

@section('content')
<div class="space-y-6 animate-fade-in" x-data="{ showBulk: false }">

    {{-- ── Add Form Card ── --}}
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-6 border-b border-surface-100 pb-4">
            <div>
                <h3 class="text-lg font-black text-surface-900" style="font-family:'Outfit',sans-serif;">Add to Blacklist</h3>
                <p class="text-xs text-surface-500 font-semibold mt-1">Prevent specific emails from receiving campaigns or registering on forms.</p>
            </div>
            <button @click="showBulk = !showBulk" class="btn btn-secondary btn-sm flex items-center gap-1.5 font-bold shadow-sm">
                <i class="fa-solid" :class="showBulk ? 'fa-user-plus' : 'fa-users-line'"></i>
                <span x-text="showBulk ? 'Switch to Single Add' : 'Switch to Bulk Add'"></span>
            </button>
        </div>

        {{-- Single Add Form --}}
        <form x-show="!showBulk" action="{{ route('admin.blacklist.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            @csrf
            <div class="md:col-span-5">
                <label class="form-label font-bold text-xs uppercase tracking-widest text-surface-500">Email Address</label>
                <input type="email" name="email" class="form-input" placeholder="spam@example.com" required>
            </div>
            <div class="md:col-span-5">
                <label class="form-label font-bold text-xs uppercase tracking-widest text-surface-500">Reason (optional)</label>
                <input type="text" name="reason" class="form-input" placeholder="e.g., Spam complaints, bounced, invalid">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="btn btn-danger w-full flex items-center justify-center gap-2">
                    <i class="fa-solid fa-ban"></i>
                    Block
                </button>
            </div>
        </form>

        {{-- Bulk Add Form --}}
        <form x-show="showBulk" x-cloak action="{{ route('admin.blacklist.bulk-store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="form-label font-bold text-xs uppercase tracking-widest text-surface-500">Email Addresses</label>
                <textarea name="emails" class="form-input h-36 font-mono" placeholder="spam1@example.com, spam2@example.com&#10;spam3@example.com spam4@example.com" required></textarea>
                <span class="text-[11px] text-surface-500 mt-1.5 block leading-normal">
                    <i class="fa-solid fa-circle-info text-brand mr-1"></i>
                    You can separate email addresses using <strong>commas (`,`)</strong>, <strong>spaces</strong>, or <strong>new lines</strong>.
                </span>
            </div>
            <div>
                <label class="form-label font-bold text-xs uppercase tracking-widest text-surface-500">Reason for Blocking (optional)</label>
                <input type="text" name="reason" class="form-input" placeholder="e.g., Bulk import blacklist">
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="btn btn-danger flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-ban"></i>
                    Block All Emails
                </button>
            </div>
        </form>
    </div>

    {{-- ── Blacklist Table Card ── --}}
    <div class="glass-card overflow-hidden">
        <div class="px-6 py-4 border-b border-surface-100 bg-surface-50 flex items-center justify-between">
            <h3 class="text-xs font-black text-surface-900 uppercase tracking-widest">Blocked Directory</h3>
            <span class="badge badge-brand">{{ $blacklist->total() }} Blocked Emails</span>
        </div>

        @if($blacklist->count())
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="font-bold text-xs text-surface-500">Email Address</th>
                        <th class="font-bold text-xs text-surface-500">Reason</th>
                        <th class="font-bold text-xs text-surface-500">Blocked At</th>
                        <th class="text-right font-bold text-xs text-surface-500">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($blacklist as $entry)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-sm bg-red-50 text-red-600 flex items-center justify-center shadow-sm">
                                    <i class="fa-solid fa-envelope-open text-xs"></i>
                                </div>
                                <span class="text-surface-900 font-bold font-sans">{{ $entry->email }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="text-surface-600 font-semibold text-xs bg-surface-100/80 px-2 py-1 rounded-sm border border-surface-200/50">
                                {{ $entry->reason ?? 'No reason provided' }}
                            </span>
                        </td>
                        <td>
                            <span class="text-xs text-surface-500 font-semibold">{{ $entry->created_at->diffForHumans() }}</span>
                        </td>
                        <td class="text-right">
                            <form action="{{ route('admin.blacklist.destroy', $entry) }}" method="POST" onsubmit="return confirm('Are you sure you want to unblock this email address?')" class="inline-block">
                                @csrf 
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 btn-sm font-bold flex items-center gap-1.5 ml-auto">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Unblock
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($blacklist->hasPages())
        <div class="px-6 py-4 border-t border-surface-100">
            {{ $blacklist->links() }}
        </div>
        @endif
        @else
        <div class="p-16 text-center bg-white flex flex-col items-center justify-center space-y-3">
            <div class="w-16 h-16 rounded-full bg-surface-50 flex items-center justify-center text-surface-400">
                <i class="fa-solid fa-ban fa-2x"></i>
            </div>
            <div class="space-y-1">
                <p class="font-bold text-surface-900 text-lg">No Blocked Emails</p>
                <p class="text-sm text-surface-500 max-w-sm">Emails added here are globally restricted across all your subscriber lists and workspaces, preventing any campaign dispatch or signup events.</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
