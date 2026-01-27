<template>
    <section class="legal-hero py-12 md:py-16">
        <div class="marketing-container max-w-4xl mx-auto">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 text-text" data-reveal>
                {{ policy.title }}
            </h1>
            <p class="text-muted mb-6" data-reveal>
                Last updated: {{ formattedDate }}
            </p>
            <div class="space-y-4 text-muted leading-relaxed" data-reveal>
                <p v-for="(paragraph, idx) in policy.intro" :key="idx">
                    {{ paragraph }}
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    policy: {
        type: Object,
        required: true,
    },
    lastUpdated: {
        type: String,
        required: true,
    },
});

const formattedDate = computed(() => {
    try {
        const date = new Date(props.lastUpdated);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch (e) {
        return props.lastUpdated;
    }
});
</script>
