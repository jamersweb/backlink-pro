<template>
    <div v-if="!isDismissed" class="announcement-bar bg-gradient-to-r from-primary/20 to-lime/20 border-b border-border py-3">
        <div class="marketing-container flex items-center justify-between">
            <p class="text-sm text-text m-0">
                <span class="font-semibold">New:</span> AI Link Safety Score + Approval Queue
            </p>
            <button
                @click="dismiss"
                class="text-muted hover:text-text transition-colors p-1"
                aria-label="Dismiss announcement"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const isDismissed = ref(false);

const dismiss = () => {
    isDismissed.value = true;
    if (typeof window !== 'undefined') {
        localStorage.setItem('bp_announce_closed', '1');
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        isDismissed.value = localStorage.getItem('bp_announce_closed') === '1';
    }
});
</script>

<style scoped>
.announcement-bar {
    position: relative;
    z-index: 50;
}
</style>
