<template>
    <div class="mock-panel marketing-card">
        <div class="mock-panel-topbar">
            <div class="mock-panel-dots">
                <span class="is-danger"></span>
                <span class="is-warning"></span>
                <span class="is-success"></span>
            </div>
        </div>

        <div class="mock-panel-body">
            <div v-if="type === 'settings'" class="space-y-4">
                <div class="mock-panel-header">
                    <h4 class="font-semibold text-text">Project Settings</h4>
                    <span class="mock-status-pill">Active</span>
                </div>
                <div class="space-y-3">
                    <div class="mock-stat-row">
                        <span class="text-sm text-muted">Risk Threshold</span>
                        <span class="text-sm font-semibold text-text">0.7</span>
                    </div>
                    <div class="mock-stat-row">
                        <span class="text-sm text-muted">Velocity Cap</span>
                        <span class="text-sm font-semibold text-text">10/day</span>
                    </div>
                    <div class="mock-stack-card">
                        <div class="text-sm text-muted mb-2">Whitelist</div>
                        <div class="mock-chip-row">
                            <span class="mock-chip">example.com</span>
                            <span class="mock-chip">trusted.org</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="type === 'audit'" class="space-y-4">
                <h4 class="font-semibold text-text">Website Analysis</h4>
                <div class="space-y-2">
                    <div class="mock-list-row">
                        <svg class="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-muted">Sitemap found</span>
                    </div>
                    <div class="mock-list-row">
                        <svg class="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-muted">HTTPS enabled</span>
                    </div>
                    <div class="mock-list-row is-warning">
                        <svg class="w-4 h-4 text-warning" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-muted">Review recommendations</span>
                    </div>
                </div>
            </div>

            <div v-else-if="type === 'queue'" class="space-y-4">
                <h4 class="font-semibold text-text">Opportunity Queue</h4>
                <div class="space-y-2">
                    <div class="mock-stack-card is-highlight">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-text">example-blog.com</span>
                            <span class="mock-chip">Score: 0.85</span>
                        </div>
                        <div class="text-xs text-muted">Relevant | Low risk</div>
                    </div>
                    <div class="mock-stack-card">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-text">target-site.org</span>
                            <span class="mock-chip is-warning">Score: 0.65</span>
                        </div>
                        <div class="text-xs text-muted">Review needed</div>
                    </div>
                    <div class="mock-stack-card is-muted">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-text">spam-site.net</span>
                            <span class="mock-chip is-danger">Filtered</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="type === 'decision'" class="space-y-4">
                <h4 class="font-semibold text-text">Action Routing</h4>
                <div class="space-y-3">
                    <div class="mock-stack-card">
                        <div class="text-sm text-muted mb-2">Target: example.com</div>
                        <div class="flex items-center gap-2">
                            <span class="mock-chip">Comment</span>
                            <span class="text-xs text-muted">Best for high engagement</span>
                        </div>
                    </div>
                    <div class="mock-stack-card">
                        <div class="text-sm text-muted mb-2">Target: brand-site.org</div>
                        <div class="flex items-center gap-2">
                            <span class="mock-chip is-success">Profile</span>
                            <span class="text-xs text-muted">Best for brand signal</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="type === 'approvals'" class="space-y-4">
                <h4 class="font-semibold text-text">Approval Queue</h4>
                <div class="space-y-2">
                    <div class="mock-stack-card is-warning">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-text">example.com/comment</span>
                            <span class="mock-chip is-warning">Pending</span>
                        </div>
                        <div class="text-xs text-muted mb-2">Risk: 0.75 | Above threshold</div>
                        <div class="flex gap-2">
                            <button class="mock-action-btn" disabled>Approve</button>
                            <button class="mock-action-btn is-secondary" disabled>Reject</button>
                        </div>
                    </div>
                    <div class="mock-stack-card">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-text">target.org/profile</span>
                            <span class="mock-chip is-success">Approved</span>
                        </div>
                        <div class="text-xs text-muted">Risk: 0.45 | Auto-approved</div>
                    </div>
                </div>
            </div>

            <div v-else-if="type === 'evidence'" class="space-y-4">
                <h4 class="font-semibold text-text">Evidence Log</h4>
                <div class="space-y-3">
                    <div class="mock-stack-card">
                        <div class="text-sm font-semibold text-text mb-2">example.com/comment/123</div>
                        <div class="text-xs text-muted mb-2">2026-01-22 14:30 UTC</div>
                        <div class="mock-chip-row mb-2">
                            <span class="mock-chip">Screenshot</span>
                            <span class="mock-chip">HTML</span>
                        </div>
                        <div class="text-xs text-muted">Status: Live</div>
                    </div>
                    <div class="mock-stack-card is-muted">
                        <div class="text-sm font-semibold text-text mb-2">target.org/profile</div>
                        <div class="text-xs text-muted mb-2">2026-01-22 10:15 UTC</div>
                        <div class="text-xs text-muted">Status: Pending</div>
                    </div>
                </div>
            </div>

            <div v-else-if="type === 'reports'" class="space-y-4">
                <h4 class="font-semibold text-text">Reports</h4>
                <div class="space-y-2">
                    <div class="mock-stat-row">
                        <div>
                            <div class="text-sm font-semibold text-text">Weekly Summary</div>
                            <div class="text-xs text-muted">Jan 15-22, 2026</div>
                        </div>
                        <button class="mock-action-btn" disabled>Export</button>
                    </div>
                    <div class="mock-stat-row">
                        <div>
                            <div class="text-sm font-semibold text-text">Evidence Pack</div>
                            <div class="text-xs text-muted">12 actions</div>
                        </div>
                        <button class="mock-action-btn" disabled>Download</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        required: true,
    },
});
</script>

