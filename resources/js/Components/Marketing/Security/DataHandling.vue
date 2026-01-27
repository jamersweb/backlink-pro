<template>
    <section id="data" class="data-handling py-20 bg-surface2 scroll-mt-24">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Data handling
            </h2>
            <p class="text-center text-muted mb-12 max-w-2xl mx-auto" data-reveal>
                What we store and why (keep copy conservative and accurate).
            </p>

            <div class="grid md:grid-cols-2 gap-8 mb-8" data-reveal>
                <div class="marketing-card p-6">
                    <h3 class="text-lg font-bold mb-4 text-text">We may store</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, idx) in weStore"
                            :key="idx"
                            class="flex items-start gap-2 text-muted text-sm"
                        >
                            <span class="text-success">•</span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
                <div class="marketing-card p-6">
                    <h3 class="text-lg font-bold mb-4 text-text">We avoid storing</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, idx) in weAvoid"
                            :key="idx"
                            class="flex items-start gap-2 text-muted text-sm"
                        >
                            <span class="text-danger">✗</span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="marketing-card p-6 bg-primary/10 border-2 border-primary/30" data-reveal>
                <h3 class="font-bold text-text mb-2">Principle</h3>
                <p class="text-muted">Collect only what's needed for transparency, monitoring, and product reliability.</p>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const weStore = props.section.lists?.[0]?.items || [];
const weAvoid = props.section.lists?.[1]?.items || [];
</script>
