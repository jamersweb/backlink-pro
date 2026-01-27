<template>
    <section class="how-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Left Column -->
                <div data-reveal>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                        How BacklinkPro Works
                    </h1>
                    <p class="text-xl text-muted mb-8 leading-relaxed">
                        A guardrailed link engine: discover → decide → execute → approve → report.
                    </p>
                    <div class="flex flex-wrap gap-4 mb-6">
                        <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                            Run Free Backlink Plan
                        </a>
                        <button @click="$emit('demo-open')" class="btn-secondary text-lg px-8 py-4">
                            Watch demo
                        </button>
                    </div>
                    <p class="text-sm text-muted">
                        Human approvals • Risk scoring • Evidence logs • No PBNs
                    </p>
                </div>

                <!-- Right Column - Workflow Summary Card -->
                <div data-reveal class="gradient-glow">
                    <div class="marketing-card p-8">
                        <h3 class="text-2xl font-bold mb-6 text-text">Workflow Summary</h3>
                        
                        <div class="space-y-6">
                            <!-- Inputs -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-sm">
                                        1
                                    </div>
                                    <h4 class="font-semibold text-text">Inputs</h4>
                                </div>
                                <p class="text-sm text-muted ml-10">Site + Rules</p>
                            </div>

                            <!-- Engine -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-sm">
                                        2
                                    </div>
                                    <h4 class="font-semibold text-text">Engine</h4>
                                </div>
                                <p class="text-sm text-muted ml-10">AI Decision + Templates</p>
                                <div class="ml-10 mt-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-warning/20 text-warning">
                                        Risk Score: 72
                                    </span>
                                </div>
                            </div>

                            <!-- Outputs -->
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-sm">
                                        3
                                    </div>
                                    <h4 class="font-semibold text-text">Outputs</h4>
                                </div>
                                <ul class="text-sm text-muted ml-10 space-y-1">
                                    <li>• Links + Proof</li>
                                    <li>• Monitoring</li>
                                    <li>• Reports</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
defineEmits(['demo-open']);

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<style scoped>
.how-hero {
    min-height: 80vh;
    display: flex;
    align-items: center;
}
</style>
