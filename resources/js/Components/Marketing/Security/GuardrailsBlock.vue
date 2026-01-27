<template>
    <section id="automation" class="guardrails-block py-20 scroll-mt-24">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Automation guardrails
            </h2>
            <p class="text-center text-muted mb-12 max-w-2xl mx-auto" data-reveal>
                Automation is constrained by your rules.
            </p>

            <div class="grid md:grid-cols-2 gap-8 mb-8" data-reveal>
                <div class="marketing-card p-6">
                    <h3 class="text-xl font-bold mb-4 text-success">Guardrails included</h3>
                    <ul class="space-y-3">
                        <li
                            v-for="(item, idx) in guardrails"
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
                <div class="marketing-card p-6">
                    <h3 class="text-xl font-bold mb-4 text-danger">What we never do</h3>
                    <ul class="space-y-3">
                        <li
                            v-for="(item, idx) in neverDo"
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
            </div>

            <div v-if="disclaimer" class="mt-8 p-4 bg-warning/10 border border-warning/20 rounded-lg" data-reveal>
                <p class="text-sm text-muted">
                    <strong class="text-text">Note:</strong> {{ disclaimer }}
                </p>
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

const guardrails = props.section.twoCol?.[0]?.items || [];
const neverDo = props.section.twoCol?.[1]?.items || [];
const disclaimer = props.section.disclaimer;
</script>
