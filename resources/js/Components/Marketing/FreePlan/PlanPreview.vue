<template>
    <div v-if="plan" id="plan-preview" class="plan-preview py-20 scroll-mt-24" data-reveal>
        <div class="marketing-container max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Your Backlink Plan
            </h2>

            <!-- Summary Cards -->
            <div class="grid md:grid-cols-3 gap-6 mb-12" data-reveal>
                <div class="marketing-card p-6 text-center">
                    <div class="text-3xl font-bold text-primary mb-2">{{ plan.summary.weeklyActions }}</div>
                    <div class="text-sm text-muted">Weekly Actions</div>
                </div>
                <div class="marketing-card p-6 text-center">
                    <div class="text-lg font-bold text-text mb-2 capitalize">{{ plan.summary.riskMode }}</div>
                    <div class="text-sm text-muted">Risk Mode</div>
                </div>
                <div class="marketing-card p-6 text-center">
                    <div class="text-lg font-bold text-text mb-2">{{ getTierHint() }}</div>
                    <div class="text-sm text-muted">Suggested Plan</div>
                </div>
            </div>

            <!-- Workflow Mix -->
            <div class="marketing-card p-8 mb-8" data-reveal>
                <WorkflowMixBars :mix="plan.workflowMix" :workflows="workflows" />
            </div>

            <!-- Weekly Schedule -->
            <div class="marketing-card p-8 mb-8" data-reveal>
                <WeeklyScheduleTable :schedule="plan.weeklySchedule" />
            </div>

            <!-- Anchor Mix -->
            <div class="marketing-card p-8 mb-8" data-reveal>
                <AnchorMixDonut :anchor-mix="plan.anchorMix" />
            </div>

            <!-- Guardrails -->
            <div class="mb-8" data-reveal>
                <GuardrailsCard :guardrails="plan.guardrails" />
            </div>

            <!-- Next Steps -->
            <div class="marketing-card p-8 mb-8" data-reveal>
                <NextStepsList :steps="plan.nextSteps" />
            </div>

            <!-- Disclosures -->
            <div class="mb-8 p-4 bg-warning/10 border border-warning/20 rounded-lg" data-reveal>
                <div class="space-y-2 text-sm text-muted">
                    <p v-for="(disclosure, idx) in plan.disclosures" :key="idx">
                        {{ disclosure }}
                    </p>
                </div>
            </div>

            <!-- CTAs -->
            <div class="flex flex-wrap gap-4 justify-center" data-reveal>
                <a href="/pricing" class="btn-primary text-lg px-8 py-4">
                    See pricing
                </a>
                <a href="/workflows" class="btn-secondary text-lg px-8 py-4">
                    Explore workflows
                </a>
                <a href="/contact" class="btn-secondary text-lg px-8 py-4">
                    Book demo
                </a>
                <button
                    v-if="email"
                    @click="emailPlan"
                    class="btn-secondary text-lg px-8 py-4 opacity-50 cursor-not-allowed"
                    disabled
                    title="Email feature coming soon"
                >
                    Email me this plan
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    plan: {
        type: Object,
        default: null,
    },
    workflows: {
        type: Array,
        required: true,
    },
    email: {
        type: String,
        default: '',
    },
});

import WorkflowMixBars from './WorkflowMixBars.vue';
import WeeklyScheduleTable from './WeeklyScheduleTable.vue';
import AnchorMixDonut from './AnchorMixDonut.vue';
import GuardrailsCard from './GuardrailsCard.vue';
import NextStepsList from './NextStepsList.vue';

const getTierHint = () => {
    if (!props.plan) return 'Starter';
    const monthly = props.plan.summary.weeklyActions * 4;
    if (monthly < 200) return 'Starter';
    if (monthly < 400) return 'Growth';
    return 'Pro';
};

const emailPlan = () => {
    // Placeholder - would send email with plan
    alert('Email feature coming soon');
};
</script>
