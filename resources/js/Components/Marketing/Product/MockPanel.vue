<template>
    <div class="mock-panel marketing-card p-0 overflow-hidden">
        <div class="bg-surface2 border-b border-border px-4 py-3 flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-danger"></div>
            <div class="w-3 h-3 rounded-full bg-warning"></div>
            <div class="w-3 h-3 rounded-full bg-success"></div>
            <div class="flex-1"></div>
            <div class="text-xs text-muted">BacklinkPro</div>
        </div>
        <div class="p-6">
            <!-- Settings Mock -->
            <div v-if="type === 'settings'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="font-semibold text-text">Project Settings</h4>
                    <span class="px-2 py-1 bg-success/20 text-success text-xs rounded">Active</span>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-surface2 rounded">
                        <span class="text-sm text-muted">Risk Threshold</span>
                        <span class="text-sm font-semibold text-text">0.7</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-surface2 rounded">
                        <span class="text-sm text-muted">Velocity Cap</span>
                        <span class="text-sm font-semibold text-text">10/day</span>
                    </div>
                    <div class="p-3 bg-surface2 rounded">
                        <div class="text-sm text-muted mb-2">Whitelist</div>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">example.com</span>
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">trusted.org</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Mock -->
            <div v-else-if="type === 'audit'" class="space-y-4">
                <h4 class="font-semibold text-text">Website Analysis</h4>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 p-2 bg-surface2 rounded">
                        <svg class="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-muted">Sitemap found</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-surface2 rounded">
                        <svg class="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-muted">HTTPS enabled</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-warning/20 rounded">
                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-muted">Review recommendations</span>
                    </div>
                </div>
            </div>

            <!-- Queue Mock -->
            <div v-else-if="type === 'queue'" class="space-y-4">
                <h4 class="font-semibold text-text">Opportunity Queue</h4>
                <div class="space-y-2">
                    <div class="p-3 bg-surface2 rounded border-l-2 border-primary">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-text">example-blog.com</span>
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">Score: 0.85</span>
                        </div>
                        <div class="text-xs text-muted">Relevant • Low risk</div>
                    </div>
                    <div class="p-3 bg-surface2 rounded">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-text">target-site.org</span>
                            <span class="px-2 py-1 bg-warning/20 text-warning text-xs rounded">Score: 0.65</span>
                        </div>
                        <div class="text-xs text-muted">Review needed</div>
                    </div>
                    <div class="p-3 bg-surface2 rounded opacity-60">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-text">spam-site.net</span>
                            <span class="px-2 py-1 bg-danger/20 text-danger text-xs rounded">Filtered</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decision Mock -->
            <div v-else-if="type === 'decision'" class="space-y-4">
                <h4 class="font-semibold text-text">Action Routing</h4>
                <div class="space-y-3">
                    <div class="p-3 bg-surface2 rounded">
                        <div class="text-sm text-muted mb-2">Target: example.com</div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">Comment</span>
                            <span class="text-xs text-muted">→ High engagement</span>
                        </div>
                    </div>
                    <div class="p-3 bg-surface2 rounded">
                        <div class="text-sm text-muted mb-2">Target: brand-site.org</div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 bg-success/20 text-success text-xs rounded">Profile</span>
                            <span class="text-xs text-muted">→ Brand signal</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approvals Mock -->
            <div v-else-if="type === 'approvals'" class="space-y-4">
                <h4 class="font-semibold text-text">Approval Queue</h4>
                <div class="space-y-2">
                    <div class="p-3 bg-surface2 rounded border-l-2 border-warning">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-text">example.com/comment</span>
                            <span class="px-2 py-1 bg-warning/20 text-warning text-xs rounded">Pending</span>
                        </div>
                        <div class="text-xs text-muted mb-2">Risk: 0.75 • Above threshold</div>
                        <div class="flex gap-2">
                            <button class="px-3 py-1 bg-success text-white text-xs rounded opacity-50 cursor-not-allowed" disabled>Approve</button>
                            <button class="px-3 py-1 bg-danger text-white text-xs rounded opacity-50 cursor-not-allowed" disabled>Reject</button>
                        </div>
                    </div>
                    <div class="p-3 bg-surface2 rounded">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-text">target.org/profile</span>
                            <span class="px-2 py-1 bg-success/20 text-success text-xs rounded">Approved</span>
                        </div>
                        <div class="text-xs text-muted">Risk: 0.45 • Auto-approved</div>
                    </div>
                </div>
            </div>

            <!-- Evidence Mock -->
            <div v-else-if="type === 'evidence'" class="space-y-4">
                <h4 class="font-semibold text-text">Evidence Log</h4>
                <div class="space-y-3">
                    <div class="p-3 bg-surface2 rounded">
                        <div class="text-sm font-semibold text-text mb-2">example.com/comment/123</div>
                        <div class="text-xs text-muted mb-2">2026-01-22 14:30 UTC</div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">Screenshot</span>
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">HTML</span>
                        </div>
                        <div class="text-xs text-muted">Status: Live</div>
                    </div>
                    <div class="p-3 bg-surface2 rounded opacity-60">
                        <div class="text-sm font-semibold text-text mb-2">target.org/profile</div>
                        <div class="text-xs text-muted mb-2">2026-01-22 10:15 UTC</div>
                        <div class="text-xs text-muted">Status: Pending</div>
                    </div>
                </div>
            </div>

            <!-- Reports Mock -->
            <div v-else-if="type === 'reports'" class="space-y-4">
                <h4 class="font-semibold text-text">Reports</h4>
                <div class="space-y-2">
                    <div class="p-3 bg-surface2 rounded flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-text">Weekly Summary</div>
                            <div class="text-xs text-muted">Jan 15-22, 2026</div>
                        </div>
                        <button class="px-3 py-1 bg-primary text-white text-xs rounded opacity-50 cursor-not-allowed" disabled>Export</button>
                    </div>
                    <div class="p-3 bg-surface2 rounded flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-text">Evidence Pack</div>
                            <div class="text-xs text-muted">12 actions</div>
                        </div>
                        <button class="px-3 py-1 bg-primary text-white text-xs rounded opacity-50 cursor-not-allowed" disabled>Download</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    type: {
        type: String,
        required: true,
    },
});
</script>
