<template>
    <section class="timeline-roadmap py-20 bg-surface2">
        <div class="marketing-container max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Roadmap
            </h2>
            
            <!-- Desktop: Horizontal Timeline -->
            <div class="hidden md:block relative">
                <div class="absolute top-12 left-0 right-0 h-0.5 bg-border"></div>
                <div class="grid grid-cols-3 gap-8 relative">
                    <div
                        v-for="(phase, idx) in timeline"
                        :key="idx"
                        data-reveal
                        class="relative"
                    >
                        <div class="w-24 h-24 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xl mx-auto mb-4 relative z-10">
                            {{ phase.year }}
                        </div>
                        <div class="marketing-card p-6 text-center">
                            <h3 class="text-lg font-bold mb-2 text-text">{{ phase.title }}</h3>
                            <p class="text-sm text-muted mb-2">{{ phase.desc }}</p>
                            <span
                                v-if="phase.desc.includes('placeholder')"
                                class="inline-block px-2 py-1 bg-warning/20 text-warning text-xs rounded"
                            >
                                Planned
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile: Vertical Timeline -->
            <div class="md:hidden space-y-8">
                <div
                    v-for="(phase, idx) in timeline"
                    :key="idx"
                    data-reveal
                    class="relative pl-8 border-l-2 border-border"
                >
                    <div class="absolute -left-3 top-0 w-6 h-6 rounded-full bg-primary"></div>
                    <div class="marketing-card p-6">
                        <div class="text-sm font-semibold text-primary mb-2">{{ phase.year }}</div>
                        <h3 class="text-lg font-bold mb-2 text-text">{{ phase.title }}</h3>
                        <p class="text-sm text-muted mb-2">{{ phase.desc }}</p>
                        <span
                            v-if="phase.desc.includes('placeholder')"
                            class="inline-block px-2 py-1 bg-warning/20 text-warning text-xs rounded"
                        >
                            Planned
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    timeline: {
        type: Array,
        required: true,
    },
});
</script>
