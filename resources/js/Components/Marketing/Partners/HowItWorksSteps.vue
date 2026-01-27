<template>
    <section class="how-it-works py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                How It Works
            </h2>
            <div class="grid md:grid-cols-4 gap-8">
                <div
                    v-for="(step, idx) in steps"
                    :key="idx"
                    data-reveal
                    class="relative"
                >
                    <!-- Connector line (desktop only) -->
                    <div
                        v-if="idx < steps.length - 1"
                        class="hidden md:block absolute top-12 left-full w-full h-0.5 bg-border"
                        style="width: calc(100% - 2rem);"
                    ></div>
                    
                    <div class="marketing-card p-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center font-bold text-2xl mx-auto mb-4">
                            {{ step.step }}
                        </div>
                        <h3 class="text-lg font-bold text-text mb-2">{{ step.title }}</h3>
                        <p class="text-sm text-muted">{{ step.desc }}</p>
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
