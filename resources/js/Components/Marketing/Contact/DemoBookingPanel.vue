<template>
    <div class="demo-booking-panel">
        <!-- If embed URL is provided -->
        <div v-if="demoEmbedUrl" class="marketing-card p-8">
            <h2 class="text-2xl font-bold mb-6 text-text">Book a Demo</h2>
            <div class="aspect-video bg-surface2 rounded-lg overflow-hidden border border-border">
                <iframe
                    v-if="shouldLoad"
                    :src="demoEmbedUrl"
                    class="w-full h-full"
                    frameborder="0"
                    allow="calendar"
                ></iframe>
                <div v-else class="w-full h-full flex items-center justify-center">
                    <button @click="shouldLoad = true" class="btn-primary">
                        Load Calendar
                    </button>
                </div>
            </div>
        </div>

        <!-- Fallback: No embed URL -->
        <div v-else class="marketing-card p-8 text-center">
            <h2 class="text-2xl font-bold mb-4 text-text">Book a Demo</h2>
            <p class="text-muted mb-6">
                Schedule a personalized demo to see how BacklinkPro works with guardrails, approvals, and evidence logs.
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <button @click="scrollToForm" class="btn-primary text-lg px-8 py-4">
                    Email us for a demo slot
                </button>
                <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-secondary text-lg px-8 py-4">
                    Start with Free Backlink Plan
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    demoEmbedUrl: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['scroll-to-form']);

const shouldLoad = ref(false);

const scrollToForm = () => {
    emit('scroll-to-form');
    // Also trigger tab switch to contact
    const contactTab = document.querySelector('[data-tab="contact"]');
    if (contactTab) {
        contactTab.click();
        // Set inquiry type to sales
        const inquirySelect = document.getElementById('inquiry_type');
        if (inquirySelect) {
            inquirySelect.value = 'sales';
            inquirySelect.dispatchEvent(new Event('change'));
        }
    }
};

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>
