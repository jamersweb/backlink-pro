<template>
    <section id="evidence" class="evidence-block py-20 scroll-mt-24 bg-surface2" data-reveal>
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-text">Evidence</h2>
            <div class="space-y-6 mb-8">
                <div
                    v-for="(item, idx) in evidence.items"
                    :key="idx"
                    class="marketing-card p-6"
                >
                    <h3 class="text-lg font-bold mb-2 text-text">{{ item.label }}</h3>
                    <p class="text-sm text-muted">{{ item.desc }}</p>
                </div>
            </div>
            <div class="marketing-card p-6 bg-primary/10 border border-primary/20">
                <h3 class="text-lg font-bold mb-4 text-text">Sample Evidence Pack</h3>
                <div class="space-y-3">
                    <div class="p-3 bg-surface rounded">
                        <div class="text-sm font-semibold text-text mb-1">example.com/comment/123</div>
                        <div class="text-xs text-muted mb-2">2026-01-22 14:30 UTC</div>
                        <div class="flex gap-2 mb-2">
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">Screenshot</span>
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">HTML</span>
                        </div>
                        <div class="text-xs text-muted">Status: Live</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    evidence: {
        type: Object,
        required: true,
    },
});
</script>
