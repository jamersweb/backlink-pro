<template>
    <div id="free-plan-form" class="free-plan-form marketing-card p-8">
        <h2 class="text-2xl font-bold mb-6 text-text">Generate Your Plan</h2>

        <!-- Error Summary -->
        <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-danger/10 border border-danger/20 rounded-lg">
            <h3 class="font-semibold text-danger mb-2">Please fix the following errors:</h3>
            <ul class="text-sm text-muted space-y-1">
                <li v-for="(error, field) in form.errors" :key="field">
                    {{ error }}
                </li>
            </ul>
        </div>

        <Stepper :current-step="currentStep" :steps="steps" />

        <form @submit.prevent="submit">
            <!-- Step 1: Website + Segment + Risk Mode -->
            <div v-show="currentStep === 0" class="space-y-6">
                <UrlInputCard
                    v-model="form.website"
                    :error="form.errors.website"
                />
                <div>
                    <label for="segment" class="block text-sm font-semibold text-text mb-2">
                        Segment <span class="text-danger">*</span>
                    </label>
                    <select
                        id="segment"
                        v-model="form.segment"
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                        required
                    >
                        <option value="">Select...</option>
                        <option
                            v-for="seg in segments"
                            :key="seg.value"
                            :value="seg.value"
                        >
                            {{ seg.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.segment" class="text-xs text-danger mt-1">
                        {{ form.errors.segment }}
                    </p>
                </div>
                <RiskModeSelector
                    v-model="form.risk_mode"
                    :modes="riskModes"
                    :error="form.errors.risk_mode"
                />
            </div>

            <!-- Step 2: Goals + Targets + Competitors + Budget + Email -->
            <div v-show="currentStep === 1" class="space-y-6">
                <GoalsSelector
                    v-model="form.goals"
                    :goals="goals"
                    :error="form.errors.goals"
                />
                <TargetsRepeater v-model="form.target_pages" />
                <CompetitorsRepeater v-model="form.competitors" />
                <BudgetEmailRow
                    :budget="form.monthly_budget"
                    :email="form.email"
                    @update:budget="form.monthly_budget = $event"
                    @update:email="form.email = $event"
                />
            </div>

            <!-- Hidden: UTM + Honeypot -->
            <div class="sr-only">
                <label for="hp">Leave this field empty</label>
                <input id="hp" v-model="form.hp" type="text" name="hp" tabindex="-1" autocomplete="off" />
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between mt-8">
                <button
                    v-if="currentStep > 0"
                    type="button"
                    @click="currentStep--"
                    class="btn-secondary px-6 py-3"
                >
                    Back
                </button>
                <div v-else></div>
                <button
                    v-if="currentStep < steps.length - 1"
                    type="button"
                    @click="nextStep"
                    class="btn-primary px-6 py-3"
                >
                    Next
                </button>
                <button
                    v-else
                    type="submit"
                    :disabled="form.processing"
                    class="btn-primary px-6 py-3 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="form.processing">Generating...</span>
                    <span v-else>Generate plan</span>
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Stepper from './Stepper.vue';
import UrlInputCard from './UrlInputCard.vue';
import RiskModeSelector from './RiskModeSelector.vue';
import GoalsSelector from './GoalsSelector.vue';
import TargetsRepeater from './TargetsRepeater.vue';
import CompetitorsRepeater from './CompetitorsRepeater.vue';
import BudgetEmailRow from './BudgetEmailRow.vue';

const props = defineProps({
    segments: {
        type: Array,
        required: true,
    },
    riskModes: {
        type: Array,
        required: true,
    },
    goals: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['success']);

const currentStep = ref(0);

const steps = [
    { label: 'Basic Info' },
    { label: 'Goals & Details' },
];

const form = useForm({
    website: '',
    segment: '',
    risk_mode: '',
    goals: [],
    target_pages: [],
    competitors: [],
    monthly_budget: null,
    email: '',
    utm: {},
    hp: '',
});

const captureUTM = () => {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);
    const utm = {};
    ['source', 'medium', 'campaign', 'term', 'content'].forEach(key => {
        const value = params.get(`utm_${key}`);
        if (value) utm[key] = value;
    });
    if (Object.keys(utm).length > 0) {
        form.utm = utm;
    }
};

const nextStep = () => {
    // Basic validation for step 1
    if (currentStep.value === 0) {
        if (!form.website || !form.segment || !form.risk_mode) {
            return;
        }
    }
    currentStep.value++;
};

const submit = () => {
    form.post('/free-backlink-plan', {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            // Scroll to plan preview
            setTimeout(() => {
                const preview = document.getElementById('plan-preview');
                if (preview) {
                    preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        },
    });
};

onMounted(() => {
    captureUTM();
});
</script>

<style scoped>
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
</style>
