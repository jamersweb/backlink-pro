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

        <!-- Trust Chips -->
        <div class="flex flex-wrap gap-2">
            <span class="px-3 py-1 bg-surface2 border border-border rounded-full text-xs text-muted">
                Trust
            </span>
            <span class="px-3 py-1 bg-surface2 border border-border rounded-full text-xs text-muted">
                Evidence
            </span>
            <span class="px-3 py-1 bg-surface2 border border-border rounded-full text-xs text-muted">
                Approvals
            </span>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    testimonials: {
        type: Array,
        default: () => [],
    },
});
</script>
