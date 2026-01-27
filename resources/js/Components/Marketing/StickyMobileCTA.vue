<template>
    <div
        v-if="show"
        class="sticky-mobile-cta fixed bottom-0 left-0 right-0 z-50 md:hidden"
    >
        <div class="bg-surface border-t border-border p-4 shadow-lg">
            <button
                @click="scrollToHero"
                class="btn-primary w-full text-lg py-4"
            >
                Run Free Backlink Plan
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const show = ref(false);

const checkScroll = () => {
    const hero = document.getElementById('hero-section');
    if (hero) {
        const heroBottom = hero.offsetTop + hero.offsetHeight;
        // Show CTA when scrolled past hero section
        show.value = window.scrollY > heroBottom - 100;
    }
};

const scrollToHero = () => {
    const hero = document.getElementById('hero-section');
    if (hero) {
        hero.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(() => {
    window.addEventListener('scroll', checkScroll);
    checkScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', checkScroll);
});
</script>

<style scoped>
.sticky-mobile-cta {
    backdrop-filter: blur(10px);
}
</style>
