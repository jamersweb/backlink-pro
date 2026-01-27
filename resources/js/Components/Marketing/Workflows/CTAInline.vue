<template>
    <section class="cta-inline py-20">
        <div class="marketing-container max-w-3xl mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6 text-text" data-reveal>
                Not sure which workflow fits?
            </h2>
            <p class="text-lg text-muted mb-8" data-reveal>
                Run a free backlink plan to see your opportunities and risk score.
            </p>
            <div class="flex flex-wrap gap-4 justify-center" data-reveal>
                <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                    Run Free Backlink Plan
                </a>
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
