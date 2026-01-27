<template>
    <section class="security-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="max-w-4xl mx-auto text-center" data-reveal>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                    Security & Trust
                </h1>
                <p class="text-xl text-muted mb-8 leading-relaxed">
                    Guardrails, approvals, evidence logs, and auditability—designed for safe backlink automation.
                </p>
                <div class="flex flex-wrap gap-4 justify-center mb-6">
                    <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                        Run Free Backlink Plan
                    </a>
                    <Link href="/pricing" class="btn-secondary text-lg px-8 py-4">
                        View pricing
                    </Link>
                    <a href="#contact" class="btn-ghost text-lg px-8 py-4">
                        Contact
                    </a>
                </div>
                <p class="text-sm text-muted">
                    No PBNs • Human approvals • Evidence logs • Monitoring
                </p>
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

<style scoped>
.security-hero {
    min-height: 70vh;
    display: flex;
    align-items: center;
}
</style>
