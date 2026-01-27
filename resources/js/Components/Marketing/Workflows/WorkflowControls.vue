<template>
    <section id="controls" class="workflow-controls py-20">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Controls & Settings
            </h2>
            <p class="text-center text-muted mb-12" data-reveal>
                Configure safety rules and approval workflows.
            </p>

            <!-- Controls Table -->
            <div class="marketing-card p-8 mb-8" data-reveal>
                <div class="space-y-4">
                    <div
                        v-for="(control, idx) in workflow.controls"
                        :key="idx"
                        class="flex items-start justify-between py-4 border-b border-border last:border-0"
                    >
                        <div class="flex-1">
                            <div class="font-semibold text-text mb-1">{{ control.label }}</div>
                            <div class="text-sm text-muted">{{ control.value }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Presets -->
            <div class="text-center" data-reveal>
                <div class="text-sm text-muted mb-4">Default Presets</div>
                <div class="flex flex-wrap gap-3 justify-center">
                    <button
                        v-for="preset in presets"
                        :key="preset.value"
                        @click="selectedPreset = preset.value"
                        :class="[
                            'px-4 py-2 rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                            selectedPreset === preset.value
                                ? 'bg-primary/20 border-primary text-primary'
                                : 'bg-surface border-border text-muted hover:text-text'
                        ]"
                    >
                        {{ preset.label }}
                    </button>
                </div>
                <p v-if="selectedPreset" class="mt-4 text-sm text-muted">
                    {{ getPresetDescription(selectedPreset) }}
                </p>
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

const selectedPreset = ref(null);

const presets = [
    { value: 'conservative', label: 'Conservative' },
    { value: 'balanced', label: 'Balanced' },
    { value: 'aggressive', label: 'Aggressive' },
];

const getPresetDescription = (preset) => {
    const descriptions = {
        conservative: 'Lower risk thresholds, stricter relevance requirements, slower velocity.',
        balanced: 'Moderate risk thresholds, standard relevance, balanced velocity.',
        aggressive: 'Higher risk thresholds, broader relevance, faster velocity.',
    };
    return descriptions[preset] || '';
};
</script>
