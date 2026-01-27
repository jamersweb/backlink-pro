<template>
    <section id="evidence" class="workflow-evidence py-20">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Evidence Pack Preview
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Complete transparency with evidence logs for every placement attempt.
            </p>

            <div class="marketing-card p-8 mb-8" data-reveal>
                <div class="space-y-6">
                    <div>
                        <h3 class="font-semibold text-text mb-2">Placement URL</h3>
                        <a href="#" class="text-primary hover:underline text-sm break-all">
                            https://example-site.com/article/123
                        </a>
                    </div>

                    <div>
                        <h3 class="font-semibold text-text mb-2">Screenshot Preview</h3>
                        <div class="aspect-video bg-surface2 rounded border border-border flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-5xl mb-2">📸</div>
                                <p class="text-xs text-muted">Screenshot placeholder</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold text-text mb-2">Snippet</h3>
                        <code class="block p-3 bg-surface2 rounded text-xs text-muted overflow-x-auto">
                            &lt;a href="https://example.com"&gt;Example Link&lt;/a&gt;
                        </code>
                    </div>

                    <div>
                        <h3 class="font-semibold text-text mb-2">Timestamp</h3>
                        <p class="text-sm text-muted">2024-01-15 14:30:00 UTC</p>
                    </div>
                </div>
            </div>

            <!-- Evidence Types -->
            <div class="grid md:grid-cols-3 gap-6 mb-8" data-reveal>
                <div
                    v-for="(item, idx) in workflow.evidence"
                    :key="idx"
                    class="marketing-card p-6 text-center"
                >
                    <h3 class="font-semibold text-text mb-2">{{ item.label }}</h3>
                    <p class="text-sm text-muted">{{ item.desc }}</p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex flex-wrap gap-4 justify-center" data-reveal>
                <button @click="showSampleModal = true" class="btn-secondary">
                    View Sample Report
                </button>
                <button class="btn-ghost" disabled>
                    Export CSV
                </button>
                <button class="btn-ghost" disabled>
                    Export PDF
                </button>
            </div>

            <!-- Sample Modal -->
            <div
                v-if="showSampleModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75"
                @click.self="showSampleModal = false"
            >
                <div class="bg-surface border border-border rounded-lg max-w-4xl w-full max-h-[90vh] overflow-auto">
                    <div class="flex items-center justify-between p-6 border-b border-border">
                        <h3 class="text-xl font-bold text-text">Sample Evidence Report</h3>
                        <button
                            @click="showSampleModal = false"
                            class="text-muted hover:text-text transition-colors p-2"
                            aria-label="Close modal"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="marketing-card p-6">
                            <div class="text-center text-muted">
                                <div class="text-5xl mb-4">📄</div>
                                <p>Sample report layout placeholder</p>
                                <p class="text-xs mt-2">Full report includes all placements with screenshots, HTML snippets, and metadata.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    workflow: {
        type: Object,
        required: true,
    },
});

const showSampleModal = ref(false);
</script>
