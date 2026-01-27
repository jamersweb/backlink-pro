<template>
    <section class="which-plan-picker py-20">
        <div class="marketing-container max-w-3xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Which plan fits?
            </h2>

            <div v-if="!result" class="marketing-card p-8" data-reveal>
                <!-- Question 1: Segment -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-text mb-4">
                        What's your segment?
                    </label>
                    <div class="grid md:grid-cols-2 gap-3">
                        <button
                            v-for="option in segmentOptions"
                            :key="option.value"
                            @click="answers.segment = option.value"
                            :class="[
                                'px-4 py-3 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                                answers.segment === option.value
                                    ? 'bg-primary text-white'
                                    : 'bg-surface2 border border-border text-muted hover:text-text'
                            ]"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <!-- Question 2: Risk Mode -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-text mb-4">
                        What's your risk tolerance?
                    </label>
                    <div class="grid md:grid-cols-3 gap-3">
                        <button
                            v-for="option in riskOptions"
                            :key="option.value"
                            @click="answers.risk = option.value"
                            :class="[
                                'px-4 py-3 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                                answers.risk === option.value
                                    ? 'bg-primary text-white'
                                    : 'bg-surface2 border border-border text-muted hover:text-text'
                            ]"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <!-- Question 3: Team Size -->
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-text mb-4">
                        Team size?
                    </label>
                    <div class="grid md:grid-cols-3 gap-3">
                        <button
                            v-for="option in teamOptions"
                            :key="option.value"
                            @click="answers.team = option.value"
                            :class="[
                                'px-4 py-3 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                                answers.team === option.value
                                    ? 'bg-primary text-white'
                                    : 'bg-surface2 border border-border text-muted hover:text-text'
                            ]"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <button
                    @click="calculateResult"
                    :disabled="!canSubmit"
                    class="btn-primary w-full disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Get recommendation
                </button>
            </div>

            <!-- Result -->
            <div v-else class="marketing-card p-8" data-reveal>
                <h3 class="text-2xl font-bold mb-4 text-text">Recommended: {{ result.plan }}</h3>
                <p class="text-muted mb-6">{{ result.rationale }}</p>
                <div class="flex flex-wrap gap-4">
                    <a
                        :href="result.cta.href"
                        class="btn-primary px-8 py-4"
                    >
                        {{ result.cta.label }}
                    </a>
                    <a href="/pricing" class="btn-secondary px-8 py-4">
                        View all plans
                    </a>
                </div>
                <button
                    @click="reset"
                    class="mt-4 text-sm text-primary hover:underline"
                >
                    Start over
                </button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed } from 'vue';

const answers = ref({
    segment: '',
    risk: '',
    team: '',
});

const result = ref(null);

const segmentOptions = [
    { value: 'saas', label: 'SaaS' },
    { value: 'ecommerce', label: 'Ecommerce' },
    { value: 'local', label: 'Local' },
    { value: 'agency', label: 'Agency' },
];

const riskOptions = [
    { value: 'conservative', label: 'Conservative' },
    { value: 'balanced', label: 'Balanced' },
    { value: 'growth', label: 'Growth' },
];

const teamOptions = [
    { value: '1', label: '1 person' },
    { value: '2-3', label: '2–3 people' },
    { value: '4-10', label: '4–10 people' },
];

const canSubmit = computed(() => {
    return answers.value.segment && answers.value.risk && answers.value.team;
});

const calculateResult = () => {
    const { segment, risk, team } = answers.value;

    // Simple heuristics
    if (team === '4-10' || segment === 'agency') {
        result.value = {
            plan: 'Pro / Agency',
            rationale: 'Your team size and needs suggest the Pro plan with advanced approvals and audit capabilities.',
            cta: { label: 'Talk to sales', href: '/contact' },
        };
    } else if (team === '2-3' || risk === 'growth' || segment === 'ecommerce') {
        result.value = {
            plan: 'Growth',
            rationale: 'Growth plan offers the right balance of workflows, team seats, and controls for scaling.',
            cta: { label: 'Generate my plan', href: '/free-backlink-plan' },
        };
    } else {
        result.value = {
            plan: 'Starter',
            rationale: 'Starter plan is perfect for testing guardrailed workflows with basic approvals and evidence logs.',
            cta: { label: 'Start with Free Plan', href: '/free-backlink-plan' },
        };
    }
};

const reset = () => {
    answers.value = { segment: '', risk: '', team: '' };
    result.value = null;
};
</script>
