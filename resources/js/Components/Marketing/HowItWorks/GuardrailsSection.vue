<template>
    <section id="guardrails" class="guardrails-section py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Safety Guardrails
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Every action is gated by safety checks and human oversight.
            </p>

            <div class="grid md:grid-cols-2 gap-12 max-w-4xl mx-auto mb-12">
                <!-- What We Do -->
                <div data-reveal>
                    <h3 class="text-2xl font-bold mb-6 text-success flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        What We Do
                    </h3>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, idx) in guardrails.do"
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

                <!-- What We Never Do -->
                <div data-reveal>
                    <h3 class="text-2xl font-bold mb-6 text-danger flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        What We Never Do
                    </h3>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, idx) in guardrails.never"
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

            <!-- Highlighted Callout -->
            <div class="max-w-3xl mx-auto" data-reveal>
                <div class="marketing-card p-8 bg-primary/10 border-2 border-primary/30">
                    <div class="flex items-start gap-4">
                        <div class="text-4xl">🛡️</div>
                        <div>
                            <h3 class="text-xl font-bold mb-2 text-text">Safety Score Gates Every Action</h3>
                            <p class="text-muted">
                                You control risk thresholds. Actions below your threshold require approval. 
                                Actions above are automatically rejected. Full transparency, zero surprises.
                            </p>
                        </div>
                    </div>
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
});
</script>
