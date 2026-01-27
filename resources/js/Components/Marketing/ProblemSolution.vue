<template>
    <section class="problem-solution py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Link building is slow, expensive, or risky.
            </h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div
                    v-for="(problem, idx) in problems"
                    :key="idx"
                    data-reveal
                    class="marketing-card"
                >
                    <div class="text-4xl mb-4">{{ problem.icon }}</div>
                    <h3 class="text-xl font-bold mb-3 text-text">{{ problem.title }}</h3>
                    <p class="text-muted">{{ problem.description }}</p>
                </div>
            </div>
            <p class="text-center mt-12 text-lg text-text" data-reveal>
                <span class="font-semibold text-primary">BacklinkPro</span> automates execution with guardrails.
            </p>
        </div>
    </section>
</template>

<script setup>
const problems = [
    {
        icon: '🐌',
        title: 'Manual Grind',
        description: 'Hours spent researching, reaching out, and following up. Growth is painfully slow.',
    },
    {
        icon: '💰',
        title: 'Retainers',
        description: 'Opaque agencies charge thousands monthly with no transparency or control.',
    },
    {
        icon: '⚠️',
        title: 'Cheap Automation',
        description: 'Spam blasts that risk penalties. No safety checks or quality control.',
    },
];
</script>
