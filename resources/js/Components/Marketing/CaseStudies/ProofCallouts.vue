<template>
    <section class="proof-callouts py-20 bg-surface2">
        <div class="marketing-container max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                How we prove it
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div
                    v-for="(callout, idx) in callouts"
                    :key="idx"
                    class="marketing-card p-6"
                    data-reveal
                >
                    <h3 class="text-lg font-bold mb-3 text-text">{{ callout.title }}</h3>
                    <p class="text-sm text-muted mb-4">{{ callout.desc }}</p>
                    <a :href="callout.link" class="text-primary hover:underline text-sm font-semibold">
                        {{ callout.linkText }} →
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const callouts = [
    {
        title: 'Evidence logs',
        desc: 'Every action tracked with placement URL, proof artifacts, and timestamps.',
        link: '/product',
        linkText: 'View product',
    },
    {
        title: 'Approval gates',
        desc: 'Manual approvals for risky actions with full audit trail.',
        link: '/security',
        linkText: 'Learn more',
    },
    {
        title: 'Monitoring/pruning',
        desc: 'Track live/lost placements and adjust targets based on evidence.',
        link: '/product',
        linkText: 'See features',
    },
];
</script>
