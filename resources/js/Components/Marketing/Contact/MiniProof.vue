<template>
    <div class="mini-proof">
        <!-- Testimonial Card -->
        <div v-if="testimonials.length > 0" class="marketing-card p-6 mb-6">
            <div class="flex items-start gap-4">
                <div class="text-4xl">💬</div>
                <div>
                    <p class="text-muted mb-3 italic">"{{ testimonials[0].quote }}"</p>
                    <div class="text-sm">
                        <div class="font-semibold text-text">{{ testimonials[0].name }}</div>
                        <div class="text-muted">{{ testimonials[0].role }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo Strip (optional) -->
        <div v-if="logos.length > 0" class="flex items-center gap-4 flex-wrap opacity-60">
            <div
                v-for="(logo, idx) in logos"
                :key="idx"
                class="h-8 w-24 bg-surface2 rounded flex items-center justify-center text-muted text-xs"
            >
                {{ logo.name }}
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    testimonials: {
        type: Array,
        default: () => [],
    },
    logos: {
        type: Array,
        default: () => [],
    },
});
</script>
