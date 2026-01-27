<template>
    <section id="hero-section" class="hero-section py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Left Column -->
                <div data-reveal>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                        Build quality backlinks — without manual grind.
                    </h1>
                    <p class="text-xl text-muted mb-8 leading-relaxed">
                        AI selects the safest action, executes workflows, and tracks every link with evidence.
                    </p>
                    <div class="flex flex-wrap gap-4 mb-6">
                        <button @click="scrollToPlanForm" class="btn-primary text-lg px-8 py-4">
                            Run Free Backlink Plan
                        </button>
                        <button @click="openDemoModal" class="btn-secondary text-lg px-8 py-4">
                            Watch 2-min demo
                        </button>
                    </div>
                    <p class="text-sm text-muted">
                        Human approvals • Risk scoring • Evidence logs • No PBNs
                    </p>
                </div>

                <!-- Right Column - Interactive Plan Card -->
                <div data-reveal class="gradient-glow">
                    <div class="marketing-card p-8">
                        <h3 class="text-2xl font-bold mb-6 text-text">Get Your Free Plan</h3>
                        
                        <!-- Form -->
                        <form v-if="!previewData" @submit.prevent="generatePlan" class="space-y-4">
                            <div>
                                <label for="url" class="block text-sm font-medium mb-2 text-text">Website URL</label>
                                <input
                                    id="url"
                                    v-model="form.url"
                                    type="url"
                                    required
                                    placeholder="https://example.com"
                                    class="w-full px-4 py-3 rounded-lg bg-surface2 border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label for="industry" class="block text-sm font-medium mb-2 text-text">Industry (Optional)</label>
                                <select
                                    id="industry"
                                    v-model="form.industry"
                                    class="w-full px-4 py-3 rounded-lg bg-surface2 border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                                >
                                    <option value="">Select industry...</option>
                                    <option value="tech">Technology</option>
                                    <option value="saas">SaaS</option>
                                    <option value="ecommerce">E-commerce</option>
                                    <option value="finance">Finance</option>
                                    <option value="healthcare">Healthcare</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="btn-primary w-full"
                            >
                                <span v-if="loading">Generating...</span>
                                <span v-else>Generate plan</span>
                            </button>
                        </form>

                        <!-- Loading State -->
                        <div v-if="loading" class="space-y-4">
                            <div class="animate-pulse space-y-3">
                                <div class="h-4 bg-surface2 rounded w-3/4"></div>
                                <div class="h-4 bg-surface2 rounded w-1/2"></div>
                                <div class="h-4 bg-surface2 rounded w-2/3"></div>
                            </div>
                        </div>

                        <!-- Preview Panel -->
                        <div v-if="previewData && !showEmailGate" class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-4 bg-surface2 rounded-lg">
                                    <div class="text-3xl font-bold text-primary">{{ previewData.opportunities }}</div>
                                    <div class="text-sm text-muted mt-1">Opportunities</div>
                                </div>
                                <div class="text-center p-4 bg-surface2 rounded-lg">
                                    <div class="text-3xl font-bold" :class="riskScoreColor">{{ previewData.riskScore }}</div>
                                    <div class="text-sm text-muted mt-1">Risk Score</div>
                                </div>
                            </div>
                            <div class="p-4 bg-surface2 rounded-lg">
                                <div class="text-sm text-muted mb-2">Estimated Links/Month</div>
                                <div class="text-xl font-bold text-text">
                                    {{ previewData.estLinks.min }} - {{ previewData.estLinks.max }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold mb-3 text-text">Top Opportunities</div>
                                <div class="space-y-2">
                                    <div
                                        v-for="(op, idx) in previewData.topOps"
                                        :key="idx"
                                        class="p-3 bg-surface2 rounded text-sm"
                                    >
                                        <div class="font-medium text-text">{{ op.domain }}</div>
                                        <div class="text-muted text-xs mt-1">{{ op.reason }}</div>
                                    </div>
                                </div>
                            </div>
                            <button @click="showEmailGate = true" class="btn-primary w-full">
                                Get Full Plan
                            </button>
                        </div>

                        <!-- Email Gate -->
                        <div v-if="showEmailGate" class="space-y-4">
                            <div class="text-center mb-4">
                                <h4 class="text-xl font-bold mb-2 text-text">Get Your Full Plan</h4>
                                <p class="text-sm text-muted">We'll send your complete backlink plan to your email.</p>
                            </div>
                            <form @submit.prevent="submitLead" class="space-y-4">
                                <div>
                                    <label for="email" class="block text-sm font-medium mb-2 text-text">Email Address</label>
                                    <input
                                        id="email"
                                        v-model="emailForm.email"
                                        type="email"
                                        required
                                        placeholder="you@example.com"
                                        class="w-full px-4 py-3 rounded-lg bg-surface2 border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                </div>
                                <button type="submit" :disabled="submitting" class="btn-primary w-full">
                                    <span v-if="submitting">Submitting...</span>
                                    <span v-else>Get My Plan</span>
                                </button>
                            </form>
                            <div v-if="leadSubmitted" class="text-center p-4 bg-success/20 border border-success/30 rounded-lg">
                                <p class="text-success font-semibold">Check your inbox!</p>
                                <p class="text-sm text-muted mt-1">We've sent your plan to {{ emailForm.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed } from 'vue';

const emit = defineEmits(['demo-modal-open']);

const form = ref({
    url: '',
    industry: '',
});

const emailForm = ref({
    email: '',
});

const loading = ref(false);
const previewData = ref(null);
// Note: email gate state is managed internally in this component
const submitting = ref(false);
const leadSubmitted = ref(false);

const riskScoreColor = computed(() => {
    if (!previewData.value) return '';
    const score = previewData.value.riskScore;
    if (score < 40) return 'text-success';
    if (score < 70) return 'text-warning';
    return 'text-danger';
});

const scrollToPlanForm = () => {
    const form = document.querySelector('#hero-section form');
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

const openDemoModal = () => {
    emit('demo-modal-open');
    console.log('hero_generate_clicked');
};

const generatePlan = async () => {
    loading.value = true;
    console.log('hero_preview_loaded');

    try {
        const response = await fetch('/api/plan/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();
        if (data.success) {
            previewData.value = data.data;
        } else {
            alert('Error generating plan. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error generating plan. Please try again.');
    } finally {
        loading.value = false;
    }
};

const submitLead = async () => {
    submitting.value = true;
    console.log('email_gate_opened');
    console.log('email_gate_submitted');

    try {
        const response = await fetch('/api/plan/lead', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                email: emailForm.value.email,
                url: form.value.url,
                industry: form.value.industry,
                previewJson: previewData.value,
            }),
        });

        const data = await response.json();
        if (data.success) {
            leadSubmitted.value = true;
        } else {
            alert('Error submitting. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error submitting. Please try again.');
    } finally {
        submitting.value = false;
    }
};
</script>

<style scoped>
.hero-section {
    min-height: 90vh;
    display: flex;
    align-items: center;
}
</style>
