<template>
    <div class="partner-apply-form marketing-card p-8">
        <h2 class="text-2xl font-bold mb-6 text-text">Apply to Partner Program</h2>

        <!-- Error Summary -->
        <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-danger/10 border border-danger/20 rounded-lg">
            <h3 class="font-semibold text-danger mb-2">Please fix the following errors:</h3>
            <ul class="text-sm text-muted space-y-1">
                <li v-for="(error, field) in form.errors" :key="field">
                    {{ error }}
                </li>
            </ul>
        </div>

        <form @submit.prevent="submit">
            <!-- Partner Type -->
            <div class="mb-4">
                <label for="partner_type" class="block text-sm font-semibold text-text mb-2">
                    Partner Type <span class="text-danger">*</span>
                </label>
                <select
                    id="partner_type"
                    v-model="form.partner_type"
                    class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                    required
                >
                    <option value="">Select...</option>
                    <option
                        v-for="type in partnerTypes"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ type.label }}
                    </option>
                </select>
                <p v-if="form.errors.partner_type" class="text-xs text-danger mt-1">
                    {{ form.errors.partner_type }}
                </p>
            </div>

            <!-- Name + Email -->
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="name" class="block text-sm font-semibold text-text mb-2">
                        Name <span class="text-danger">*</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <p v-if="form.errors.name" class="text-xs text-danger mt-1">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-text mb-2">
                        Email <span class="text-danger">*</span>
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <p v-if="form.errors.email" class="text-xs text-danger mt-1">
                        {{ form.errors.email }}
                    </p>
                </div>
            </div>

            <!-- Company + Website -->
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="company" class="block text-sm font-semibold text-text mb-2">
                        Company
                    </label>
                    <input
                        id="company"
                        v-model="form.company"
                        type="text"
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>

                <div>
                    <label for="website" class="block text-sm font-semibold text-text mb-2">
                        Website
                    </label>
                    <input
                        id="website"
                        v-model="form.website"
                        type="url"
                        placeholder="https://example.com"
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                </div>
            </div>

            <!-- Company Size + Client Count (conditional) -->
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="company_size" class="block text-sm font-semibold text-text mb-2">
                        Company Size
                    </label>
                    <select
                        id="company_size"
                        v-model="form.company_size"
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="">Select...</option>
                        <option
                            v-for="size in companySizes"
                            :key="size.value"
                            :value="size.value"
                        >
                            {{ size.label }}
                        </option>
                    </select>
                </div>

                <div v-if="form.partner_type === 'reseller'">
                    <label for="client_count" class="block text-sm font-semibold text-text mb-2">
                        Client Count
                    </label>
                    <select
                        id="client_count"
                        v-model="form.client_count"
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="">Select...</option>
                        <option
                            v-for="count in clientCounts"
                            :key="count.value"
                            :value="count.value"
                        >
                            {{ count.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Regions -->
            <div class="mb-4">
                <label for="regions" class="block text-sm font-semibold text-text mb-2">
                    Regions
                </label>
                <input
                    id="regions"
                    v-model="form.regions"
                    type="text"
                    placeholder="e.g., US, EU, MENA, Global"
                    class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                />
            </div>

            <!-- Message -->
            <div class="mb-4">
                <label for="message" class="block text-sm font-semibold text-text mb-2">
                    Message <span class="text-danger">*</span>
                </label>
                <textarea
                    id="message"
                    v-model="form.message"
                    rows="5"
                    required
                    class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                    placeholder="Tell us about your model, audience, and how you'd like to partner..."
                ></textarea>
                <p v-if="form.errors.message" class="text-xs text-danger mt-1">
                    {{ form.errors.message }}
                </p>
            </div>

            <!-- Hidden: Honeypot -->
            <div class="sr-only">
                <label for="hp">Leave this field empty</label>
                <input id="hp" v-model="form.hp" type="text" name="hp" tabindex="-1" autocomplete="off" />
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing"
                class="btn-primary w-full text-lg px-8 py-4 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="form.processing">Submitting...</span>
                <span v-else>Submit Application</span>
            </button>

            <!-- Disclaimer -->
            <p class="text-xs text-muted mt-4 text-center">
                No guaranteed backlinks. We focus on safe workflows, approvals, and evidence.
            </p>
        </form>
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    partnerTypes: {
        type: Array,
        required: true,
    },
    companySizes: {
        type: Array,
        required: true,
    },
    clientCounts: {
        type: Array,
        required: true,
    },
    selectedTier: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['success']);

// Watch for selectedTier changes
watch(() => props.selectedTier, (newTier) => {
    if (newTier) {
        form.partner_type = newTier;
    }
}, { immediate: true });

const form = useForm({
    partner_type: '',
    name: '',
    email: '',
    company: '',
    website: '',
    company_size: '',
    client_count: '',
    regions: '',
    message: '',
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

const submit = () => {
    form.post('/partners/apply', {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            // Clear message field on success
            form.message = '';
        },
    });
};

onMounted(() => {
    captureUTM();
    
    // Load saved partner type from localStorage
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('bp_partner_type');
        if (saved && ['referral', 'reseller', 'integration'].includes(saved)) {
            form.partner_type = saved;
        }
    }
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
