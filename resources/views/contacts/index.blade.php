@extends('layouts.app')
@section('title', 'Contacts Management')
@section('heading', 'Centralized Contacts')

@section('content')
<div class="space-y-8 animate-slide-up">
    {{-- Search & Filters --}}
    <div class="glass-card p-6">
        <form action="{{ route('contacts.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" class="form-input pl-12" placeholder="Search by name, email, or company...">
            </div>
            <select name="status" class="form-input md:w-48">
                <option value="">All Statuses</option>
                <option value="subscribed" {{ request('status') === 'subscribed' ? 'selected' : '' }}>Subscribed</option>
                <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
            </select>
            <button type="submit" class="btn btn-primary px-8">Filter</button>
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('contacts.index') }}" class="btn btn-ghost">Reset</a>
            @endif
        </form>
    </div>

    {{-- Contacts Table --}}
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Contact Name</th>
                        <th>Email Address</th>
                        <th>Status</th>
                        <th>List Name</th>
                        <th class="text-center">Activity</th>
                        <th>Last Active</th>
                        <th class="text-right">Profile</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $contact)
                    <tr class="group hover:bg-surface-50/50 transition-colors">
                        <td>
                            <div class="font-bold text-surface-900">{{ $contact->name ?? 'Unnamed Contact' }}</div>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @if($contact->tags)
                                    @foreach($contact->tags as $tag)
                                        <span class="text-[9px] font-black uppercase px-1.5 py-0.5 bg-primary-50 text-primary-600 rounded">{{ $tag }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="text-sm font-medium text-surface-600">{{ $contact->email }}</div>
                        </td>
                        <td>
                            @php
                                $statusCls = match($contact->subscription_status) {
                                    'subscribed' => 'badge-success',
                                    'unsubscribed' => 'badge-danger',
                                    default => 'badge-neutral',
                                };
                            @endphp
                            <span class="badge {{ $statusCls }}">{{ ucfirst($contact->subscription_status) }}</span>
                        </td>
                        <td>
                            <span class="text-xs font-bold text-surface-400 bg-surface-100 px-2 py-1 rounded">{{ $contact->emailList->name }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-center gap-4">
                                <div class="text-center" title="Total Opens">
                                    <div class="text-xs font-black text-emerald-600">{{ $contact->opens_count }}</div>
                                    <div class="text-[9px] font-bold text-surface-400 uppercase">Opens</div>
                                </div>
                                <div class="text-center" title="Total Clicks">
                                    <div class="text-xs font-black text-indigo-600">{{ $contact->clicks_count }}</div>
                                    <div class="text-[9px] font-bold text-surface-400 uppercase">Clicks</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-xs text-surface-500 font-medium">
                                {{ $contact->last_active_at ? $contact->last_active_at->diffForHumans() : 'No Activity' }}
                            </div>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('contacts.show', $contact->id) }}" class="btn btn-ghost btn-sm group-hover:bg-primary-50 group-hover:text-primary-600 transition-all">
                                View Profile
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t border-surface-100 bg-surface-50/30">
            {{ $contacts->links() }}
        </div>
    </div>
</div>
@endsection
