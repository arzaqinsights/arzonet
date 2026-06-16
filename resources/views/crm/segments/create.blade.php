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
        <div class="glass-card p-8 mb-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-brand/5 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>
            
            <div class="flex items-center justify-between mb-8 relative z-10">
                <div>
                    <h2 class="text-xl font-black text-surface-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Targeting Rules
                    </h2>
                    <p class="text-sm text-surface-500 mt-1">Contacts matching <span class="font-bold text-brand bg-brand/10 px-1 rounded">ALL</span> rules will be included in this segment.</p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Live Count Badge --}}
                    <div class="flex items-center gap-3 bg-white px-5 py-3 rounded-xl border border-brand/20 shadow-sm shadow-brand/5">
                        <div class="w-2.5 h-2.5 rounded-full animate-pulse shadow-[0_0_8px_rgba(var(--color-brand-rgb),0.6)]" :class="isLoadingCount ? 'bg-amber-400' : 'bg-brand'"></div>
                        <div class="flex flex-col text-right">
                            <span class="text-lg leading-none font-black text-brand" x-text="isLoadingCount ? '...' : matchingCount.toLocaleString()"></span>
                            <span class="text-[9px] leading-tight font-black text-brand/60 uppercase tracking-widest mt-0.5">Matching Contacts</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4 relative z-10">
                <template x-for="(rule, index) in rules" :key="index">
                    <div class="flex items-center gap-3 p-5 bg-white border border-surface-200 rounded-xl group hover:border-brand/40 hover:shadow-md hover:shadow-brand/5 transition-all relative overflow-hidden"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-x-4"
                         x-transition:enter-end="opacity-100 transform translate-x-0">
                        
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-surface-200 group-hover:bg-brand transition-colors"></div>

                        {{-- Field --}}
                        <div class="flex-1 relative">
                            <label class="absolute -top-2 left-2 px-1 bg-white text-[9px] font-black uppercase tracking-widest text-surface-400">Field</label>
                            <select x-model="rule.field" @change="handleFieldChange(rule)" class="form-select text-sm font-semibold !py-3 bg-surface-50 focus:bg-white border-surface-200 focus:border-brand">
                                <option value="">Select Target Attribute</option>
                                <optgroup label="Standard Fields">
                                    <option value="name">Name</option>
                                    <option value="email">Email</option>
                                    <option value="status">Status</option>
                                    <option value="subscription_status">Subscription Status</option>
                                    <option value="engagement_score">Engagement Score</option>
                                    <option value="tag">Tag</option>
                                    <option value="topic">Subscription Topic</option>
                                    <option value="whatsapp_number">WhatsApp Number</option>
                                    <option value="whatsapp_subscription_status">WhatsApp Subscription Status</option>
                                    <option value="email_score">Email Score</option>
                                    <option value="signup_source">Signup Source</option>
                                    <option value="validation_reason">Validation Reason</option>
                                    @foreach($customFields as $cf)
                                        <option value="{{ $cf->key }}">{{ $cf->name }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Activity Metrics">
                                    <option value="last_engaged_at">Last Engaged (Opens/Clicks)</option>
                                    <option value="last_active_at">Last Active (Clicks)</option>
                                    <option value="last_sent_at">Last Email Sent Time</option>
                                    <option value="email_lead_score">Email Lead Score</option>
                                    <option value="whatsapp_lead_score">WhatsApp Lead Score</option>
                                    <option value="last_campaign_status">Last Campaign Status</option>
                                    <option value="last_bounce_type">Last Bounce Type</option>
                                    <option value="opened_email">Opened Any Email</option>
                                    <option value="clicked_email">Clicked Any Link</option>
                                    <option value="sent_in_last_campaign">Sent in Last Campaign</option>
                                </optgroup>
                            </select>
                        </div>

                        {{-- Operator --}}
                        <div class="w-48 relative">
                            <label class="absolute -top-2 left-2 px-1 bg-white text-[9px] font-black uppercase tracking-widest text-surface-400">Condition</label>
                            <select x-model="rule.operator" @change="updateCount()" class="form-select text-sm font-bold !py-3 bg-surface-50 focus:bg-white border-surface-200 focus:border-brand text-brand" :disabled="!rule.field">
                                <option value="">Select Condition</option>
                                <template x-for="op in getOperators(rule.field)" :key="op.value">
                                    <option :value="op.value" x-text="op.label"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Value --}}
                        <div class="flex-[1.5] relative">
                            <label class="absolute -top-2 left-2 px-1 bg-white text-[9px] font-black uppercase tracking-widest text-surface-400" x-show="needsValue(rule.operator)">Value</label>
                            
                            <div x-show="rule.operator === 'date_range'" class="flex gap-2 w-full">
                                <input type="date" x-model="rule.value_start" @change="rule.value = (rule.value_start || '') + ',' + (rule.value_end || ''); updateCount()" class="form-input text-sm font-semibold !py-3 bg-surface-50 focus:bg-white border-surface-200 focus:border-brand">
                                <span class="self-center text-xs text-gray-400">to</span>
                                <input type="date" x-model="rule.value_end" @change="rule.value = (rule.value_start || '') + ',' + (rule.value_end || ''); updateCount()" class="form-input text-sm font-semibold !py-3 bg-surface-50 focus:bg-white border-surface-200 focus:border-brand">
                            </div>

                            <input x-show="['before_date', 'after_date'].includes(rule.operator)" type="date" x-model="rule.value" @change="updateCount()" class="form-input text-sm font-semibold !py-3 bg-surface-50 focus:bg-white border-surface-200 focus:border-brand">

                            <select x-show="['opened_email', 'clicked_email', 'sent_in_last_campaign'].includes(rule.field) && needsValue(rule.operator)" x-model="rule.value" @change="updateCount()" class="form-select text-sm font-semibold !py-3 bg-surface-50 focus:bg-white border-surface-200 focus:border-brand">
                                <option value="">Select Option</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>

                            <input x-show="needsValue(rule.operator) && !['date_range', 'before_date', 'after_date'].includes(rule.operator) && !['opened_email', 'clicked_email', 'sent_in_last_campaign'].includes(rule.field)" type="text" x-model="rule.value" @input.debounce.400ms="updateCount()" class="form-input text-sm font-semibold !py-3 bg-surface-50 focus:bg-white border-surface-200 focus:border-brand" placeholder="Enter matching value...">
                            
                            <div x-show="!needsValue(rule.operator)" class="h-[46px] flex items-center px-4 bg-surface-50 border border-surface-100 rounded-md">
                                <span class="text-xs font-bold text-surface-400 italic">No value required for this condition</span>
                            </div>
                        </div>

                        {{-- Remove --}}
                        <button type="button" @click="removeRule(index)" class="w-10 h-10 flex items-center justify-center rounded-md text-surface-300 hover:bg-red-50 hover:text-red-600 border border-transparent hover:border-red-100 transition-all cursor-pointer shrink-0" title="Remove Rule">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>

                {{-- Add Rule Button --}}
                <div class="pt-2">
                    <button type="button" @click="addRule()"
                        class="w-full py-4 bg-surface-50 border-2 border-dashed border-surface-200 rounded-xl text-surface-500 hover:bg-brand/5 hover:border-brand/40 hover:text-brand transition-all cursor-pointer flex items-center justify-center gap-2 text-sm font-black uppercase tracking-wider">
                        <div class="w-6 h-6 rounded-full bg-white shadow-sm flex items-center justify-center text-current">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        Add Another Rule
                    </button>
                </div>
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
        rules: [{ field: '', operator: '', value: '', value_start: '', value_end: '' }],
        matchingCount: 0,
        isLoadingCount: false,
        isSaving: false,
 
        getOperators(field) {
            const dateFields = ['last_engaged_at', 'last_active_at', 'last_sent_at'];
            if (dateFields.includes(field)) {
                return [
                    { value: 'recent_days', label: 'In the last X days' },
                    { value: 'date_range', label: 'Between dates' },
                    { value: 'before_date', label: 'Before date' },
                    { value: 'after_date', label: 'After date' }
                ];
            }
            return [
                { value: 'equals', label: 'Equals' },
                { value: 'not_equals', label: 'Not Equals' },
                { value: 'contains', label: 'Contains' },
                { value: 'greater_than', label: 'Greater Than' },
                { value: 'less_than', label: 'Less Than' },
                { value: 'is_empty', label: 'Is Empty' },
                { value: 'is_not_empty', label: 'Is Not Empty' }
            ];
        },

        handleFieldChange(rule) {
            rule.operator = '';
            rule.value = '';
            rule.value_start = '';
            rule.value_end = '';
            this.updateCount();
        },

        needsValue(operator) {
            return !['is_empty', 'is_not_empty'].includes(operator);
        },

        addRule() {
            this.rules.push({ field: '', operator: '', value: '', value_start: '', value_end: '' });
        },

        removeRule(index) {
            this.rules.splice(index, 1);
            this.updateCount();
        },

        updateCount() {
            const validRules = this.rules.filter(r => r.field && r.operator && (!this.needsValue(r.operator) || r.value !== ''));
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
            const validRules = this.rules.filter(r => r.field && r.operator && (!this.needsValue(r.operator) || r.value !== ''));
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
