<template>
    <section id="integrations" class="integrations-teaser py-20">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Connect Your Stack
            </h2>
            <p class="text-center text-muted mb-12" data-reveal>
                Integrate with your existing SEO and marketing tools.
            </p>

            <div class="flex flex-wrap justify-center gap-4 mb-8" data-reveal>
                <div
                    v-for="(integration, idx) in integrations"
                    :key="idx"
                    class="px-6 py-3 bg-surface border border-border rounded-lg text-text font-medium hover:border-primary transition-colors"
                >
                    {{ integration }}
                </div>
            </div>

            <p class="text-center text-sm text-muted" data-reveal>
                More integrations coming soon.
            </p>
        </div>
    </section>
</template>

<script setup>
const integrations = [
    'Google Search Console',
    'Ahrefs',
    'Semrush',
    'Slack',
    'Email Alerts',
];
</script>
