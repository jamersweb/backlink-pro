<template>
    <div
        class="reading-progress fixed top-0 left-0 right-0 h-1 bg-surface2 z-50"
        v-if="show"
    >
        <div
            class="h-full bg-primary transition-all duration-150"
            :style="{ width: progress + '%' }"
        ></div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: true,
    },
});

const progress = ref(0);

const updateProgress = () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        progress.value = 0;
        return;
    }

    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight - windowHeight;
    const scrolled = window.scrollY;
    progress.value = Math.min(100, (scrolled / documentHeight) * 100);
};

onMounted(() => {
    if (props.show) {
        window.addEventListener('scroll', updateProgress);
        updateProgress();
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateProgress);
});
</script>
