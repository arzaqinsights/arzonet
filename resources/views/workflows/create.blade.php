@extends('layouts.app')
@section('title', 'Create Workflow')
@section('heading', 'Create Visual Workflow')

@section('content')
<div class="max-w-4xl mx-auto space-y-8 animate-slide-up pb-12">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.workflows.index') }}" class="inline-flex items-center text-xs font-black uppercase tracking-widest text-surface-400 hover:text-surface-700 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Workflows
        </a>
    </div>

    <form action="{{ route('admin.workflows.store') }}" method="POST" id="workflow-form" class="space-y-8">
        @csrf
        
        {{-- Metadata card --}}
        <div class="glass-card rounded-md">
            <div class="p-8 space-y-6">
                <div>
                    <h3 class="text-xl font-bold text-surface-900 tracking-tight">Workflow Settings</h3>
                    <p class="text-sm text-surface-500 mt-1">Configure your automation triggers and default properties.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Workflow Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-input rounded-md bg-surface-50 border-surface-200 py-3 text-sm font-semibold w-full focus:border-brand" placeholder="e.g. Welcome Journey for New Subscribers" required>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-surface-400 uppercase tracking-widest">Description</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-input rounded-md bg-surface-50 border-surface-200 py-3 text-sm font-semibold w-full focus:border-brand" placeholder="e.g. Sends welcome emails and tags highly engaged users.">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-brand border-surface-300 rounded focus:ring-brand">
                    <label for="is_active" class="text-sm font-bold text-surface-750">Activate this workflow immediately</label>
                </div>
            </div>
        </div>

        {{-- Visual Builder Block --}}
        <div class="space-y-6" x-data="workflowBuilder()">
            <div class="text-center">
                <h4 class="text-surface-900 font-extrabold text-[11px] uppercase tracking-[0.2em]">Journey Visual Builder</h4>
                <p class="text-xs text-surface-400 mt-1">Visualise subscriber progression path from trigger to finish.</p>
            </div>

            {{-- Hidden Input for backend submission --}}
            <input type="hidden" name="nodes" :value="JSON.stringify(nodes)">

            {{-- Trigger Node --}}
            <div class="flex flex-col items-center">
                <div class="w-full max-w-lg bg-white border-2 border-brand/20 rounded-xl shadow-sm p-6 relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 px-4 py-1 bg-brand text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-sm">
                        Trigger
                    </div>
                    <div class="space-y-4 mt-2">
                        <div class="space-y-2">
                            <label class="text-xs font-black text-surface-500 uppercase tracking-widest">Choose Trigger Event</label>
                            <select name="trigger_type" x-model="triggerType" class="form-input rounded-md bg-surface-50 border-surface-200 py-2.5 text-sm font-semibold w-full focus:border-brand focus:ring-0" required>
                                <option value="list_signup">New List Signup / Form Submission</option>
                                <option value="topic_subscribe">Subscribed to Topic</option>
                                <option value="tag_added">Tag Added to Contact</option>
                            </select>
                        </div>

                        {{-- Trigger values options --}}
                        <div x-show="triggerType === 'topic_subscribe'" class="space-y-2" style="display: none;">
                            <label class="text-xs font-black text-surface-500 uppercase tracking-widest">Select Topic</label>
                            <select x-model="triggerValue" class="form-input rounded-md bg-surface-50 border-surface-200 py-2 text-sm font-semibold w-full focus:border-brand focus:ring-0">
                                @foreach($topics as $topic)
                                    <option value="{{ $topic->name }}">{{ $topic->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="triggerType === 'tag_added'" class="space-y-2" style="display: none;">
                            <label class="text-xs font-black text-surface-500 uppercase tracking-widest">Type Tag Name</label>
                            <input type="text" x-model="triggerValue" class="form-input rounded-md bg-surface-50 border-surface-200 py-2.5 text-sm font-semibold w-full focus:border-brand focus:ring-0" placeholder="e.g. VIP">
                        </div>
                    </div>
                </div>

                {{-- Trigger to First Node Connection --}}
                <div class="w-0.5 h-12 bg-brand/30 my-1 relative">
                    <div class="absolute bottom-0 -left-1.5 transform translate-y-1/2">
                        <svg class="w-4.5 h-4.5 text-brand/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            {{-- Dynamic Nodes Container --}}
            <div id="nodes-container" class="flex flex-col items-center w-full">
                <template x-if="nodes['start']">
                    <div class="w-full flex justify-center" x-html="renderNodeHtml('start')"></div>
                </template>
                <div class="pt-2 pb-8" x-show="!nodes['start']">
                    <button type="button" @click="openAddNodeModal(null, 'start')" class="w-10 h-10 rounded-full bg-white border-2 border-dashed border-surface-300 text-surface-400 flex items-center justify-center hover:bg-brand hover:text-white hover:border-brand transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>

            {{-- End Journey Node --}}
            <div class="flex flex-col items-center pt-8">
                <div class="px-8 py-2.5 bg-surface-100 text-surface-500 text-xs font-bold uppercase tracking-widest rounded-full border border-surface-200/80 shadow-inner">
                    End Journey
                </div>
            </div>

            {{-- Add Node Modal --}}
            <div x-show="showAddModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80" style="display: none;" x-cloak>
                <div class="bg-white rounded-lg w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col" @click.away="showAddModal = false">
                    <div class="p-4 border-b border-surface-100 bg-surface-50 flex justify-between items-center">
                        <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest">Add New Action</h3>
                        <button type="button" @click="showAddModal = false" class="text-surface-400 hover:text-surface-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[70vh]">
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Basic Actions -->
                            <div class="col-span-2">
                                <h4 class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-3 border-b border-surface-100 pb-2">Flow Control</h4>
                            </div>
                            <button type="button" @click="addNode('wait')" class="text-left p-4 rounded border border-surface-200 hover:border-amber-500 hover:ring-1 hover:ring-amber-500 transition-all flex gap-3 items-start group">
                                <div class="p-2 rounded bg-amber-50 text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                <div><h5 class="font-bold text-surface-900 text-sm">Wait Delay</h5><p class="text-[10px] text-surface-500 mt-1">Pause the journey for a set time.</p></div>
                            </button>
                            <button type="button" @click="addNode('if_else')" class="text-left p-4 rounded border border-surface-200 hover:border-purple-500 hover:ring-1 hover:ring-purple-500 transition-all flex gap-3 items-start group">
                                <div class="p-2 rounded bg-purple-50 text-purple-500 group-hover:bg-purple-500 group-hover:text-white transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg></div>
                                <div><h5 class="font-bold text-surface-900 text-sm">If / Else Branch</h5><p class="text-[10px] text-surface-500 mt-1">Split journey based on conditions.</p></div>
                            </button>

                            <!-- Communication -->
                            <div class="col-span-2 mt-4">
                                <h4 class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-3 border-b border-surface-100 pb-2">Communication</h4>
                            </div>
                            <button type="button" @click="addNode('send_email')" class="text-left p-4 rounded border border-surface-200 hover:border-blue-500 hover:ring-1 hover:ring-blue-500 transition-all flex gap-3 items-start group">
                                <div class="p-2 rounded bg-blue-50 text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                                <div><h5 class="font-bold text-surface-900 text-sm">Send Email</h5><p class="text-[10px] text-surface-500 mt-1">Send a pre-designed template.</p></div>
                            </button>

                            <!-- CRM Actions -->
                            <div class="col-span-2 mt-4">
                                <h4 class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-3 border-b border-surface-100 pb-2">CRM Actions</h4>
                            </div>
                            <button type="button" @click="addNode('add_tag')" class="text-left p-4 rounded border border-surface-200 hover:border-emerald-500 hover:ring-1 hover:ring-emerald-500 transition-all flex gap-3 items-start group">
                                <div class="p-2 rounded bg-emerald-50 text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                                <div><h5 class="font-bold text-surface-900 text-sm">Add Tag</h5><p class="text-[10px] text-surface-500 mt-1">Add a tag to the contact.</p></div>
                            </button>
                            <button type="button" @click="addNode('remove_tag')" class="text-left p-4 rounded border border-surface-200 hover:border-emerald-500 hover:ring-1 hover:ring-emerald-500 transition-all flex gap-3 items-start group">
                                <div class="p-2 rounded bg-emerald-50 text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
                                <div><h5 class="font-bold text-surface-900 text-sm">Remove Tag</h5><p class="text-[10px] text-surface-500 mt-1">Remove a tag from the contact.</p></div>
                            </button>
                            <button type="button" @click="addNode('add_note')" class="text-left p-4 rounded border border-surface-200 hover:border-gray-500 hover:ring-1 hover:ring-gray-500 transition-all flex gap-3 items-start group">
                                <div class="p-2 rounded bg-gray-50 text-gray-500 group-hover:bg-gray-500 group-hover:text-white transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></div>
                                <div><h5 class="font-bold text-surface-900 text-sm">Add Note</h5><p class="text-[10px] text-surface-500 mt-1">Append a note to the profile.</p></div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Edit Node Modal --}}
            <div x-show="showEditModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/80" style="display: none;" x-cloak>
                <div class="bg-white rounded-lg w-full max-w-md overflow-hidden shadow-2xl flex flex-col" @click.away="showEditModal = false">
                    <div class="p-4 border-b border-surface-100 bg-surface-50 flex justify-between items-center">
                        <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest">Configure Action</h3>
                        <button type="button" @click="showEditModal = false" class="text-surface-400 hover:text-surface-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-4" x-if="editingNodeData">
                        
                        <!-- Wait Details -->
                        <template x-if="editingNodeData.type === 'wait'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest mb-1.5">Delay Duration</label>
                                    <div class="flex gap-2">
                                        <input type="number" x-model="editingNodeData.details.delay" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold w-24 focus:border-brand" min="1">
                                        <select x-model="editingNodeData.details.unit" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold flex-1 focus:border-brand">
                                            <option value="minutes">Minutes</option>
                                            <option value="hours">Hours</option>
                                            <option value="days">Days</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Send Email Details -->
                        <template x-if="editingNodeData.type === 'send_email'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest mb-1.5">Select Template</label>
                                    <select x-model="editingNodeData.details.template_id" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold w-full focus:border-brand">
                                        <option value="">-- Choose Template --</option>
                                        @foreach($templates as $tmpl)
                                            <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest mb-1.5">Custom Subject Line (Optional)</label>
                                    <input type="text" x-model="editingNodeData.details.subject" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold w-full focus:border-brand" placeholder="Leave blank to use template subject">
                                </div>
                            </div>
                        </template>

                        <!-- If/Else Details -->
                        <template x-if="editingNodeData.type === 'if_else'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest mb-1.5">Condition Type</label>
                                    <select x-model="editingNodeData.details.condition_type" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold w-full focus:border-brand">
                                        <option value="has_tag">Contact has Tag</option>
                                        <option value="does_not_have_tag">Contact does NOT have Tag</option>
                                        <option value="has_topic">Contact subscribed to Topic</option>
                                    </select>
                                </div>
                                
                                <div x-show="editingNodeData.details.condition_type === 'has_tag' || editingNodeData.details.condition_type === 'does_not_have_tag'">
                                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest mb-1.5">Tag Name</label>
                                    <input type="text" x-model="editingNodeData.details.value" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold w-full focus:border-brand" placeholder="e.g. VIP">
                                </div>

                                <div x-show="editingNodeData.details.condition_type === 'has_topic'">
                                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest mb-1.5">Topic Name</label>
                                    <select x-model="editingNodeData.details.value" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold w-full focus:border-brand">
                                        @foreach($topics as $topic)
                                            <option value="{{ $topic->name }}">{{ $topic->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>

                        <!-- Tag Details -->
                        <template x-if="editingNodeData.type === 'add_tag' || editingNodeData.type === 'remove_tag'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest mb-1.5">Tag Name</label>
                                    <input type="text" x-model="editingNodeData.details.tag" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold w-full focus:border-brand" placeholder="e.g. Follow Up">
                                </div>
                            </div>
                        </template>

                        <!-- Add Note Details -->
                        <template x-if="editingNodeData.type === 'add_note'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-black text-surface-500 uppercase tracking-widest mb-1.5">Note Content</label>
                                    <textarea x-model="editingNodeData.details.note" rows="3" class="form-input rounded-md border-surface-200 py-2 text-sm font-semibold w-full focus:border-brand" placeholder="Type the note to add to the contact's profile..."></textarea>
                                </div>
                            </div>
                        </template>

                        <div class="pt-4 border-t border-surface-100 flex justify-end">
                            <button type="button" @click="saveNodeEdit()" class="btn btn-primary rounded-md px-6 py-2 text-xs font-black uppercase tracking-widest">Save Config</button>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Secret Form field for trigger value parsing --}}
            <input type="hidden" name="trigger_value" :value="triggerValue">
        </div>

        {{-- Submit Panel --}}
        <div class="pt-8 flex justify-end border-t border-surface-200/85">
            <button type="submit" class="btn btn-primary rounded-md px-16 py-4 shadow-xl shadow-primary-200 text-sm font-black uppercase tracking-widest" @click="return document.getElementById('workflow-form').checkValidity()">
                Save Workflow
            </button>
        </div>
    </form>
