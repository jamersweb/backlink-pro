<template>
    <section id="workflow" class="workflow-used py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Workflow Used
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                The exact workflow steps executed with AI decisioning rules.
            </p>

            <!-- Workflow Steps -->
            <div class="max-w-5xl mx-auto mb-12" data-reveal>
                <div class="hidden md:flex justify-between items-center relative">
                    <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-border transform -translate-y-1/2 z-0"></div>
                    <div
                        v-for="(step, idx) in workflowSteps"
                        :key="idx"
                        class="relative z-10 flex-1 text-center"
                    >
                        <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center font-bold mx-auto mb-4">
                            {{ idx + 1 }}
                        </div>
                        <h4 class="font-semibold text-text mb-2">{{ step.title }}</h4>
                        <p class="text-xs text-muted">{{ step.desc }}</p>
                    </div>
                </div>

                <!-- Mobile Vertical -->
                <div class="md:hidden space-y-6">
                    <div
                        v-for="(step, idx) in workflowSteps"
                        :key="idx"
                        class="flex items-start gap-4"
                    >
                        <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold flex-shrink-0">
                            {{ idx + 1 }}
                        </div>
                        <div>
                            <h4 class="font-semibold text-text mb-1">{{ step.title }}</h4>
                            <p class="text-sm text-muted">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Rules -->
            <div class="max-w-3xl mx-auto" data-reveal>
                <div class="marketing-card p-8">
                    <h3 class="text-xl font-bold mb-6 text-text">AI Decisioning Rules Used</h3>
                    <ul class="space-y-3">
                        <li
                            v-for="(rule, idx) in caseStudy.aiRules"
                            :key="idx"
                            class="flex items-start gap-3 text-muted"
                        >
                            <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ rule }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Disclaimer -->
            <div class="mt-8 max-w-3xl mx-auto p-4 bg-warning/10 border border-warning/20 rounded-lg" data-reveal>
                <p class="text-sm text-muted">
                    <strong class="text-text">Note:</strong> No guaranteed links; every action is logged with evidence. 
                    Outcomes vary based on site authority, niche, and opportunity quality.
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    caseStudy: {
        type: Object,
        required: true,
    },
});

const workflowSteps = [
    { title: 'Discover', desc: 'Opportunity identification' },
    { title: 'Filter', desc: 'Quality & relevance scoring' },
    { title: 'Decide', desc: 'AI workflow selection' },
    { title: 'Execute', desc: 'Automated placement' },
    { title: 'Approve', desc: 'Human review queue' },
    { title: 'Evidence', desc: 'Screenshot & logs' },
    { title: 'Monitor', desc: 'Link health tracking' },
];
</script>
