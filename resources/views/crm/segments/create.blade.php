@extends('layouts.app')
@section('title', 'Create Segment')
@section('heading', 'Create Segment')

@section('content')
<div class="max-w-3xl mx-auto animate-slide-up" x-data="segmentBuilder()">
    <form @submit.prevent="saveSegment()">
        <div class="glass-card p-8 mb-6">
            <h2 class="text-xl font-black text-surface-900 mb-6">Segment Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Segment Name *</label>
                    <input type="text" x-model="name" class="form-input" placeholder="e.g. High-Value Leads" required>
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <input type="text" x-model="description" class="form-input" placeholder="Optional description...">
                </div>
            </div>
        </div>

        {{-- Rules Builder --}}
        <div class="glass-card p-8 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-black text-surface-900">Rules</h2>
                    <p class="text-sm text-surface-500 mt-1">Contacts matching ALL rules will be included.</p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Live Count Badge --}}
                    <div class="flex items-center gap-2 bg-brand/10 px-4 py-2 rounded-sm border border-brand/20">
                        <div class="w-2 h-2 rounded-full animate-pulse" :class="isLoadingCount ? 'bg-amber-500' : 'bg-brand'"></div>
                        <span class="text-sm font-black text-brand" x-text="isLoadingCount ? '...' : matchingCount.toLocaleString()"></span>
                        <span class="text-[9px] font-black text-brand/60 uppercase tracking-widest">Contacts Match</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <template x-for="(rule, index) in rules" :key="index">
                    <div class="flex items-center gap-3 p-4 bg-surface-50 border border-surface-100 rounded-sm group">
                        {{-- Field --}}
                        <div class="flex-1">
                            <select x-model="rule.field" @change="updateCount()" class="form-select text-sm">
                                <option value="">Select Field</option>
                                <optgroup label="Standard Fields">
                                    <option value="name">Name</option>
                                    <option value="email">Email</option>
                                    <option value="engagement_score">AI Score</option>
                                </optgroup>
                                @if($customFields->isNotEmpty())
                                    <optgroup label="Custom Fields">
                                        @foreach($customFields as $cf)
                                            <option value="{{ $cf->name }}">{{ $cf->label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>

                        {{-- Operator --}}
                        <div class="w-40">
                            <select x-model="rule.operator" @change="updateCount()" class="form-select text-sm">
                                <option value="">Operator</option>
                                <option value="equals">Equals</option>
                                <option value="not_equals">Not Equals</option>
                                <option value="contains">Contains</option>
                                <option value="greater_than">Greater Than</option>
                                <option value="less_than">Less Than</option>
                            </select>
                        </div>

                        {{-- Value --}}
                        <div class="flex-1">
                            <input type="text" x-model="rule.value" @input.debounce.400ms="updateCount()" class="form-input text-sm" placeholder="Value...">
                        </div>

                        {{-- Remove --}}
                        <button type="button" @click="removeRule(index)" class="p-2 text-surface-300 hover:text-red-500 transition-colors cursor-pointer opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>

                {{-- Add Rule Button --}}
                <button type="button" @click="addRule()"
                    class="w-full p-4 border-2 border-dashed border-surface-200 rounded-sm text-surface-400 hover:border-brand hover:text-brand transition-all cursor-pointer flex items-center justify-center gap-2 text-sm font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Add Rule
                </button>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.segments.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary" :disabled="rules.length === 0 || isSaving">
                <span x-show="!isSaving">Save Segment</span>
                <span x-show="isSaving">Saving...</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function segmentBuilder() {
    return {
        name: '',
        description: '',
        rules: [{ field: '', operator: '', value: '' }],
        matchingCount: 0,
        isLoadingCount: false,
        isSaving: false,

        addRule() {
            this.rules.push({ field: '', operator: '', value: '' });
        },

        removeRule(index) {
            this.rules.splice(index, 1);
            this.updateCount();
        },

        updateCount() {
            const validRules = this.rules.filter(r => r.field && r.operator && r.value);
            if (validRules.length === 0) {
                this.matchingCount = 0;
                return;
            }

            this.isLoadingCount = true;

            fetch('{{ route("admin.segments.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rules: validRules })
            })
            .then(res => res.json())
            .then(data => {
                this.matchingCount = data.count || 0;
                this.isLoadingCount = false;
            })
            .catch(() => { this.isLoadingCount = false; });
        },

        saveSegment() {
            const validRules = this.rules.filter(r => r.field && r.operator && r.value);
            if (validRules.length === 0) {
                alert('Please add at least one complete rule.');
                return;
            }

            this.isSaving = true;

            fetch('{{ route("admin.segments.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: this.name,
                    description: this.description,
                    rules: validRules
                })
            })
            .then(res => {
                if (res.redirected) {
                    window.location.href = res.url;
                } else {
                    return res.json();
                }
            })
            .then(data => {
                if (data && data.success !== false) {
                    window.location.href = '{{ route("admin.segments.index") }}';
                }
                this.isSaving = false;
            })
            .catch(() => { this.isSaving = false; });
        }
    };
}
</script>
@endpush
@endsection