</div>

{{-- Define Alpine component and recursive template renderer --}}
<script>
    function workflowBuilder() {
        return {
            triggerType: '{{ old('trigger_type', 'list_signup') }}',
            triggerValue: '{{ old('trigger_value', '') }}',
            nodes: {}, // Flat dictionary of nodes
            showAddModal: false,
            showEditModal: false,
            activeTargetParentId: null, // "node_x" or null if adding to start
            activeTargetPointer: null,  // 'next', 'true', 'false', 'start'
            activeNode: null,
            editingNodeData: null,
            
            init() {
                // For editing, we will load existing nodes. Here it's create so it's empty.
                const oldNodes = @json(old('nodes', null));
                if (oldNodes) {
                    try {
                        this.nodes = typeof oldNodes === 'string' ? JSON.parse(oldNodes) : oldNodes;
                    } catch (e) {}
                }
            },

            // Recursive renderer wrapper via fetch to partial
            renderNodeHtml(nodeId) {
                // Since Alpine x-html doesn't support recursive component loading easily on client-side,
                // we will build the HTML string recursively in JS based on the nodes dict.
                if (!nodeId || !this.nodes[nodeId]) return '';
                const node = this.nodes[nodeId];
                
                let html = `
                <div class="flex flex-col items-center w-full relative group">
                    <div class="w-0.5 h-10 bg-brand/20 relative">
                        <div class="absolute bottom-0 -left-1.5 transform translate-y-1/2">
                            <svg class="w-4.5 h-4.5 text-brand/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <div class="w-full max-w-[280px] bg-white border border-surface-200 rounded-lg p-5 shadow-sm relative transition-all hover:border-brand/50">
                        <div class="absolute -top-3 left-4 px-3 py-0.5 text-white text-[9px] font-black uppercase tracking-widest rounded-full shadow-sm \${this.getColorClass(node.type)}">
                            \${this.getNodeTypeLabel(node.type)}
                        </div>
                        
                        <div class="flex justify-between items-start pt-2 gap-2">
                            <div class="text-xs font-semibold text-surface-800 break-words leading-relaxed">
                                \${this.getNodeDescription(node)}
                            </div>
                            <div class="flex flex-col gap-1 shrink-0">
                                <button type="button" @click.stop="editNode('\${nodeId}')" class="p-1 text-surface-400 hover:text-brand hover:bg-brand/5 rounded transition-colors bg-surface-50 border border-surface-100" title="Edit Step">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button type="button" @click.stop="deleteNode('\${nodeId}')" class="p-1 text-surface-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors bg-surface-50 border border-surface-100" title="Remove Step">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                if (node.type === 'if_else') {
                    html += `
                    <div class="flex w-full items-start justify-center mt-2 relative">
                        <div class="absolute top-0 w-[calc(100%-2rem)] max-w-sm h-0.5 bg-brand/20 left-1/2 transform -translate-x-1/2"></div>
                        <div class="flex flex-col items-center flex-1 pt-4 relative px-2">
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-green-100 text-green-800 border border-green-200 px-2 py-0.5 text-[9px] font-black rounded uppercase">Yes</div>
                            \${node.next_true ? this.renderNodeHtml(node.next_true) : this.renderAddButton(nodeId, 'true')}
                        </div>
                        <div class="flex flex-col items-center flex-1 pt-4 relative px-2">
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-red-100 text-red-800 border border-red-200 px-2 py-0.5 text-[9px] font-black rounded uppercase">No</div>
                            \${node.next_false ? this.renderNodeHtml(node.next_false) : this.renderAddButton(nodeId, 'false')}
                        </div>
                    </div>
                    `;
                } else {
                    html += `
                    <div class="flex flex-col items-center w-full">
                        \${node.next ? this.renderNodeHtml(node.next) : this.renderAddButton(nodeId, 'next')}
                    </div>
                    `;
                }

                html += `</div>`;
                return html;
            },

            renderAddButton(parentId, pointer) {
                return `
                <div class="pt-4 pb-2">
                    <button type="button" @click="openAddNodeModal('\${parentId}', '\${pointer}')" class="w-8 h-8 rounded-full bg-surface-50 border border-surface-200 text-surface-400 flex items-center justify-center hover:bg-brand hover:text-white hover:border-brand transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
                `;
            },

            generateNodeId() {
                return 'node_' + Math.random().toString(36).substr(2, 9);
            },

            openAddNodeModal(parentId, pointer) {
                this.activeTargetParentId = parentId;
                this.activeTargetPointer = pointer;
                this.showAddModal = true;
            },

            addNode(type) {
                const newId = this.generateNodeId();
                let details = {};
                if (type === 'wait') details = { delay: 1, unit: 'days' };
                else if (type === 'send_email') details = { template_id: '', subject: '' };
                else if (type === 'if_else') details = { condition_type: 'has_tag', value: '' };
                else if (type === 'add_tag' || type === 'remove_tag') details = { tag: '' };
                else if (type === 'add_note') details = { note: '' };

                const newNode = {
                    type: type,
                    details: details,
                    next: null
                };
                if (type === 'if_else') {
                    newNode.next_true = null;
                    newNode.next_false = null;
                }

                this.nodes[newId] = newNode;

                if (this.activeTargetPointer === 'start') {
                    this.nodes['start'] = newNode;
                } else if (this.activeTargetParentId) {
                    const parent = this.nodes[this.activeTargetParentId];
                    if (this.activeTargetPointer === 'true') parent.next_true = newId;
                    else if (this.activeTargetPointer === 'false') parent.next_false = newId;
                    else parent.next = newId;
                }

                this.showAddModal = false;
                
                // Trigger Alpine re-render by replacing object
                this.nodes = { ...this.nodes };
                this.editNode(newId);
            },

            editNode(nodeId) {
                this.activeNode = nodeId;
                this.editingNodeData = JSON.parse(JSON.stringify(this.nodes[nodeId]));
                this.showEditModal = true;
            },

            saveNodeEdit() {
                if (this.activeNode) {
                    this.nodes[this.activeNode].details = this.editingNodeData.details;
                    this.nodes = { ...this.nodes };
                }
                this.showEditModal = false;
                this.activeNode = null;
            },

            deleteNode(nodeId) {
                if (!confirm("Are you sure you want to delete this node and all paths below it?")) return;

                // Find parent and remove reference
                if (this.nodes['start'] && this.nodes['start'] === this.nodes[nodeId]) {
                    delete this.nodes['start'];
                } else {
                    for (let key in this.nodes) {
                        if (this.nodes[key].next === nodeId) this.nodes[key].next = null;
                        if (this.nodes[key].next_true === nodeId) this.nodes[key].next_true = null;
                        if (this.nodes[key].next_false === nodeId) this.nodes[key].next_false = null;
                    }
                }

                // Recursively delete children
                const deleteChildren = (id) => {
                    const n = this.nodes[id];
                    if (!n) return;
                    if (n.next) deleteChildren(n.next);
                    if (n.next_true) deleteChildren(n.next_true);
                    if (n.next_false) deleteChildren(n.next_false);
                    delete this.nodes[id];
                };

                deleteChildren(nodeId);
                this.nodes = { ...this.nodes };
            },

            getNodeTypeLabel(type) {
                const labels = {
                    'wait': 'Wait Delay',
                    'send_email': 'Send Email',
                    'add_tag': 'Add Tag',
                    'remove_tag': 'Remove Tag',
                    'if_else': 'If / Else',
                    'add_note': 'Add Note'
                };
                return labels[type] || type;
            },

            getColorClass(type) {
                const colors = {
                    'wait': 'bg-amber-500',
                    'send_email': 'bg-blue-500',
                    'add_tag': 'bg-emerald-500',
                    'remove_tag': 'bg-emerald-500',
                    'if_else': 'bg-purple-500',
                    'add_note': 'bg-gray-700'
                };
                return colors[type] || 'bg-gray-700';
            },

            getNodeDescription(node) {
                const d = node.details;
                if (node.type === 'wait') return `Wait for <span class="text-brand font-black">${d.delay} ${d.unit}</span>`;
                if (node.type === 'send_email') return `Send Email <span class="text-brand font-black">#${d.template_id || '?'}</span>`;
                if (node.type === 'add_tag') return `Add Tag: <span class="text-brand font-black">${d.tag || '...'}</span>`;
                if (node.type === 'remove_tag') return `Remove Tag: <span class="text-brand font-black">${d.tag || '...'}</span>`;
                if (node.type === 'if_else') {
                    if (d.condition_type === 'has_tag') return `Has Tag: <span class="text-brand font-black">${d.value || '...'}</span>?`;
                    if (d.condition_type === 'does_not_have_tag') return `Lacks Tag: <span class="text-brand font-black">${d.value || '...'}</span>?`;
                    if (d.condition_type === 'has_topic') return `Has Topic: <span class="text-brand font-black">${d.value || '...'}</span>?`;
                    return "Condition?";
                }
                if (node.type === 'add_note') return `Add Note`;
                return "Config missing";
            }
        }
    }
</script>
@endsection
