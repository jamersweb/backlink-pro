<template>
    <div class="safety-profile flex flex-wrap gap-4 mb-8">
        <div class="marketing-card p-4 bg-primary/10 border border-primary/20">
            <div class="text-xs font-semibold text-muted mb-1">Risk</div>
            <div class="text-sm font-bold text-text">{{ profile.risk }}</div>
        </div>
        <div class="marketing-card p-4 bg-success/10 border border-success/20">
            <div class="text-xs font-semibold text-muted mb-1">Time to signals</div>
            <div class="text-sm font-bold text-text">{{ profile.timeToFirstSignals }}</div>
        </div>
        <div class="marketing-card p-4 bg-warning/10 border border-warning/20">
            <div class="text-xs font-semibold text-muted mb-1">Moderation variance</div>
            <div class="text-sm font-bold text-text">{{ profile.moderationVariance }}</div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
});
</script>
