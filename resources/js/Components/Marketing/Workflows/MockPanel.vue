<template>
    <div class="mock-panel marketing-card p-6">
        <div class="aspect-video bg-surface2 rounded-lg flex items-center justify-center border border-border">
            <div class="text-center">
                <div class="text-5xl mb-4">🖥️</div>
                <p class="text-muted text-sm">{{ stepTitle }} UI</p>
                <p class="text-xs text-muted mt-2">Screenshot placeholder</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    stepTitle: {
        type: String,
        required: true,
    },
});
</script>
