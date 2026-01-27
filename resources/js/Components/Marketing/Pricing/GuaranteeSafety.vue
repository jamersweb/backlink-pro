<template>
    <section class="guarantee-safety py-20">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                Safety Pledge & Guarantees
            </h2>

            <div class="grid md:grid-cols-2 gap-12 mb-12">
                <!-- Safety Pledge -->
                <div data-reveal>
                    <h3 class="text-2xl font-bold mb-6 text-success flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Safety Pledge
                    </h3>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, idx) in guarantees.pledge"
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
                            v-for="(item, idx) in guarantees.never"
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

            <!-- Risk Control Callout -->
            <div class="marketing-card p-8 bg-primary/10 border-2 border-primary/30" data-reveal>
                <div class="flex items-start gap-4">
                    <div class="text-4xl">🛡️</div>
                    <div>
                        <h3 class="text-xl font-bold mb-2 text-text">You Control Thresholds and Approvals</h3>
                        <p class="text-muted">
                            Set your risk score thresholds, configure auto-approval rules, and review every action 
                            before execution. Full transparency, zero surprises.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    guarantees: {
        type: Object,
        required: true,
    },
});
</script>
