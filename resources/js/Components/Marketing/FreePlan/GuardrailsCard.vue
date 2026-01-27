<template>
    <div class="guardrails-card marketing-card p-6">
        <h3 class="text-xl font-bold mb-6 text-text">Guardrails Preset</h3>
        <div class="space-y-4">
            <div
                v-for="(value, key) in guardrails"
                :key="key"
                class="flex items-start gap-3"
            >
                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div>
                    <div class="text-sm font-semibold text-text mb-1">{{ formatKey(key) }}</div>
                    <div class="text-sm text-muted">{{ value }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    guardrails: {
        type: Object,
        required: true,
    },
});

const formatKey = (key) => {
    return key
        .replace(/([A-Z])/g, ' $1')
        .replace(/^./, str => str.toUpperCase())
        .trim();
};
</script>
