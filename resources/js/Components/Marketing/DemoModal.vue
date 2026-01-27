<template>
    <div
        v-if="isOpen"
        class="demo-modal fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="close"
    >
        <div class="bg-surface border border-border rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-border">
                <h3 class="text-2xl font-bold text-text">Product Demo</h3>
                <button
                    @click="close"
                    class="text-muted hover:text-text transition-colors p-2"
                    aria-label="Close modal"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-auto p-6">
                <div class="aspect-video bg-surface2 rounded-lg flex items-center justify-center border border-border">
                    <div class="text-center">
                        <div class="text-6xl mb-4">▶️</div>
                        <p class="text-muted">Video placeholder</p>
                        <p class="text-xs text-muted mt-2">Embed your demo video here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

// Focus trap and ESC key
const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.isOpen) {
        close();
    }
};

// Prevent body scroll when modal is open
watch(() => props.isOpen, (isOpen) => {
    if (typeof document !== 'undefined') {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<style scoped>
.demo-modal {
    background-color: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
}
</style>
