<template>
    <div class="tool-widget-velocity-planner marketing-card p-8 my-12">
        <h3 class="text-2xl font-bold mb-6 text-text">Velocity Planner</h3>
        <p class="text-muted mb-8">
            Calculate recommended action velocity based on project count and target intensity.
        </p>

        <div class="space-y-6">
            <!-- Projects Count -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Number of Projects: {{ projectsCount }}
                </label>
                <input
                    v-model.number="projectsCount"
                    type="range"
                    min="1"
                    max="20"
                    class="w-full"
                />
                <div class="flex justify-between text-xs text-muted mt-1">
                    <span>1</span>
                    <span>20</span>
                </div>
            </div>

            <!-- Target Intensity -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Target Intensity
                </label>
                <select
                    v-model="intensity"
                    class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="conservative">Conservative</option>
                    <option value="balanced">Balanced</option>
                    <option value="aggressive">Aggressive</option>
                </select>
            </div>

            <!-- Results -->
            <div class="mt-8 p-6 bg-surface2 rounded-lg">
                <div class="text-center mb-4">
                    <div class="text-3xl font-bold mb-2 text-text">
                        {{ weeklyRange }}
                    </div>
                    <div class="text-sm text-muted">Recommended Weekly Actions</div>
                </div>
                <div class="text-center mt-4">
                    <div class="text-sm text-muted mb-2">Per Project:</div>
                    <div class="text-lg font-semibold text-text">
                        {{ perProjectRange }} actions/week
                    </div>
                </div>
            </div>

            <!-- Warning -->
            <div v-if="isTooAggressive" class="mt-6 p-4 bg-danger/10 border border-danger/20 rounded-lg">
                <div class="flex items-start gap-3">
                    <div class="text-2xl">⚠️</div>
                    <div>
                        <div class="font-semibold text-text mb-1">Too Aggressive</div>
                        <p class="text-sm text-muted">
                            This velocity may trigger unnatural link spikes. Consider reducing intensity or project count.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Disclaimer -->
            <div class="mt-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
                <p class="text-xs text-muted">
                    <strong class="text-text">Disclaimer:</strong> Recommendations are placeholders. Actual velocity should consider domain authority, niche competitiveness, and historical performance. Outcomes vary. No guaranteed links.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const projectsCount = ref(3);
const intensity = ref('balanced');

const intensityMultipliers = {
    conservative: { min: 5, max: 10 },
    balanced: { min: 10, max: 20 },
    aggressive: { min: 20, max: 35 },
};

const weeklyRange = computed(() => {
    const multiplier = intensityMultipliers[intensity.value];
    const min = multiplier.min * projectsCount.value;
    const max = multiplier.max * projectsCount.value;
    return `${min}-${max}`;
});

const perProjectRange = computed(() => {
    const multiplier = intensityMultipliers[intensity.value];
    return `${multiplier.min}-${multiplier.max}`;
});

const isTooAggressive = computed(() => {
    if (intensity.value !== 'aggressive') return false;
    const totalMax = intensityMultipliers.aggressive.max * projectsCount.value;
    return totalMax > 50;
});
</script>
