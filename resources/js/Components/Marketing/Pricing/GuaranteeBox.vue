<template>
    <div class="guarantee-box marketing-card p-8 bg-primary/10 border-2 border-primary/20 mb-16" data-reveal>
        <h3 class="text-2xl font-bold mb-6 text-text">{{ guaranteeBox.title }}</h3>
        <ul class="space-y-3 mb-6">
            <li
                v-for="(bullet, idx) in guaranteeBox.bullets"
                :key="idx"
                class="flex items-start gap-3 text-muted"
            >
                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ bullet }}</span>
            </li>
        </ul>
        <p class="text-sm text-muted italic mb-4">{{ guaranteeBox.note }}</p>
        <a href="/security" class="text-primary hover:underline text-sm font-semibold">
            Learn more about Security & Trust →
        </a>
    </div>
</template>

<script setup>
const props = defineProps({
    guaranteeBox: {
        type: Object,
        required: true,
    },
});
</script>
