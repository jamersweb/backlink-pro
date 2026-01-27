<template>
    <section id="overview" class="workflow-overview py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                Overview
            </h2>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Best For -->
                <div data-reveal class="marketing-card p-6">
                    <h3 class="text-xl font-bold mb-4 text-text">Best For</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, idx) in workflow.bestFor"
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

                <!-- Timeline -->
                <div data-reveal class="marketing-card p-6">
                    <h3 class="text-xl font-bold mb-4 text-text">Timeline</h3>
                    <p class="text-muted mb-4">{{ workflow.timeline }}</p>
                    <div class="text-sm text-muted">
                        Timeline varies based on site moderation and approval processes.
                    </div>
                </div>

                <!-- What You Get -->
                <div data-reveal class="marketing-card p-6">
                    <h3 class="text-xl font-bold mb-4 text-text">What You Get</h3>
                    <ul class="space-y-2 text-muted">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Human approvals</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Evidence logs</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Link monitoring</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Disclosure -->
            <div class="mt-12 max-w-3xl mx-auto" data-reveal>
                <div class="p-4 bg-warning/10 border border-warning/20 rounded-lg">
                    <p class="text-sm text-muted">
                        <strong class="text-text">Note:</strong> {{ workflow.disclosure }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    workflow: {
        type: Object,
        required: true,
    },
});
</script>
