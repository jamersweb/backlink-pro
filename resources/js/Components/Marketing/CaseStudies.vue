<template>
    <section class="case-studies py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Real Results
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                See how teams use BacklinkPro to scale their link building.
            </p>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div
                    v-for="(study, idx) in studies"
                    :key="idx"
                    data-reveal
                    class="marketing-card"
                >
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold">
                            {{ study.segment.charAt(0) }}
                        </div>
                        <div>
                            <div class="font-semibold text-text">{{ study.segment }}</div>
                            <div class="text-xs text-muted">{{ study.company }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span
                            v-for="(metric, mIdx) in study.metrics"
                            :key="mIdx"
                            class="px-3 py-1 bg-surface2 rounded-full text-xs font-semibold text-text"
                        >
                            {{ metric }}
                        </span>
                    </div>
                    <p class="text-sm text-muted mb-4">{{ study.narrative }}</p>
                    <a href="#" class="text-primary hover:underline font-semibold text-sm">
                        Read case study →
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const studies = [
    {
        segment: 'SaaS',
        company: 'TechCo',
        metrics: ['+45 links', '87% approval', '3 months'],
        narrative: 'Scaled from 0 to 45 quality backlinks in 3 months with full transparency.',
    },
    {
        segment: 'E-commerce',
        company: 'ShopPro',
        metrics: ['+120 links', '92% approval', '6 months'],
        narrative: 'Automated link building across 4 product categories with risk scoring.',
    },
    {
        segment: 'Agency',
        company: 'MarketingHub',
        metrics: ['+200 links', '85% approval', '12 months'],
        narrative: 'Managed 15 client projects simultaneously with approval workflows.',
    },
];
</script>
