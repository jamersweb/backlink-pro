<template>
    <div v-if="!isDismissed" class="announcement-bar">
        <div class="marketing-container announcement-shell">
            <p>
                <span>New</span>
                AI Link Safety Score + Approval Queue
            </p>
            <button @click="dismiss" aria-label="Dismiss announcement">
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
    z-index: 60;
    padding-top: 0.8rem;
}

.announcement-shell {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-height: 3rem;
    padding: 0.65rem 1rem;
    border-radius: 1rem;
    background: rgba(255, 110, 64, 0.08);
    border: 1px solid rgba(255, 110, 64, 0.16);
    color: rgba(255, 244, 239, 0.82);
    backdrop-filter: blur(14px);
}

.announcement-shell p {
    margin: 0;
    font-size: 0.84rem;
    display: flex;
    align-items: center;
    gap: 0.7rem;
}

.announcement-shell span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.18rem 0.45rem;
    border-radius: 999px;
    background: rgba(255, 110, 64, 0.18);
    color: #ffd4c3;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-size: 0.62rem;
    font-weight: 800;
}

.announcement-shell button {
    color: rgba(255, 244, 239, 0.68);
    background: transparent;
    border: 0;
    padding: 0.25rem;
    cursor: pointer;
}
</style>
