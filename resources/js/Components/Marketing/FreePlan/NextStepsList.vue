<template>
    <div class="next-steps-list">
        <h3 class="text-xl font-bold mb-6 text-text">Recommended Next Steps</h3>
        <ol class="space-y-3">
            <li
                v-for="(step, idx) in steps"
                :key="idx"
                class="flex items-start gap-3"
            >
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">
                    {{ idx + 1 }}
                </span>
                <span class="text-muted">{{ step }}</span>
            </li>
        </ol>
    </div>
</template>

<script setup>
const props = defineProps({
    steps: {
        type: Array,
        required: true,
    },
});
</script>
