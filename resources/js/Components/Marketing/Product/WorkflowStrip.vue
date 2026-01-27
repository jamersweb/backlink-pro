<template>
    <section id="workflows" class="workflow-strip py-20 bg-surface2 scroll-mt-24">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Supported Workflows
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div
                    v-for="(workflow, idx) in workflows"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <h3 class="text-lg font-bold mb-2 text-text">{{ workflow.label }}</h3>
                    <p class="text-sm text-muted mb-4">{{ workflow.desc }}</p>
                    <a :href="`/workflows/${workflow.slug}`" class="text-primary hover:underline text-sm font-semibold">
                        Learn more →
                    </a>
                </div>
            </div>
            <div class="text-center" data-reveal>
                <a href="/workflows" class="btn-secondary text-lg px-8 py-4">
                    Explore workflows
                </a>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    workflows: {
        type: Array,
        required: true,
    },
});
</script>
