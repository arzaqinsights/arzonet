@extends('layouts.app')
@section('title', 'Custom Fields')
@section('heading', 'Custom Fields Manager')

@section('content')
<div class="space-y-6 animate-slide-up" x-data="{ showCreate: false }">

    {{-- Info Banner --}}
    <div class="bg-brand/5 border border-brand/20 rounded-sm p-5 flex items-start gap-4">
        <div class="shrink-0 w-10 h-10 rounded-sm bg-brand/10 flex items-center justify-center text-brand">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-bold text-surface-900">Custom fields store values in each contact's metadata (JSON).</p>
            <p class="text-xs text-surface-500 mt-1">No database schema changes needed — they automatically appear in the contact import form and grid view.</p>
        </div>
    </div>

    {{-- Add Field Form --}}
    <div class="glass-card p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xs font-black text-surface-900 uppercase tracking-widest">Define Fields</h2>
            <button @click="showCreate = !showCreate"
                class="px-4 py-2 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">
                <svg class="w-3 h-3 mr-1.5 transition-transform" :class="showCreate ? 'rotate-45' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                <span x-text="showCreate ? 'Close' : 'Add Field'"></span>
            </button>
        </div>

        <div x-show="showCreate" x-transition x-cloak>
            <form action="{{ route('admin.custom-fields.store') }}" method="POST" class="bg-surface-50 border border-surface-100 rounded-sm p-6 mb-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="form-label">Label *</label>
                        <input type="text" name="label" class="form-input" placeholder="e.g. Company Size" required>
                        @error('label') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-select" x-data="{ type: 'text' }" x-model="type" required>
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                            <option value="select">Select (Dropdown)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Choices <span class="text-surface-400">(for Select type, comma-separated)</span></label>
                        <input type="text" name="choices" class="form-input" placeholder="e.g. Small, Medium, Large">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary w-full">Create Field</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Fields Table --}}
        @if($fields->isEmpty())
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-surface-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                <p class="text-sm font-medium text-surface-400">No custom fields defined yet.</p>
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Slug</th>
                        <th>Type</th>
                        <th>Choices</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fields as $field)
                        <tr class="group">
                            <td class="font-bold text-surface-900">{{ $field->label }}</td>
                            <td><code class="text-xs bg-surface-50 px-2 py-0.5 rounded">{{ $field->name }}</code></td>
                            <td>
                                @php
                                    $typeBadge = match($field->type) {
                                        'text'   => 'badge-neutral',
                                        'number' => 'badge-info',
                                        'date'   => 'badge-warning',
                                        'select' => 'badge-brand',
                                        default  => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $typeBadge }}">{{ ucfirst($field->type) }}</span>
                            </td>
                            <td class="text-surface-500 text-sm">
                                @if($field->choices)
                                    {{ implode(', ', $field->choices) }}
                                @else
                                    <span class="text-surface-300">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <form action="{{ route('admin.custom-fields.destroy', $field) }}" method="POST" class="inline" onsubmit="return confirm('Delete this field? Existing contact data will NOT be removed.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-ghost text-red-500 hover:text-red-700 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
