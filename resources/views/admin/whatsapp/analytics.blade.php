@extends('layouts.app')

@section('title', 'WhatsApp Engagement Analytics')

@section('content')
<div class="space-y-6">

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $stats = [
                ['label' => 'Messages Sent', 'value' => number_format($stats['sent'] ?? 0), 'icon' => 'fa-paper-plane', 'color' => 'text-brand bg-brand/10'],
                ['label' => 'Delivered', 'value' => number_format($stats['delivered'] ?? 0), 'icon' => 'fa-check-double', 'color' => 'text-blue-600 bg-blue-50'],
                ['label' => 'Read', 'value' => number_format($stats['read'] ?? 0), 'icon' => 'fa-eye', 'color' => 'text-emerald-600 bg-emerald-50'],
                ['label' => 'Failed', 'value' => number_format($stats['failed'] ?? 0), 'icon' => 'fa-circle-xmark', 'color' => 'text-red-500 bg-red-50'],
                ['label' => 'Inbound Messages', 'value' => number_format($stats['inbound'] ?? 0), 'icon' => 'fa-inbox', 'color' => 'text-purple-600 bg-purple-50'],
                ['label' => 'Active Conversations', 'value' => number_format($stats['conversations'] ?? 0), 'icon' => 'fa-comments', 'color' => 'text-amber-600 bg-amber-50'],
                ['label' => 'Templates Used', 'value' => number_format($stats['templates_used'] ?? 0), 'icon' => 'fa-rectangle-list', 'color' => 'text-surface-600 bg-surface-100'],
                ['label' => 'Campaigns Run', 'value' => number_format($stats['campaigns'] ?? 0), 'icon' => 'fa-bullhorn', 'color' => 'text-pink-600 bg-pink-50'],
            ];
        @endphp

        @foreach($stats as $stat)
        <div class="bg-white border border-color rounded-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-sm flex items-center justify-center {{ $stat['color'] }}">
                    <i class="fa-solid {{ $stat['icon'] }} text-sm"></i>
                </div>
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">{{ $stat['label'] }}</p>
            </div>
            <p class="text-2xl font-black text-surface-900">{{ $stat['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Delivery Rate Card --}}
    @php
        $sent = $stats[0]['value'] ?? 1;
        $deliveryRate = $stats['sent'] > 0 ? round(($stats['delivered'] / $stats['sent']) * 100) : 0;
        $readRate     = $stats['delivered'] > 0 ? round(($stats['read'] / $stats['delivered']) * 100) : 0;
    @endphp
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white border border-color rounded-sm p-6">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-4">Delivery Rate</h2>
            <div class="flex items-end gap-4">
                <p class="text-5xl font-black text-brand">{{ $deliveryRate }}%</p>
                <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mb-2">of sent messages delivered</p>
            </div>
            <div class="mt-4 bg-surface-100 rounded-full h-2">
                <div class="bg-brand h-2 rounded-full" style="width: {{ $deliveryRate }}%"></div>
            </div>
        </div>
        <div class="bg-white border border-color rounded-sm p-6">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-4">Read Rate</h2>
            <div class="flex items-end gap-4">
                <p class="text-5xl font-black text-emerald-600">{{ $readRate }}%</p>
                <p class="text-[10px] text-surface-400 font-bold uppercase tracking-widest mb-2">of delivered messages read</p>
            </div>
            <div class="mt-4 bg-surface-100 rounded-full h-2">
                <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $readRate }}%"></div>
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white border border-color rounded-sm overflow-hidden">
        <div class="border-b border-color px-6 py-4 bg-surface-50/50">
            <h2 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Recent Conversations</h2>
        </div>
        @forelse($recentConversations as $conv)
        <a href="{{ route('admin.whatsapp.conversations.show', $conv) }}" class="flex items-center justify-between px-6 py-4 border-b border-color last:border-0 hover:bg-surface-50/50 transition-colors">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-sm bg-surface-100 flex items-center justify-center font-black text-xs text-surface-600">
                    {{ strtoupper(substr($conv->contact->name ?? 'W', 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-black text-surface-900">{{ $conv->contact->name ?? $conv->contact->whatsapp_number }}</p>
                    <p class="text-[10px] text-surface-400 font-bold">{{ Str::limit($conv->last_message_preview, 50) }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">{{ $conv->last_message_at?->diffForHumans() }}</p>
                @if($conv->unread_count > 0)
                <span class="inline-block mt-1 bg-brand text-white text-[8px] font-black px-1.5 py-0.5 rounded-full">{{ $conv->unread_count }}</span>
                @endif
            </div>
        </a>
        @empty
        <div class="py-16 text-center">
            <i class="fa-brands fa-whatsapp text-3xl text-surface-200 mb-3 block"></i>
            <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">No conversations yet</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
