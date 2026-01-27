<template>
    <section class="proof-strip py-12 border-y border-border">
        <div class="marketing-container">
            <!-- Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                <div
                    v-for="(metric, idx) in metrics"
                    :key="idx"
                    data-reveal
                    class="text-center"
                >
                    <div class="text-4xl md:text-5xl font-bold mb-2" :class="metric.color || 'text-primary'">
                        <span ref="el => setCounterRef(el, idx)">{{ metric.displayValue }}</span>{{ metric.suffix }}
                    </div>
                    <div class="text-sm text-muted">{{ metric.label }}</div>
                </div>
            </div>

            <!-- Logo Strip -->
            <div data-reveal class="flex items-center justify-center gap-8 md:gap-12 flex-wrap opacity-60">
                <div
                    v-for="(logo, idx) in logos"
                    :key="idx"
                    class="grayscale hover:grayscale-0 transition-all"
                >
                    <div class="h-8 w-24 bg-surface2 rounded flex items-center justify-center text-muted text-xs">
                        {{ logo.name }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    metrics: {
        type: Array,
        required: true,
    },
    logos: {
        type: Array,
        default: () => [],
    },
});

const counterRefs = ref([]);

const setCounterRef = (el, idx) => {
    if (el) {
        counterRefs.value[idx] = el;
    }
};

const animateCounter = (el, target, suffix = '') => {
    if (!el) return;
    
    const duration = 2000;
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (prefersReducedMotion) {
        el.textContent = target + suffix;
        return;
    }

    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            el.textContent = Math.round(target) + suffix;
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
                if (idx !== -1 && props.metrics[idx]) {
                    const metric = props.metrics[idx];
                    animateCounter(entry.target, metric.value, metric.suffix);
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
