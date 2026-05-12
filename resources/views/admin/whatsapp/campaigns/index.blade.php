@extends('layouts.app')

@section('title', 'WhatsApp Campaigns')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">WhatsApp Campaigns</h1>
            <p class="mt-1 text-sm text-gray-500">Manage and monitor your WhatsApp template broadcasts.</p>
        </div>
        <a href="{{ route('admin.whatsapp.campaigns.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-plus mr-2"></i> Create Campaign
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-md border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Template</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($campaigns as $campaign)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $campaign->name }}</div>
                        <div class="text-xs text-gray-500">{{ $campaign->created_at->format('M d, Y') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $campaign->template->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 uppercase">
                            {{ $campaign->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $campaign->sent_count }} / {{ $campaign->total_recipients }}</div>
                        @if($campaign->failed_count > 0)
                        <div class="text-xs text-red-500">{{ $campaign->failed_count }} failed</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        @if($campaign->status === 'draft')
                        <form action="{{ route('admin.whatsapp.campaigns.send', $campaign) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-brand hover:text-[#e05638]">Send Now</button>
                        </form>
                        @endif
                        <button class="ml-4 text-gray-400 hover:text-gray-600">Details</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">No campaigns found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
