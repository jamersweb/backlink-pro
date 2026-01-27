<template>
    <section id="proof" class="proof-evidence py-20 bg-surface2">
        <div class="marketing-container max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Evidence & Reporting for {{ solution.name }}
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Complete transparency with evidence logs for every placement attempt.
            </p>

            <!-- Report Cards -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <!-- Evidence Pack Preview -->
                <div data-reveal class="marketing-card p-6">
                    <h3 class="font-bold text-text mb-4">Evidence Pack Preview</h3>
                    <div class="space-y-3 text-sm text-muted">
                        <div>• Placement URL</div>
                        <div>• Screenshot/snippet</div>
                        <div>• Timestamp + operator</div>
                    </div>
                    <button @click="showSampleModal = true" class="btn-secondary mt-4 w-full text-sm">
                        View Sample
                    </button>
                </div>

                <!-- Monitoring Overview -->
                <div data-reveal class="marketing-card p-6">
                    <h3 class="font-bold text-text mb-4">Monitoring Overview</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted">Live:</span>
                            <span class="text-success font-semibold">85%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Lost:</span>
                            <span class="text-danger font-semibold">10%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Pending:</span>
                            <span class="text-warning font-semibold">5%</span>
                        </div>
                    </div>
                </div>

                <!-- Weekly Summary -->
                <div data-reveal class="marketing-card p-6">
                    <h3 class="font-bold text-text mb-4">Weekly Summary</h3>
                    <div class="space-y-3 text-sm text-muted">
                        <div>• Actions logged: 42</div>
                        <div>• Approvals: 38</div>
                        <div>• Placements: 35</div>
                    </div>
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
                @keydown.esc="showSampleModal = false"
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
    solution: {
        type: Object,
        required: true,
    },
});

const showSampleModal = ref(false);
</script>
