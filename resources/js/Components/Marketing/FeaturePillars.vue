<template>
    <section id="features" class="feature-pillars py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Built for Safety, Scale, and Clarity
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                The Guardrailed Link Engine™ combines AI automation with human oversight.
            </p>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div
                    v-for="(pillar, idx) in pillars"
                    :key="idx"
                    data-reveal
                    class="marketing-card"
                >
                    <div class="text-5xl mb-6">{{ pillar.icon }}</div>
                    <h3 class="text-2xl font-bold mb-4 text-text">{{ pillar.title }}</h3>
                    <ul class="space-y-3 mb-6">
                        <li
                            v-for="(bullet, bIdx) in pillar.bullets"
                            :key="bIdx"
                            class="flex items-start gap-3 text-muted"
                        >
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ bullet }}</span>
                        </li>
                    </ul>
                    <a href="#" class="text-primary hover:underline font-semibold text-sm">
                        Learn more →
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const pillars = [
    {
        icon: '🛡️',
        title: 'Safety',
        bullets: [
            'Risk scoring for every opportunity',
            'Blacklist/whitelist domain control',
            'Anchor distribution rules',
        ],
    },
    {
        icon: '⚡',
        title: 'Scale',
        bullets: [
            'Automated workflow execution',
            'Multi-project management',
            'Unlimited opportunity discovery',
        ],
    },
    {
        icon: '📊',
        title: 'Clarity',
        bullets: [
            'Evidence logs for every link',
            'Real-time monitoring & alerts',
            'Transparent reporting dashboard',
        ],
    },
];
</script>
