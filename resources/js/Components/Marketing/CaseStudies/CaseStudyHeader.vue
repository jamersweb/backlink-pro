<template>
    <section class="case-study-header py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <!-- Breadcrumb -->
            <nav class="mb-8" aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-sm text-muted">
                    <li><Link href="/" class="hover:text-text">Home</Link></li>
                    <li>/</li>
                    <li><Link href="/case-studies" class="hover:text-text">Case Studies</Link></li>
                    <li>/</li>
                    <li class="text-text">{{ caseStudy.title }}</li>
                </ol>
            </nav>

            <div data-reveal>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                    {{ caseStudy.segment }}: Scaling backlinks safely with approvals + evidence
                </h1>

                <!-- Quick Facts -->
                <div class="flex flex-wrap gap-4 mb-8">
                    <div class="px-4 py-2 bg-surface2 rounded-lg">
                        <span class="text-xs text-muted">Segment</span>
                        <div class="font-semibold text-text">{{ caseStudy.segment }}</div>
                    </div>
                    <div class="px-4 py-2 bg-surface2 rounded-lg">
                        <span class="text-xs text-muted">Workflows</span>
                        <div class="font-semibold text-text">{{ caseStudy.workflows.join(', ') }}</div>
                    </div>
                    <div class="px-4 py-2 bg-surface2 rounded-lg">
                        <span class="text-xs text-muted">Timeline</span>
                        <div class="font-semibold text-text">{{ caseStudy.timelineLabel }}</div>
                    </div>
                    <div class="px-4 py-2 bg-surface2 rounded-lg">
                        <span class="text-xs text-muted">Risk Level</span>
                        <div :class="['font-semibold', getRiskClass(caseStudy.riskLevel)]">
                            {{ caseStudy.riskLevel }}
                        </div>
                    </div>
                </div>

                <!-- CTAs -->
                <div class="flex flex-wrap gap-4">
                    <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                        Run Free Backlink Plan
                    </a>
                    <Link href="/pricing" class="btn-secondary text-lg px-8 py-4">
                        View Pricing
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    caseStudy: {
        type: Object,
        required: true,
    },
});

const getRiskClass = (risk) => {
    const classes = {
        'Low': 'text-success',
        'Medium': 'text-warning',
        'High': 'text-danger',
    };
    return classes[risk] || 'text-text';
};

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<style scoped>
.case-study-header {
    min-height: 60vh;
    display: flex;
    align-items: center;
}
</style>
