<template>
    <div class="contact-form marketing-card p-8">
        <h2 class="text-2xl font-bold mb-6 text-text">Contact Us</h2>

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
            <!-- Row 1: Inquiry Type + Segment -->
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="inquiry_type" class="block text-sm font-semibold text-text mb-2">
                        Inquiry Type <span class="text-danger">*</span>
                    </label>
                    <select
                        id="inquiry_type"
                        v-model="form.inquiry_type"
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                        required
                    >
                        <option value="">Select...</option>
                        <option
                            v-for="type in inquiryTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>
                    <p v-if="form.errors.inquiry_type" class="text-xs text-danger mt-1">
                        {{ form.errors.inquiry_type }}
                    </p>
                </div>

                <div>
                    <label for="segment" class="block text-sm font-semibold text-text mb-2">
                        Segment
                    </label>
                    <select
                        id="segment"
                        v-model="form.segment"
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
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
                </div>
            </div>

            <!-- Row 2: Name + Email -->
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

            <!-- Row 3: Company + Website -->
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

            <!-- Row 4: Budget + Preferred Contact -->
            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="budget" class="block text-sm font-semibold text-text mb-2">
                        Budget
                    </label>
                    <select
                        id="budget"
                        v-model="form.budget"
                        class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        <option value="">Select...</option>
                        <option
                            v-for="budget in budgets"
                            :key="budget.value"
                            :value="budget.value"
                        >
                            {{ budget.label }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-text mb-2">
                        Preferred Contact
                    </label>
                    <div class="flex gap-2">
                        <button
                            v-for="method in contactMethods"
                            :key="method.value"
                            type="button"
                            @click="form.preferred_contact = method.value"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                                form.preferred_contact === method.value
                                    ? 'bg-primary text-white'
                                    : 'bg-surface border border-border text-muted hover:text-text'
                            ]"
                        >
                            {{ method.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Row 5: Message -->
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
                    placeholder="Tell us about your goals, questions, or how we can help..."
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
                <span v-if="form.processing">Sending...</span>
                <span v-else>Send message</span>
            </button>

            <!-- Disclaimer -->
            <p class="text-xs text-muted mt-4 text-center">
                Outcomes vary. No guaranteed links. We'll respond with next steps.
            </p>
        </form>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    inquiryTypes: {
        type: Array,
        required: true,
    },
    segments: {
        type: Array,
        required: true,
    },
    budgets: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['success']);

const contactMethods = [
    { value: 'email', label: 'Email' },
    { value: 'call', label: 'Call' },
    { value: 'whatsapp', label: 'WhatsApp' },
];

const form = useForm({
    inquiry_type: '',
    name: '',
    email: '',
    company: '',
    website: '',
    segment: '',
    budget: '',
    preferred_contact: 'email',
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
    form.post('/contact', {
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
