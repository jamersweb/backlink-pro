<template>
    <div class="article-sections">
        <div
            v-for="(section, idx) in sections"
            :key="idx"
            :id="`section-${idx}`"
            class="mb-12 scroll-mt-24"
            data-reveal
        >
            <h2 class="text-3xl font-bold mb-6 text-text">{{ section.h2 }}</h2>
            <div class="space-y-4 text-muted">
                <p
                    v-for="(para, pIdx) in section.p"
                    :key="pIdx"
                    class="leading-relaxed"
                >
                    {{ para }}
                </p>
            </div>

            <!-- Optional Callout -->
            <div v-if="shouldShowCallout(section.h2)" class="mt-6">
                <Callout :type="getCalloutType(section.h2)" />
            </div>
        </div>
    </div>
</template>

<script setup>
import Callout from './Callout.vue';

const props = defineProps({
    sections: {
        type: Array,
        required: true,
    },
});

const shouldShowCallout = (h2) => {
    const calloutKeywords = ['approval', 'evidence', 'safety', 'guardrail'];
    return calloutKeywords.some(keyword => h2.toLowerCase().includes(keyword));
};

const getCalloutType = (h2) => {
    if (h2.toLowerCase().includes('approval')) return 'approval';
    if (h2.toLowerCase().includes('evidence')) return 'evidence';
    return 'safety';
};
</script>
