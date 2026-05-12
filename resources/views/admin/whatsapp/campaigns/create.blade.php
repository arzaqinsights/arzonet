@extends('layouts.app')

@section('title', 'Create WhatsApp Campaign')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Create WhatsApp Campaign</h1>
        <p class="mt-1 text-sm text-gray-500">Choose a template and prepare your broadcast.</p>
    </div>

    <form action="{{ route('admin.whatsapp.campaigns.store') }}" method="POST" class="space-y-6 bg-white p-8 rounded-xl border border-gray-200 shadow-sm">
        @csrf
        
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Campaign Name</label>
            <input type="text" name="name" id="name" required placeholder="e.g., Welcome Message June"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand focus:border-brand sm:text-sm">
        </div>

        <div>
            <label for="whatsapp_template_id" class="block text-sm font-medium text-gray-700">Select Template</label>
            <select name="whatsapp_template_id" id="whatsapp_template_id" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand focus:border-brand sm:text-sm">
                <option value="">Select an approved template</option>
                @foreach($templates as $template)
                <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->language }})</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-gray-400">Only "approved" templates from Meta can be used for campaigns.</p>
        </div>

        <div class="pt-4 flex justify-end space-x-3">
            <a href="{{ route('admin.whatsapp.campaigns.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Campaign</button>
        </div>
    </form>
</div>
@endsection
