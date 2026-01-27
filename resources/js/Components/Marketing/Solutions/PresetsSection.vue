<template>
    <section id="presets" class="presets-section py-20 bg-surface2">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Presets
            </h2>
            <p class="text-center text-muted mb-12" data-reveal>
                Choose a preset that matches your stage and risk tolerance.
            </p>

            <!-- Preset Pills -->
            <div class="flex flex-wrap gap-3 justify-center mb-8" data-reveal>
                <button
                    v-for="preset in solution.presets"
                    :key="preset.name"
                    @click="selectedPreset = preset.name"
                    :class="[
                        'px-6 py-3 rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                        selectedPreset === preset.name
                            ? 'bg-primary/20 border-primary text-primary'
                            : 'bg-surface border-border text-muted hover:text-text'
                    ]"
                >
                    {{ preset.name }}
                </button>
            </div>

            <!-- Preset Details -->
            <div v-if="currentPreset" class="marketing-card p-8" data-reveal>
                <div class="text-center mb-6">
                    <p class="text-sm text-muted">
                        This preset is best for <strong class="text-text">{{ getStageForPreset(currentPreset.name) }}</strong> stage.
                    </p>
                </div>
                <h3 class="text-xl font-bold mb-4 text-text">{{ currentPreset.name }} Preset</h3>
                <ul class="space-y-3">
                    <li
                        v-for="(bullet, idx) in currentPreset.bullets"
                        :key="idx"
                        class="flex items-start gap-3 text-muted"
                    >
                        <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ bullet }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    solution: {
        type: Object,
        required: true,
    },
});

const selectedPreset = ref(props.solution.presets[1].name); // Default to Balanced

const currentPreset = computed(() => {
    return props.solution.presets.find(p => p.name === selectedPreset.value);
});

const getStageForPreset = (presetName) => {
    const mapping = {
        'Conservative': 'Early',
        'Balanced': 'Growth',
        'Growth': 'Enterprise',
    };
    return mapping[presetName] || 'Growth';
};
</script>
