<template>
    <section class="workflow-cta-inline py-20">
        <div class="marketing-container max-w-3xl mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6 text-text" data-reveal>
                Generate your free plan for this workflow
            </h2>
            <p class="text-lg text-muted mb-8" data-reveal>
                See how this workflow fits your project with a personalized backlink plan.
            </p>
            <div class="flex flex-wrap gap-4 justify-center" data-reveal>
                <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                    Run Free Backlink Plan
                </a>
                <Link href="/pricing" class="btn-secondary text-lg px-8 py-4">
                    View Pricing
                </Link>
                <Link href="/case-studies" class="btn-ghost text-lg px-8 py-4">
                    View Case Studies
                </Link>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>
