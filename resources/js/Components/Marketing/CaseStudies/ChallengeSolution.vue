<template>
    <section id="challenge" class="challenge-solution py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                Challenge & Strategy
            </h2>

            <div class="grid md:grid-cols-2 gap-12 max-w-4xl mx-auto">
                <!-- Challenge -->
                <div data-reveal>
                    <h3 class="text-2xl font-bold mb-6 text-text">Challenge</h3>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, idx) in caseStudy.challenge"
                            :key="idx"
                            class="flex items-start gap-3 text-muted"
                        >
                            <svg class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Strategy -->
                <div data-reveal>
                    <h3 class="text-2xl font-bold mb-6 text-text">What We Changed</h3>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, idx) in caseStudy.strategy"
                            :key="idx"
                            class="flex items-start gap-3 text-muted"
                        >
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    caseStudy: {
        type: Object,
        required: true,
    },
});
</script>
