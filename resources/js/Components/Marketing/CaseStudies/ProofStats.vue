<template>
    <section class="proof-stats py-12 border-y border-border">
        <div class="marketing-container">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div
                    v-for="(stat, idx) in stats"
                    :key="idx"
                    data-reveal
                    class="text-center"
                >
                    <div class="text-4xl md:text-5xl font-bold mb-2 text-primary">
                        <span ref="el => setCounterRef(el, idx)">{{ stat.value }}</span>
                    </div>
                    <div class="text-sm text-muted">{{ stat.label }}</div>
                </div>
            </div>
            <p class="text-center text-xs text-muted mt-6">
                Outcomes vary by niche and site authority. No guaranteed links.
            </p>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    stats: {
        type: Array,
        required: true,
    },
});

const counterRefs = ref([]);

const setCounterRef = (el, idx) => {
    if (el) {
        counterRefs.value[idx] = el;
    }
};

const animateCounter = (el, stat) => {
    if (!el || !stat) return;
    
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    // Extract numeric value and suffix (e.g., "100%" -> 100, "%")
    const match = String(stat.value).match(/^(\d+)(.*)$/);
    
    // If value is not numeric or reduced motion, just display it
    if (prefersReducedMotion || !match) {
        el.textContent = stat.value || '';
        return;
    }

    // For numeric values, animate
    const value = parseInt(match[1]);
    const suffix = match[2] || '';
    const duration = 2000;
    const start = 0;
    const increment = value / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= value) {
            el.textContent = Math.round(value) + suffix;
            clearInterval(timer);
        } else {
            el.textContent = Math.round(current) + suffix;
        }
    }, 16);
};

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const idx = counterRefs.value.indexOf(entry.target);
                if (idx !== -1 && props.stats[idx]) {
                    const stat = props.stats[idx];
                    animateCounter(entry.target, stat);
                    observer.unobserve(entry.target);
                }
            }
        });
    }, { threshold: 0.5 });

    counterRefs.value.forEach((el) => {
        if (el) observer.observe(el);
    });
});
</script>
