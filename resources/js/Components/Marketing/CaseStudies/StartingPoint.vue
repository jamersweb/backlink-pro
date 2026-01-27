<template>
    <section id="starting-point" class="starting-point py-20 scroll-mt-24" data-reveal>
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-text">Starting Point</h2>
            <div class="marketing-card p-8">
                <ul class="space-y-3">
                    <li
                        v-for="(bullet, idx) in startingPoint.bullets"
                        :key="idx"
                        class="flex items-start gap-3 text-muted"
                    >
                        <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ bullet }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    startingPoint: {
        type: Object,
        required: true,
    },
});
</script>
