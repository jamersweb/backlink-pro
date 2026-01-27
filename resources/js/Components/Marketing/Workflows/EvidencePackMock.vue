<template>
    <section id="evidence" class="evidence-pack-mock py-20 scroll-mt-24 bg-surface2" data-reveal>
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-8 text-text">Evidence Pack</h2>
            <div class="marketing-card p-6 bg-primary/10 border-2 border-primary/20">
                <div class="space-y-4">
                    <div>
                        <div class="text-xs font-semibold text-muted mb-1">Target URL</div>
                        <div class="text-sm font-semibold text-text">example.com/article/123</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-muted mb-1">Content/Pitch</div>
                        <div class="bg-surface rounded p-3 text-sm text-muted">
                            [Content snippet placeholder - actual text would appear here]
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div>
                            <div class="text-xs font-semibold text-muted mb-1">Timestamp</div>
                            <span class="px-2 py-1 bg-surface2 text-xs rounded">2026-01-22 14:30 UTC</span>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-muted mb-1">Status</div>
                            <span class="px-2 py-1 bg-success/20 text-success text-xs rounded">Pending</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-muted mb-2">Proof Placeholder</div>
                        <div class="flex gap-2">
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">Screenshot</span>
                            <span class="px-2 py-1 bg-primary/20 text-primary text-xs rounded">HTML</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 text-sm text-muted">
                <p>Fields logged: {{ fields.join(', ') }}</p>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    evidencePack: {
        type: Object,
        required: true,
    },
});

const fields = props.evidencePack.fields || [];
</script>
