<template>
    <section id="guardrails" class="guardrails-block py-20 scroll-mt-24">
        <div class="marketing-container max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Guardrails & Controls
            </h2>
            
            <div class="grid md:grid-cols-2 gap-8 mb-8">
                <!-- What You Control -->
                <div data-reveal>
                    <h3 class="text-xl font-bold mb-4 text-success">What You Control</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, idx) in guardrails.do"
                            :key="idx"
                            class="flex items-start gap-2 text-muted"
                        >
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <!-- What We Avoid -->
                <div data-reveal>
                    <h3 class="text-xl font-bold mb-4 text-danger">What We Avoid</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, idx) in guardrails.never"
                            :key="idx"
                            class="flex items-start gap-2 text-muted"
                        >
                            <svg class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Callout -->
            <div class="marketing-card p-6 bg-primary/10 border-2 border-primary/20 text-center" data-reveal>
                <p class="text-text font-semibold">
                    Approvals can gate actions above your risk threshold.
                </p>
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
