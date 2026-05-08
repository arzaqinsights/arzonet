@extends('layouts.app')
@section('title', 'Edit Campaign')
@section('heading', 'Modify Mission')

@section('content')
<div class="max-w-3xl mx-auto animate-slide-up">
    <div class="glass-card rounded-md">
        <div class="p-8">
            <form action="{{ route('admin.campaigns.update', $campaign) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="space-y-2">
                    <label class="text-sm font-bold text-surface-700">Campaign Identity</label>
                    <input type="text" name="name" value="{{ $campaign->name }}" class="form-input rounded-md" placeholder="e.g. Q4 Growth Sequence" required>
                    <p class="text-[10px] text-surface-400 uppercase font-bold tracking-tight">Internal name for tracking and organization.</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-bold text-surface-700">Subject Line</label>
                    <input type="text" name="subject" value="{{ $campaign->subject }}" class="form-input rounded-md" placeholder="The hook for your recipients..." required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-surface-700">Target Audience</label>
                        <select name="email_list_id" class="form-select rounded-md" required>
                            @foreach($emailLists as $list)
                                <option value="{{ $list->id }}" {{ $campaign->email_list_id == $list->id ? 'selected' : '' }}>
                                    {{ $list->name }} ({{ number_format($list->valid_count) }} active)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-bold text-surface-700">Content Template</label>
                        <select name="template_id" class="form-select rounded-md" required>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ $campaign->template_id == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="p-6 bg-primary-50/30 rounded-md border border-primary-100/50 space-y-6">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-md bg-primary-100 flex items-center justify-center text-primary-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <h4 class="font-black text-surface-900 text-xs uppercase tracking-widest">Execution Strategy</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase">Sender Profile</label>
                            <select name="sender_id" class="form-select !bg-white rounded-md" required>
                                @foreach($senders as $sender)
                                    <option value="{{ $sender->id }}" {{ $campaign->sender_id == $sender->id ? 'selected' : '' }}>
                                        {{ $sender->from_name }} ({{ $sender->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-surface-500 uppercase">Velocity Limit (RPM)</label>
                            <input type="number" name="emails_per_minute" value="{{ $campaign->emails_per_minute }}" class="form-input !bg-white rounded-md" min="1">
                        </div>
                    </div>
                </div>

                <div class="pt-8 flex items-center justify-between border-t border-surface-100">
                    <a href="{{ route('admin.campaigns.index') }}" class="text-sm font-bold text-surface-400 hover:text-surface-900 transition-colors">
                        &larr; Cancel Changes
                    </a>
                    <button type="submit" class="btn btn-primary rounded-md px-12 py-3 shadow-xl shadow-primary-100">
                        Update Campaign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
