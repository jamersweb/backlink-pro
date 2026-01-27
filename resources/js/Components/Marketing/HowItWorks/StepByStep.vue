<template>
    <section class="step-by-step py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                Step-by-Step Deep Dive
            </h2>

            <div class="space-y-24">
                <div
                    v-for="(step, idx) in steps"
                    :key="idx"
                    :id="`step-${step.id}`"
                    data-reveal
                    class="scroll-mt-24"
                >
                    <div class="grid md:grid-cols-2 gap-12 items-center">
                        <!-- Left: Text Content -->
                        <div :class="{ 'md:order-2': idx % 2 === 1 }">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold">
                                    {{ step.id }}
                                </div>
                                <h3 class="text-3xl font-bold text-text">{{ step.title }}</h3>
                            </div>
                            <p class="text-lg text-muted mb-6">{{ step.summary }}</p>
                            <ul class="space-y-3">
                                <li
                                    v-for="(bullet, bIdx) in step.bullets"
                                    :key="bIdx"
                                    class="flex items-start gap-3 text-muted"
                                >
                                    <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ bullet }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Right: Screenshot Card -->
                        <div :class="{ 'md:order-1': idx % 2 === 1 }">
                            <div class="marketing-card p-6">
                                <div class="aspect-video bg-surface2 rounded-lg flex items-center justify-center border border-border">
                                    <div class="text-center">
                                        <div class="text-5xl mb-4">🖥️</div>
                                        <p class="text-muted text-sm">{{ step.uiImage }} Preview</p>
                                        <p class="text-xs text-muted mt-2">Screenshot placeholder</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Special Notes -->
                    <div v-if="step.id === 3" class="mt-8 p-6 bg-primary/10 border border-primary/20 rounded-lg">
                        <p class="text-text font-semibold">
                            💡 Human override available: You can manually select workflows or reject AI suggestions at any time.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    steps: {
        type: Array,
        required: true,
    },
});
</script>
