<template>
    <section id="best-for" class="best-for-block py-20 scroll-mt-24" data-reveal>
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-text">When to Use This Workflow</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="marketing-card p-6 bg-success/10 border border-success/20">
                    <h3 class="text-lg font-bold mb-4 text-success">Best For</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, idx) in bestFor"
                            :key="idx"
                            class="flex items-start gap-2 text-sm text-muted"
                        >
                            <svg class="w-4 h-4 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
                <div class="marketing-card p-6 bg-danger/10 border border-danger/20">
                    <h3 class="text-lg font-bold mb-4 text-danger">Not For</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, idx) in notFor"
                            :key="idx"
                            class="flex items-start gap-2 text-sm text-muted"
                        >
                            <svg class="w-4 h-4 text-danger flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    bestFor: {
        type: Array,
        required: true,
    },
    notFor: {
        type: Array,
        required: true,
    },
});
</script>
