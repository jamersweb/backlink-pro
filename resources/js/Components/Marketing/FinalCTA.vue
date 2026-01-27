<template>
    <section id="final-cta" class="final-cta py-20">
        <div class="marketing-container max-w-3xl mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6 text-text" data-reveal>
                Get your free backlink plan in 60 seconds.
            </h2>
            <p class="text-lg text-muted mb-8" data-reveal>
                No credit card required. See your opportunities and risk score instantly.
            </p>
            
            <div class="max-w-md mx-auto" data-reveal>
                <form @submit.prevent="scrollToHero" class="flex gap-3">
                    <input
                        v-model="url"
                        type="url"
                        placeholder="Enter your website URL"
                        required
                        class="flex-1 px-4 py-4 rounded-lg bg-surface2 border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    <button type="submit" class="btn-primary px-8">
                        Get Plan
                    </button>
                </form>
                <p class="text-sm text-muted mt-4">
                    No PBNs. Human approvals. Full evidence.
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const url = ref('');

const scrollToHero = () => {
    const hero = document.getElementById('hero-section');
    if (hero) {
        hero.scrollIntoView({ behavior: 'smooth' });
        // Set the URL in the hero form if it exists
        setTimeout(() => {
            const heroInput = document.querySelector('#hero-section #url');
            if (heroInput) {
                heroInput.value = url.value;
                heroInput.dispatchEvent(new Event('input'));
            }
        }, 500);
    }
};
</script>
