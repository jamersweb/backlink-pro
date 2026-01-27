<template>
    <section id="evidence" class="evidence-pack-preview py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Evidence Pack
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Complete transparency with evidence logs for every placement.
            </p>

            <div class="max-w-4xl mx-auto space-y-6">
                <div
                    v-for="(sample, idx) in evidenceSamples"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-text mb-2">Placement URL</h3>
                            <a :href="sample.url" target="_blank" class="text-primary hover:underline text-sm break-all">
                                {{ sample.url }}
                            </a>
                            <div class="mt-4">
                                <div class="text-xs text-muted mb-1">Timestamp</div>
                                <div class="text-sm text-text">{{ sample.date }}</div>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-text mb-2">Screenshot Preview</h3>
                            <div class="aspect-video bg-surface rounded border border-border flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-4xl mb-2">📸</div>
                                    <p class="text-xs text-muted">Screenshot placeholder</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-border">
                        <h3 class="font-semibold text-text mb-2">HTML Snippet</h3>
                        <code class="block p-3 bg-surface rounded text-xs text-muted overflow-x-auto">
                            {{ sample.snippet }}
                        </code>
                    </div>
                </div>
            </div>

            <div class="mt-12 text-center" data-reveal>
                <div class="flex flex-wrap gap-4 justify-center">
                    <button @click="showSampleModal = true" class="btn-secondary">
                        View Sample Report
                    </button>
                    <button class="btn-ghost">
                        Export CSV
                    </button>
                    <button class="btn-ghost">
                        Export PDF
                    </button>
                </div>
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
import { ref, computed } from 'vue';

const props = defineProps({
    caseStudy: {
        type: Object,
        required: true,
    },
});

const showSampleModal = ref(false);

const evidenceSamples = computed(() => {
    return props.caseStudy.evidenceSamples || [];
});
</script>