<style scoped>
.mock-panel {
    padding: 0;
    overflow: hidden;
    min-height: 100%;
}

.mock-panel-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.95rem 1.1rem;
    border-bottom: 1px solid rgba(255, 110, 64, 0.12);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0));
}

.mock-panel-dots {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

.mock-panel-dots span {
    width: 0.62rem;
    height: 0.62rem;
    border-radius: 999px;
    display: inline-block;
}

.mock-panel-dots .is-danger { background: rgba(255, 140, 110, 0.92); }
.mock-panel-dots .is-warning { background: rgba(255, 187, 120, 0.92); }
.mock-panel-dots .is-success { background: rgba(255, 225, 210, 0.9); }

.mock-panel-body {
    padding: 1.35rem;
}

.mock-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.mock-status-pill,
.mock-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 1.75rem;
    padding: 0 0.7rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.2);
    background: rgba(255, 110, 64, 0.1);
    color: #ff9f7d;
    font-size: 0.72rem;
    font-weight: 700;
}

.mock-chip.is-warning { color: #ffc28a; }
.mock-chip.is-danger { color: #ffc0ae; }
.mock-chip.is-success,
.mock-status-pill { color: #ffd8c8; }

.mock-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.mock-stat-row,
.mock-stack-card,
.mock-list-row {
    border-radius: 1rem;
    border: 1px solid rgba(255, 110, 64, 0.12);
    background: rgba(255, 255, 255, 0.025);
}

.mock-stat-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.95rem 1rem;
}

.mock-stack-card {
    padding: 1rem;
}

.mock-stack-card.is-highlight {
    border-color: rgba(255, 110, 64, 0.28);
    box-shadow: inset 2px 0 0 rgba(255, 110, 64, 0.85);
}

.mock-stack-card.is-warning {
    border-color: rgba(255, 187, 120, 0.28);
    box-shadow: inset 2px 0 0 rgba(255, 187, 120, 0.9);
}

.mock-stack-card.is-muted {
    opacity: 0.68;
}

.mock-list-row {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.8rem 0.9rem;
}

.mock-list-row.is-warning {
    background: rgba(255, 187, 120, 0.08);
    border-color: rgba(255, 187, 120, 0.18);
}

.mock-action-btn {
    min-height: 2rem;
    padding: 0 0.85rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.22);
    background: rgba(255, 110, 64, 0.12);
    color: #fff7f2;
    font-size: 0.73rem;
    font-weight: 700;
    opacity: 0.7;
    cursor: not-allowed;
}

.mock-action-btn.is-secondary {
    background: rgba(255, 255, 255, 0.04);
}
</style>
