<template>
    <div
        data-reveal
        :class="[
            'marketing-card p-6 cursor-pointer transition-all hover:scale-105',
            tier.highlight && 'ring-2 ring-primary'
        ]"
        @click="select"
    >
        <div v-if="tier.highlight" class="mb-4">
            <span class="px-3 py-1 bg-primary/20 text-primary text-xs font-semibold rounded-full">
                Popular
            </span>
        </div>
        <h3 class="text-xl font-bold mb-2 text-text">{{ tier.name }}</h3>
        <p class="text-sm text-muted mb-4">{{ tier.bestFor }}</p>
        <ul class="space-y-2 mb-6">
            <li
                v-for="(bullet, idx) in tier.bullets"
                :key="idx"
                class="flex items-start gap-2 text-sm text-muted"
            >
                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ bullet }}</span>
            </li>
        </ul>
        <button
            @click.stop="select"
            class="btn-primary w-full"
        >
            {{ tier.cta }}
        </button>
    </div>
</template>

<script setup>
const props = defineProps({
    tier: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['select']);

const select = () => {
    emit('select', props.tier.id);
};
</script>
