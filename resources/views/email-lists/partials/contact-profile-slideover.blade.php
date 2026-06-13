<div x-show="showProfileSlideover" class="fixed inset-0 z-[100] flex justify-end" x-cloak>
    <!-- Backdrop -->
    <div x-show="showProfileSlideover" x-transition.opacity class="fixed inset-0 bg-surface-900/80" @click="closeProfile()"></div>

    <!-- Slide-over panel -->
    <div x-show="showProfileSlideover" 
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full" 
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-300" 
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full" 
         class="w-full max-w-xl bg-white shadow-2xl h-full flex flex-col relative z-10 border-l border-surface-200">
         
        <!-- Header -->
        <div class="p-6 border-b border-surface-100 flex items-center justify-between bg-surface-50">
            <div>
                <h2 class="text-lg font-black text-surface-900 tracking-tight" x-text="profileContact ? (profileContact.name || profileContact.email) : 'Loading...'"></h2>
                <p class="text-xs font-bold text-surface-500 tracking-widest mt-1" x-text="profileContact ? (profileContact.name ? profileContact.email : '') : ''"></p>
            </div>
            <button @click="closeProfile()" class="text-surface-400 hover:text-surface-900 transition-colors p-2 rounded-full hover:bg-surface-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <template x-if="profileLoading">
            <div class="flex-1 flex items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </template>

        <template x-if="!profileLoading && profileContact">
            <div class="flex-1 overflow-hidden flex flex-col">
                <!-- Tabs -->
                <div class="flex border-b border-surface-100 px-6 pt-4 space-x-4 flex-wrap">
                    <button @click="profileActiveTab = 'details'" :class="{'border-brand text-brand': profileActiveTab === 'details', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'details'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors focus:outline-none">Details</button>
                    <button @click="profileActiveTab = 'activity'" :class="{'border-brand text-brand': profileActiveTab === 'activity', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'activity'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors focus:outline-none">Activity</button>
                    <button @click="profileActiveTab = 'notes'" :class="{'border-brand text-brand': profileActiveTab === 'notes', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'notes'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors focus:outline-none">Notes</button>
                    <button @click="profileActiveTab = 'tasks'" :class="{'border-brand text-brand': profileActiveTab === 'tasks', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'tasks'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors focus:outline-none">Tasks</button>
                    <button @click="profileActiveTab = 'sequences'" :class="{'border-brand text-brand': profileActiveTab === 'sequences', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'sequences'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors focus:outline-none">Sequences</button>
                    <button @click="profileActiveTab = 'campaigns'" :class="{'border-brand text-brand': profileActiveTab === 'campaigns', 'border-transparent text-surface-400 hover:text-surface-700': profileActiveTab !== 'campaigns'}" class="pb-3 text-[11px] font-black uppercase tracking-widest border-b-2 transition-colors focus:outline-none">Campaigns</button>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-y-auto p-6 bg-surface-50">
                    
                    <!-- DETAILS TAB -->
                    <div x-show="profileActiveTab === 'details'" class="space-y-6">
                        
                        <!-- Engagement Metrics -->
                        <div class="bg-white rounded-sm border border-surface-100 p-4 shadow-sm">
                            <h3 class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-4">Engagement & Health</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500">Email Lead Score</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-lg font-black text-primary-600" x-text="(profileContact.email_lead_score || 1) + '/10'"></span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500">WA Lead Score</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-lg font-black text-emerald-600" x-text="(profileContact.whatsapp_lead_score || 1) + '/10'"></span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500">Last Active</p>
                                    <p class="text-xs font-bold text-surface-900 mt-1" x-text="profileContact.last_active_at ? new Date(profileContact.last_active_at).toLocaleDateString() : 'Never'"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500">Last Engaged</p>
                                    <p class="text-xs font-bold text-surface-900 mt-1" x-text="profileContact.last_engaged_at ? new Date(profileContact.last_engaged_at).toLocaleDateString() : 'Never'"></p>
                                </div>
                            </div>
                        </div>

                        <!-- General Info -->
                        <div class="bg-white rounded-sm border border-surface-100 p-4 shadow-sm space-y-4">
                            <h3 class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Contact Information</h3>
                            <div>
                                <p class="text-[10px] font-bold text-surface-500">Email Address</p>
                                <p class="text-sm font-bold text-surface-900" x-text="profileContact.email"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-surface-500">WhatsApp Number</p>
                                <p class="text-sm font-bold text-surface-900" x-text="profileContact.whatsapp_number || '—'"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-surface-500">Added By</p>
                                <p class="text-sm font-bold text-surface-900" x-text="profileContact.user ? profileContact.user.name : 'System'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- ACTIVITY TAB -->
                    <div x-show="profileActiveTab === 'activity'" class="space-y-4 relative">
                        <!-- Event Type Filter Pills -->
                        <div class="flex flex-wrap gap-1.5 pb-3 mb-2 border-b border-surface-200">
                            <button @click="activityFilter = 'all'" 
                                    :class="activityFilter === 'all' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                All
                            </button>
                            <button @click="activityFilter = 'opened'" 
                                    :class="activityFilter === 'opened' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                Opens
                            </button>
                            <button @click="activityFilter = 'clicked'" 
                                    :class="activityFilter === 'clicked' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                Clicks
                            </button>
                            <button @click="activityFilter = 'form_signup'" 
                                    :class="activityFilter === 'form_signup' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                Signups
                            </button>
                            <button @click="activityFilter = 'tag_added'" 
                                    :class="activityFilter === 'tag_added' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                Tags
                            </button>
                            <button @click="activityFilter = 'stage_changed'" 
                                    :class="activityFilter === 'stage_changed' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                Pipeline
                            </button>
                            <button @click="activityFilter = 'sequence_enrolled'" 
                                    :class="activityFilter === 'sequence_enrolled' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                Sequences
                            </button>
                            <button @click="activityFilter = 'task_completed'" 
                                    :class="activityFilter === 'task_completed' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                Tasks
                            </button>
                            <button @click="activityFilter = 'note_added'" 
                                    :class="activityFilter === 'note_added' ? 'bg-surface-900 text-white' : 'bg-surface-200 text-surface-700 hover:bg-surface-300'"
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider transition-colors cursor-pointer focus:outline-none">
                                Notes
                            </button>
                        </div>

                        <template x-if="filteredActivities().length === 0">
                            <div class="text-center py-8 text-surface-400 text-xs font-bold">No matching activity.</div>
                        </template>

                        <div class="absolute left-[15px] top-12 bottom-2 w-px bg-surface-200"></div>

                        <template x-for="activity in filteredActivities()" :key="activity.id">
                            <div class="flex gap-4 relative z-10">
                                <div class="w-8 h-8 rounded-full border-2 border-white flex items-center justify-center shrink-0 shadow-sm"
                                     :class="{
                                         'bg-emerald-50 text-emerald-600': activity.type === 'form_signup',
                                         'bg-indigo-50 text-indigo-600': activity.type === 'tag_added',
                                         'bg-red-50 text-red-600': activity.type === 'tag_removed' || activity.type === 'sequence_cancelled',
                                         'bg-amber-50 text-amber-600': activity.type === 'stage_changed' || activity.type === 'unsubscribed',
                                         'bg-sky-50 text-sky-600': activity.type === 'task_created' || activity.type === 'task_completed' || activity.type === 'task_reopened',
                                         'bg-purple-50 text-purple-600': activity.type === 'sequence_enrolled' || activity.type === 'sequence_completed',
                                         'bg-rose-50 text-rose-600': activity.type === 'note_added',
                                         'bg-blue-50 text-blue-600': activity.type === 'opened',
                                         'bg-violet-50 text-violet-600': activity.type === 'clicked',
                                         'bg-gray-100 text-gray-600': activity.type === 'sent',
                                         'bg-brand/10 text-brand': !['form_signup','tag_added','tag_removed','stage_changed','task_created','task_completed','task_reopened','sequence_enrolled','sequence_completed','sequence_cancelled','note_added','opened','clicked','sent'].includes(activity.type)
                                     }">
                                    
                                    <!-- Dynamic Icons -->
                                    <template x-if="activity.type === 'form_signup'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    </template>
                                    <template x-if="activity.type === 'tag_added' || activity.type === 'tag_removed'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </template>
                                    <template x-if="activity.type === 'stage_changed'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4-4m-4 4l4 4"></path></svg>
                                    </template>
                                    <template x-if="['task_created','task_completed','task_reopened'].includes(activity.type)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 002-2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    </template>
                                    <template x-if="['sequence_enrolled','sequence_completed','sequence_cancelled'].includes(activity.type)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </template>
                                    <template x-if="activity.type === 'note_added'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                    </template>
                                    <template x-if="activity.type === 'opened'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </template>
                                    <template x-if="activity.type === 'clicked'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                                    </template>
                                    <template x-if="!['form_signup','tag_added','tag_removed','stage_changed','task_created','task_completed','task_reopened','sequence_enrolled','sequence_completed','sequence_cancelled','note_added','opened','clicked'].includes(activity.type)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    </template>
                                </div>
                                <div class="pt-1.5 pb-4">
                                    <p class="text-xs font-bold text-surface-900" x-text="activity.type.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())"></p>
                                    <p class="text-[10px] text-surface-500 mt-0.5" x-text="new Date(activity.created_at).toLocaleString()"></p>
                                    <template x-if="activity.meta && activity.meta.description">
                                        <p class="text-xs text-surface-600 mt-2 bg-white p-2 rounded-sm border border-surface-100" x-text="activity.meta.description"></p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- NOTES TAB -->
                    <div x-show="profileActiveTab === 'notes'" class="space-y-4">
                        <div class="bg-white border border-surface-200 rounded-sm p-3 shadow-sm">
                            <textarea x-model="newNoteContent" rows="3" placeholder="Write a note..." class="w-full text-xs font-semibold text-surface-900 border-none focus:ring-0 p-0 resize-none"></textarea>
                            <div class="flex justify-end mt-2 pt-2 border-t border-surface-100">
                                <button @click="addNote()" :disabled="addingNote || !newNoteContent.trim()" class="bg-surface-900 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-sm disabled:opacity-50 transition-colors">
                                    <span x-show="!addingNote">Save Note</span>
                                    <span x-show="addingNote">Saving...</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3 mt-4">
                            <template x-if="profileContact.notes && profileContact.notes.length === 0">
                                <div class="text-center py-8 text-surface-400 text-xs font-bold">No notes yet.</div>
                            </template>
                            <template x-for="note in profileContact.notes" :key="note.id">
                                <div class="bg-white border border-surface-100 rounded-sm p-3 shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-[10px] font-bold text-surface-500" x-text="new Date(note.created_at).toLocaleString()"></span>
                                        <span class="text-[10px] font-bold bg-surface-100 px-2 py-0.5 rounded-sm" x-text="note.user ? note.user.name : 'User'"></span>
                                    </div>
                                    <p class="text-xs text-surface-700 whitespace-pre-wrap" x-text="note.content"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- TASKS TAB -->
                    <div x-show="profileActiveTab === 'tasks'" class="space-y-4">
                        <div class="bg-white border border-surface-200 rounded-sm p-4 shadow-sm space-y-3">
                            <input type="text" x-model="newTask.title" placeholder="Task Title" class="w-full text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand">
                            <textarea x-model="newTask.description" rows="2" placeholder="Description (optional)" class="w-full text-xs text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand"></textarea>
                            <input type="date" x-model="newTask.due_date" class="w-full text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand">
                            
                            <div class="flex justify-end pt-2">
                                <button @click="addTask()" :disabled="addingTask || !newTask.title.trim()" class="bg-surface-900 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-sm disabled:opacity-50 transition-colors">
                                    <span x-show="!addingTask">Add Task</span>
                                    <span x-show="addingTask">Adding...</span>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3 mt-4">
                            <template x-if="profileContact.tasks && profileContact.tasks.length === 0">
                                <div class="text-center py-8 text-surface-400 text-xs font-bold">No tasks found.</div>
                            </template>
                            <template x-for="task in profileContact.tasks" :key="task.id">
                                <div class="bg-white border border-surface-100 rounded-sm p-3 shadow-sm flex items-start gap-3" :class="{'opacity-50': task.is_completed}">
                                    <input type="checkbox" :checked="task.is_completed" class="rounded-sm border-surface-300 text-brand focus:ring-brand mt-1" disabled>
                                    <div>
                                        <p class="text-xs font-bold text-surface-900" :class="{'line-through': task.is_completed}" x-text="task.title"></p>
                                        <template x-if="task.description">
                                            <p class="text-[10px] text-surface-600 mt-1" x-text="task.description"></p>
                                        </template>
                                        <template x-if="task.due_date">
                                            <p class="text-[10px] font-bold text-red-500 mt-2 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Due: <span x-text="new Date(task.due_date).toLocaleDateString()"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- SEQUENCES TAB -->
                    <div x-show="profileActiveTab === 'sequences'" class="space-y-4">
                        <!-- Enroll Form -->
                        <div class="bg-white border border-surface-200 rounded-sm p-4 shadow-sm space-y-3">
                            <h4 class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Enroll in Sequence</h4>
                            <div class="flex gap-2">
                                <select x-model="enrollSequenceId" class="flex-1 text-xs font-bold text-surface-900 border-surface-200 rounded-sm focus:border-brand focus:ring-1 focus:ring-brand">
                                    <option value="">-- Select Sequence --</option>
                                    @foreach($sequencesList as $seq)
                                        <option value="{{ $seq->id }}">{{ $seq->name }}</option>
                                    @endforeach
                                </select>
                                <button @click="enrollInSequence()" :disabled="enrollingInSeq || !enrollSequenceId" class="bg-surface-900 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-sm disabled:opacity-50 transition-colors">
                                    <span x-show="!enrollingInSeq">Enroll</span>
                                    <span x-show="enrollingInSeq">Enrolling...</span>
                                </button>
                            </div>
                        </div>

                        <!-- Enrollment List -->
                        <div class="space-y-3 mt-4">
                            <h4 class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Active Enrollments</h4>
                            <template x-if="profileContact && (!profileContact.sequence_enrollments || profileContact.sequence_enrollments.length === 0)">
                                <div class="text-center py-8 text-surface-400 text-xs font-bold">Not enrolled in any sequences.</div>
                            </template>
                            <template x-if="profileContact && profileContact.sequence_enrollments && profileContact.sequence_enrollments.length > 0">
                                <div class="space-y-3">
                                    <template x-for="enrollment in profileContact.sequence_enrollments" :key="enrollment.id">
                                        <div class="bg-white border border-surface-100 rounded-sm p-3 shadow-sm flex flex-col gap-2">
                                            <div class="flex justify-between items-center">
                                                <p class="text-xs font-bold text-surface-900" x-text="enrollment.sequence ? enrollment.sequence.name : 'Unknown Sequence'"></p>
                                                <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-sm"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700 border border-emerald-200': enrollment.status === 'active',
                                                        'bg-amber-50 text-amber-700 border border-amber-200': enrollment.status === 'paused',
                                                        'bg-gray-50 text-gray-700 border border-gray-200': enrollment.status === 'completed' || enrollment.status === 'cancelled'
                                                    }"
                                                    x-text="enrollment.status"></span>
                                            </div>
                                            <div class="flex justify-between items-center text-[10px] text-surface-500 font-semibold">
                                                <span x-text="'Current Step: ' + enrollment.current_step_number"></span>
                                                <span x-show="enrollment.scheduled_at" x-text="'Next Send: ' + new Date(enrollment.scheduled_at).toLocaleDateString()"></span>
                                            </div>
                                            <div class="flex justify-end gap-2 mt-2 pt-2 border-t border-surface-50" x-show="enrollment.status === 'active' || enrollment.status === 'paused'">
                                                <button @click="unenrollFromSequence(enrollment.sequence_id)" class="text-red-600 hover:text-red-700 text-[10px] font-black uppercase tracking-widest">
                                                    Cancel Sequence
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                    </div>

                    <!-- CAMPAIGNS TAB -->
                    <div x-show="profileActiveTab === 'campaigns'" class="space-y-4">
                        <h4 class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Campaign Sent History</h4>
                        <template x-if="profileContact && (!profileContact.logs || profileContact.logs.length === 0)">
                            <div class="text-center py-8 text-surface-400 text-xs font-bold">No campaign history found.</div>
                        </template>
                        <template x-if="profileContact && profileContact.logs && profileContact.logs.length > 0">
                            <div class="space-y-3">
                                <template x-for="log in profileContact.logs" :key="log.id">
                                    <div class="bg-white border border-surface-100 rounded-sm p-4 shadow-sm flex flex-col gap-2">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-xs font-bold text-surface-900" x-text="log.campaign ? log.campaign.name : 'Direct Email'"></p>
                                                <p class="text-[9px] font-bold text-surface-500 mt-1" x-text="'Sent: ' + (log.sent_at ? new Date(log.sent_at).toLocaleString() : 'N/A')"></p>
                                            </div>
                                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-sm border"
                                                  :class="{
                                                      'bg-emerald-50 text-emerald-700 border-emerald-200': log.status === 'delivered' || log.status === 'sent',
                                                      'bg-red-50 text-red-700 border-red-200': log.status === 'failed' || log.status === 'bounced',
                                                      'bg-amber-50 text-amber-700 border-amber-200': log.status === 'pending'
                                                  }"
                                                  x-text="log.status"></span>
                                        </div>
                                        
                                        <!-- Engagement Stats (Opens/Clicks/Bounces) -->
                                        <div class="grid grid-cols-3 gap-2 mt-2 pt-2 border-t border-surface-50 text-center">
                                            <div>
                                                <span class="text-[9px] font-bold text-surface-400 uppercase tracking-wider block">Opens</span>
                                                <span class="text-xs font-black text-surface-900" x-text="log.open_count || 0"></span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] font-bold text-surface-400 uppercase tracking-wider block">Clicks</span>
                                                <span class="text-xs font-black text-surface-900" x-text="log.click_count || 0"></span>
                                            </div>
                                            <div>
                                                <span class="text-[9px] font-bold text-surface-400 uppercase tracking-wider block">Bounced?</span>
                                                <span class="text-xs font-black" 
                                                      :class="log.bounce_type ? 'text-red-600 font-bold' : 'text-surface-400'"
                                                      x-text="log.bounce_type ? log.bounce_type : 'No'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </template>
    </div>
</div>
