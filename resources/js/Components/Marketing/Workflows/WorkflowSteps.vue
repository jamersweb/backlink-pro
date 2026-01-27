<template>
    <section id="steps" class="workflow-steps py-20 scroll-mt-24" data-reveal>
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-text">How It Works</h2>
            <div class="space-y-6">
                <div
                    v-for="(step, idx) in steps"
                    :key="idx"
                    :class="[
                        'marketing-card p-6',
                        isApprovalStep(step) && 'bg-primary/10 border-2 border-primary/20'
                    ]"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                            {{ idx + 1 }}
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold mb-2 text-text">
                                {{ step.title }}
                                <span
                                    v-if="isApprovalStep(step)"
                                    class="ml-2 px-2 py-1 bg-primary text-white text-xs rounded"
                                >
                                    Approval gate
                                </span>
                            </h3>
                            <p class="text-sm text-muted">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    steps: {
        type: Array,
        required: true,
    },
});

const isApprovalStep = (step) => {
    return step.title.toLowerCase().includes('approval') || step.title.toLowerCase().includes('gate');
};
</script>
