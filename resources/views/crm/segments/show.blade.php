@extends('layouts.app')
@section('title', $segment->name . ' — Segment')
@section('heading', $segment->name)

@section('header-actions')
    <a href="{{ route('admin.segments.index') }}"
        class="px-4 py-3 flex items-center rounded-sm bg-white border border-gray-100 text-surface-600 hover:text-surface-900 text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">
        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        All Segments
    </a>
@endsection

@section('content')
<div class="space-y-6 animate-slide-up">
    {{-- Segment Info --}}
    <div class="glass-card p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-black text-surface-900">{{ $segment->name }}</h2>
                @if($segment->description)
                    <p class="text-sm text-surface-500 mt-1">{{ $segment->description }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-brand px-3 py-1.5">{{ $contacts->total() }} {{ Str::plural('contact', $contacts->total()) }}</span>
            </div>
        </div>

        {{-- Rules Display --}}
        <div class="mt-4 pt-4 border-t border-surface-100">
            <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-3">Active Rules</p>
            <div class="flex flex-wrap gap-2">
                @foreach($segment->rules ?? [] as $rule)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-50 border border-surface-200 rounded-sm text-xs font-medium text-surface-700">
                        <strong>{{ ucfirst(str_replace('_', ' ', $rule['field'] ?? '')) }}</strong>
                        <span class="text-surface-400">{{ str_replace('_', ' ', $rule['operator'] ?? '') }}</span>
                        <span class="text-brand font-bold">"{{ $rule['value'] ?? '' }}"</span>
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Matching Contacts Table --}}
    <div class="glass-card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th class="text-center">AI Score</th>
                    <th class="text-center">Status</th>
                    <th>List</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                    <tr>
                        <td class="font-bold text-surface-900">{{ $contact->name ?? '—' }}</td>
                        <td class="text-surface-600">{{ $contact->email }}</td>
                        <td class="text-center">
                            @php
                                $score = $contact->engagement_score ?? 0;
                                $scoreCls = $score > 80 ? 'text-red-500' : ($score >= 40 ? 'text-amber-500' : 'text-blue-500');
                            @endphp
                            <span class="font-black {{ $scoreCls }}">{{ $score }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $contact->subscription_status === 'subscribed' ? 'badge-success' : 'badge-neutral' }}">
                                {{ ucfirst($contact->subscription_status) }}
                            </span>
                        </td>
                        <td class="text-surface-500 text-sm">{{ $contact->emailList->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-surface-400">No contacts match this segment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($contacts->hasPages())
            <div class="px-6 py-4 border-t border-surface-100">
                {{ $contacts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
