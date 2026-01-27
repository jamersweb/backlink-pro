<template>
    <section id="templates" class="template-preview py-20 scroll-mt-24" data-reveal>
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-4 text-text">Templates</h2>
            <p class="text-sm text-muted mb-8 italic">Example templates (adapt to context)</p>
            <div class="space-y-6">
                <div
                    v-for="(template, idx) in templates"
                    :key="idx"
                    class="marketing-card p-6"
                >
                    <h3 class="text-lg font-bold mb-3 text-text">{{ template.name }}</h3>
                    <div class="bg-surface2 rounded p-4 mb-4">
                        <p class="text-sm text-muted">{{ template.snippet }}</p>
                    </div>
                    <button
                        class="text-xs text-muted hover:text-text transition-colors"
                        disabled
                        title="Copy feature coming soon"
                    >
                        Copy template (placeholder)
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    templates: {
        type: Array,
        required: true,
    },
});
</script>
