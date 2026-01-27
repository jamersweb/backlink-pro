<template>
    <div class="reading-progress fixed top-0 left-0 right-0 h-1 bg-border z-50">
        <div
            class="h-full bg-primary transition-all duration-150"
            :style="{ width: `${progress}%` }"
        ></div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const progress = ref(0);

const updateProgress = () => {
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    const scrollableHeight = documentHeight - windowHeight;
    
    if (scrollableHeight > 0) {
        progress.value = (scrollTop / scrollableHeight) * 100;
    } else {
        progress.value = 0;
    }
};

let rafId = null;

const handleScroll = () => {
    if (rafId) {
        cancelAnimationFrame(rafId);
    }
    rafId = requestAnimationFrame(updateProgress);
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    updateProgress();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
    if (rafId) {
        cancelAnimationFrame(rafId);
    }
});
</script>
