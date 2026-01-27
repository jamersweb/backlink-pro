<template>
    <div class="evidence-sample-card marketing-card p-6">
        <h3 class="text-lg font-bold mb-4 text-text">Evidence pack preview</h3>
        <div class="space-y-4">
            <div class="p-3 bg-surface2 rounded">
                <div class="text-sm font-semibold text-text mb-1">example.com/comment/123</div>
                <div class="text-xs text-muted mb-2">2026-01-22 14:30 UTC</div>
                <div class="flex gap-2 mb-2">
                    <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">Screenshot</span>
                    <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">HTML</span>
                </div>
                <div class="text-xs text-muted">Status: Live</div>
            </div>
            <div class="p-3 bg-surface2 rounded opacity-60">
                <div class="text-sm font-semibold text-text mb-1">target.org/profile</div>
                <div class="text-xs text-muted mb-2">2026-01-22 10:15 UTC</div>
                <div class="text-xs text-muted">Status: Pending</div>
            </div>
        </div>
    </div>
</template>

<script setup>
// No props needed
</script>
