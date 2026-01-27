<template>
    <div class="tool-widget-anchor-mix-planner marketing-card p-8 my-12">
        <h3 class="text-2xl font-bold mb-6 text-text">Anchor Mix Planner</h3>
        <p class="text-muted mb-8">
            Plan and validate anchor text distributions for safe, natural link profiles.
        </p>

        <div class="space-y-6">
            <!-- Brand -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Brand (%): {{ brand }}
                </label>
                <input
                    v-model.number="brand"
                    type="range"
                    min="0"
                    max="100"
                    class="w-full"
                />
            </div>

            <!-- Partial -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Partial (%): {{ partial }}
                </label>
                <input
                    v-model.number="partial"
                    type="range"
                    min="0"
                    max="100"
                    class="w-full"
                />
            </div>

            <!-- Exact -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Exact (%): {{ exact }}
                </label>
                <input
                    v-model.number="exact"
                    type="range"
                    min="0"
                    max="100"
                    class="w-full"
                />
            </div>

            <!-- Generic -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Generic (%): {{ generic }}
                </label>
                <input
                    v-model.number="generic"
                    type="range"
                    min="0"
                    max="100"
                    class="w-full"
                />
            </div>

            <!-- Total -->
            <div class="mt-6 p-4 rounded-lg" :class="totalClass">
                <div class="text-center">
                    <div class="text-2xl font-bold mb-2">{{ total }}%</div>
                    <div class="text-sm">{{ totalMessage }}</div>
                </div>
            </div>

            <!-- Recommended Preset -->
            <div v-if="total === 100" class="mt-6 p-6 bg-surface2 rounded-lg">
                <div class="text-center mb-4">
                    <div class="text-lg font-semibold text-text mb-4">Recommended Preset</div>
                    <div class="flex flex-wrap gap-3 justify-center">
                        <span
                            v-for="preset in recommendedPresets"
                            :key="preset"
                            class="px-4 py-2 bg-primary/20 text-primary rounded-lg font-semibold"
                        >
                            {{ preset }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Disclaimer -->
            <div class="mt-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
                <p class="text-xs text-muted">
                    <strong class="text-text">Note:</strong> Recommended mix: 40-50% brand, 20-30% partial, 10-20% exact, 10-20% generic. Outcomes vary. No guaranteed links.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const brand = ref(45);
const partial = ref(25);
const exact = ref(15);
const generic = ref(15);

const total = computed(() => {
    return brand.value + partial.value + exact.value + generic.value;
});

const totalClass = computed(() => {
    if (total.value === 100) return 'bg-success/10 border border-success/20';
    if (total.value < 100) return 'bg-warning/10 border border-warning/20';
    return 'bg-danger/10 border border-danger/20';
});

const totalMessage = computed(() => {
    if (total.value === 100) return '✓ Valid distribution';
    if (total.value < 100) return `⚠ Add ${100 - total.value}% more`;
    return `✗ Reduce by ${total.value - 100}%`;
});

const recommendedPresets = computed(() => {
    const presets = [];
    
    // Conservative: high brand, low exact
    if (brand.value >= 40 && exact.value <= 15) {
        presets.push('Conservative');
    }
    
    // Balanced: balanced mix
    if (brand.value >= 35 && brand.value <= 50 && exact.value >= 10 && exact.value <= 20) {
        presets.push('Balanced');
    }
    
    // Growth: more exact, still safe
    if (exact.value >= 15 && exact.value <= 25 && brand.value >= 30) {
        presets.push('Growth');
    }
    
    return presets.length > 0 ? presets : ['Custom'];
});
</script>
