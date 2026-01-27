<template>
    <section id="goals" class="goals-section py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Goals & Targets
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                What this solution helps you achieve.
            </p>

            <!-- Goals -->
            <div class="grid md:grid-cols-3 gap-6 mb-12">
                <div
                    v-for="(goal, idx) in solution.goals"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <div class="w-12 h-12 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-lg mb-4">
                        {{ idx + 1 }}
                    </div>
                    <h3 class="text-lg font-bold text-text mb-2">{{ goal }}</h3>
                </div>
            </div>

            <!-- Page Targets -->
            <div class="max-w-4xl mx-auto" data-reveal>
                <div class="marketing-card p-8">
                    <h3 class="text-2xl font-bold mb-6 text-text">Common Page Targets</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div
                            v-for="(target, idx) in solution.pageTargets"
                            :key="idx"
                            class="flex items-start gap-3 text-muted"
                        >
                            <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ target }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    solution: {
        type: Object,
        required: true,
    },
});
</script>
