<template>
    <div class="tool-widget-risk-calculator marketing-card p-8 my-12">
        <h3 class="text-2xl font-bold mb-6 text-text">Risk Score Calculator</h3>
        <p class="text-muted mb-8">
            Calculate risk scores for link opportunities based on multiple factors. Lower scores indicate safer opportunities.
        </p>

        <div class="space-y-6">
            <!-- Relevance -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Relevance (0-10): {{ relevance }}
                </label>
                <input
                    v-model.number="relevance"
                    type="range"
                    min="0"
                    max="10"
                    class="w-full"
                />
                <div class="flex justify-between text-xs text-muted mt-1">
                    <span>Low</span>
                    <span>High</span>
                </div>
            </div>

            <!-- Moderation -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Moderation Strictness (0-10): {{ moderation }}
                </label>
                <input
                    v-model.number="moderation"
                    type="range"
                    min="0"
                    max="10"
                    class="w-full"
                />
                <div class="flex justify-between text-xs text-muted mt-1">
                    <span>Lenient</span>
                    <span>Strict</span>
                </div>
            </div>

            <!-- Domain Trust -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Domain Trust (0-10): {{ domainTrust }}
                </label>
                <input
                    v-model.number="domainTrust"
                    type="range"
                    min="0"
                    max="10"
                    class="w-full"
                />
                <div class="flex justify-between text-xs text-muted mt-1">
                    <span>Low</span>
                    <span>High</span>
                </div>
            </div>

            <!-- Velocity -->
            <div>
                <label class="block text-sm font-semibold text-text mb-2">
                    Velocity Level
                </label>
                <select
                    v-model="velocity"
                    class="w-full px-4 py-2 rounded-lg bg-surface border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>

            <!-- Results -->
            <div class="mt-8 p-6 bg-surface2 rounded-lg">
                <div class="text-center mb-4">
                    <div class="text-4xl font-bold mb-2" :class="getRiskColor(riskScore)">
                        {{ riskScore }}
                    </div>
                    <div class="text-sm text-muted">Risk Score (0-100)</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-text mb-2">
                        Recommended: {{ recommendedMode }}
                    </div>
                    <p class="text-sm text-muted">
                        {{ getRecommendation(riskScore) }}
                    </p>
                </div>
            </div>

            <!-- Disclaimer -->
            <div class="mt-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
                <p class="text-xs text-muted">
                    <strong class="text-text">Disclaimer:</strong> This calculator is a placeholder tool for demonstration. Actual risk scoring uses more complex algorithms and real-time data. Outcomes vary. No guaranteed links.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const relevance = ref(7);
const moderation = ref(5);
const domainTrust = ref(6);
const velocity = ref('medium');

const riskScore = computed(() => {
    // Simple calculation: higher values = higher risk
    const baseScore = (10 - relevance.value) * 3 + moderation.value * 2 + (10 - domainTrust.value) * 2;
    const velocityMultiplier = {
        low: 0.8,
        medium: 1.0,
        high: 1.2,
    };
    return Math.min(100, Math.round(baseScore * velocityMultiplier[velocity.value]));
});

const recommendedMode = computed(() => {
    if (riskScore.value < 30) return 'Auto-approve';
    if (riskScore.value < 60) return 'Manual review';
    return 'High-risk review';
});

const getRiskColor = (score) => {
    if (score < 30) return 'text-success';
    if (score < 60) return 'text-warning';
    return 'text-danger';
};

const getRecommendation = (score) => {
    if (score < 30) return 'Low risk. Can be auto-approved per your rules.';
    if (score < 60) return 'Medium risk. Requires manual review before execution.';
    return 'High risk. Requires careful review and may need to be avoided.';
};
</script>
