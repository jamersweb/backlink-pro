<template>
    <div class="workflow-mix-bars">
        <h3 class="text-xl font-bold mb-6 text-text">Workflow Mix</h3>
        <div class="space-y-4">
            <div
                v-for="(workflow, idx) in workflows"
                :key="idx"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-semibold text-text">{{ workflow.label }}</span>
                    <span class="text-sm text-muted">{{ mix[workflow.value] }}%</span>
                </div>
                <div class="w-full h-4 bg-surface2 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-primary transition-all duration-500"
                        :style="{ width: `${mix[workflow.value]}%` }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
    workflows: {
        type: Array,
        required: true,
    },
});
</script>
