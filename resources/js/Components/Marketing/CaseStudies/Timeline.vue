<template>
    <section id="timeline" class="timeline py-20">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                Timeline
            </h2>

            <!-- Desktop: Horizontal -->
            <div class="hidden md:block relative" data-reveal>
                <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-border transform -translate-y-1/2 z-0"></div>
                <div class="relative flex justify-between">
                    <div
                        v-for="(phase, idx) in caseStudy.timeline"
                        :key="idx"
                        class="flex-1 text-center"
                    >
                        <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold mx-auto mb-4 relative z-10">
                            {{ idx + 1 }}
                        </div>
                        <h3 class="font-semibold text-text mb-2">{{ phase.title }}</h3>
                        <p class="text-sm text-muted">{{ phase.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Mobile: Vertical -->
            <div class="md:hidden space-y-6">
                <div
                    v-for="(phase, idx) in caseStudy.timeline"
                    :key="idx"
                    data-reveal
                    class="flex items-start gap-4"
                >
                    <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold flex-shrink-0">
                        {{ idx + 1 }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-text mb-1">{{ phase.title }}</h3>
                        <p class="text-sm text-muted">{{ phase.desc }}</p>
                    </div>
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
