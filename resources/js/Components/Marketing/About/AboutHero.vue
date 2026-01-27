<template>
    <section class="about-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Left: Text -->
                <div data-reveal>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                        About BacklinkPro
                    </h1>
                    <p class="text-xl text-muted mb-8 leading-relaxed">
                        BacklinkPro is a guardrailed link engine—built to automate execution while keeping safety, control, and proof at the center.
                    </p>
                    <div class="flex flex-wrap gap-4 mb-6">
                        <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                            Run Free Backlink Plan
                        </a>
                        <a href="/how-it-works" class="btn-secondary text-lg px-8 py-4">
                            How it works
                        </a>
                        <a href="/security" class="btn-secondary text-lg px-8 py-4">
                            Security & Trust
                        </a>
                    </div>
                    <p class="text-sm text-muted">
                        No PBNs • Human approvals • Evidence logs • Monitoring
                    </p>
                </div>

                <!-- Right: What we stand for card -->
                <div data-reveal>
                    <div class="marketing-card p-8">
                        <h3 class="text-xl font-bold mb-4 text-text">What we stand for</h3>
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-start gap-2 text-sm text-muted">
                                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Automation with guardrails</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted">
                                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Evidence for every action</span>
                            </li>
                            <li class="flex items-start gap-2 text-sm text-muted">
                                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Human approvals when needed</span>
                            </li>
                        </ul>
                        <div class="flex items-center gap-3">
                            <div class="px-3 py-1 bg-primary/20 text-primary text-xs font-semibold rounded-full">
                                Risk Score
                            </div>
                            <div class="px-3 py-1 bg-success/20 text-success text-xs font-semibold rounded-full">
                                Evidence logged: 100%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<style scoped>
.about-hero {
    min-height: 70vh;
    display: flex;
    align-items: center;
}
</style>
