<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div
            v-if="show"
            class="fixed top-4 right-4 z-50 marketing-card p-4 shadow-xl max-w-md"
            role="alert"
            aria-live="polite"
        >
            <div class="flex items-start gap-3">
                <div class="text-2xl">✅</div>
                <div class="flex-1">
                    <h3 class="font-bold text-text mb-1">Plan generated!</h3>
                    <p class="text-sm text-muted">{{ message }}</p>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-muted hover:text-text transition-colors"
                    aria-label="Close"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>

<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    message: {
        type: String,
        default: 'Your plan has been generated.',
    },
});

defineEmits(['close']);
</script>
