<template>
    <section class="metrics-strip py-12 bg-surface2">
        <div class="marketing-container">
            <div class="grid md:grid-cols-3 gap-8">
                <div
                    v-for="(metric, idx) in metrics"
                    :key="idx"
                    data-reveal
                    :data-metric-idx="idx"
                    class="text-center"
                >
                    <div class="text-4xl md:text-5xl font-bold text-primary mb-2">
                        {{ animatedValue(idx) }}
                    </div>
                    <div class="text-sm text-muted">{{ metric.label }}</div>
                </div>
            </div>
            <p class="text-xs text-muted text-center mt-6 max-w-2xl mx-auto">
                Evidence logged refers to tracked actions and proof artifacts (where available).
            </p>
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
});

const animatedValues = ref({});
const hasAnimated = ref({});

const animatedValue = (idx) => {
    return animatedValues.value[idx] !== undefined ? animatedValues.value[idx] : props.metrics[idx].value;
};

const animateCounter = (idx, target) => {
    if (hasAnimated.value[idx]) return;
    
    // Check if it's a number (with optional % or other suffix)
    const numMatch = target.match(/^(\d+)/);
    if (!numMatch) {
        // Not a number, just set it
        animatedValues.value[idx] = target;
        hasAnimated.value[idx] = true;
        return;
    }

    const num = parseInt(numMatch[1]);
    const hasPercent = target.includes('%');
    const suffix = target.replace(/^\d+/, ''); // Get everything after the number
    const duration = 1000;
    const steps = 30;
    const increment = num / steps;
    let current = 0;
    let step = 0;

    const timer = setInterval(() => {
        step++;
        current = Math.min(Math.round(increment * step), num);
        animatedValues.value[idx] = current + suffix;
        
        if (step >= steps) {
            clearInterval(timer);
            hasAnimated.value[idx] = true;
        }
    }, duration / steps);
};

onMounted(() => {
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (prefersReducedMotion) {
        // Set values immediately without animation
        props.metrics.forEach((_, idx) => {
            animatedValues.value[idx] = props.metrics[idx].value;
            hasAnimated.value[idx] = true;
        });
        return;
    }

    // Use IntersectionObserver to trigger animation when visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const idx = parseInt(entry.target.dataset.metricIdx);
                if (!isNaN(idx) && !hasAnimated.value[idx]) {
                    animateCounter(idx, props.metrics[idx].value);
                }
            }
        });
    }, { threshold: 0.5 });

    // Observe each metric element
    setTimeout(() => {
        props.metrics.forEach((_, idx) => {
            const element = document.querySelector(`[data-metric-idx="${idx}"]`);
            if (element) {
                observer.observe(element);
            }
        });
    }, 100);
});
</script>
