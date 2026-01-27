<template>
    <section id="guardrails" class="guardrails py-20 scroll-mt-24" data-reveal>
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-text">Guardrails</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="marketing-card p-6">
                    <h3 class="text-lg font-bold mb-4 text-text">Controls Used</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(bullet, idx) in guardrails.bullets"
                            :key="idx"
                            class="flex items-start gap-2 text-sm text-muted"
                        >
                            <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ bullet }}</span>
                        </li>
                    </ul>
                </div>
                <div class="marketing-card p-6 bg-primary/10 border border-primary/20">
                    <h3 class="text-lg font-bold mb-2 text-text">Risk Mode</h3>
                    <p class="text-muted">{{ riskMode }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    guardrails: {
        type: Object,
        required: true,
    },
    riskMode: {
        type: String,
        required: true,
    },
});
</script>
