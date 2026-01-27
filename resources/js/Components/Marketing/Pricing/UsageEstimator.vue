<template>
    <section class="usage-estimator py-20 bg-surface2">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Which Plan Do You Need?
            </h2>
            <p class="text-center text-muted mb-12" data-reveal>
                Use our calculator to find the right plan for your needs.
            </p>

            <div class="marketing-card p-8" data-reveal>
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Inputs -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium mb-3 text-text">
                                Number of Projects
                            </label>
                            <input
                                v-model.number="inputs.projects"
                                type="number"
                                min="1"
                                max="20"
                                class="w-full px-4 py-3 rounded-lg bg-surface2 border border-border text-text focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-3 text-text">
                                Link Velocity
                            </label>
                            <div class="flex gap-3">
                                <button
                                    v-for="vel in velocities"
                                    :key="vel.value"
                                    @click="inputs.velocity = vel.value"
                                    :class="[
                                        'flex-1 px-4 py-3 rounded-lg border transition-colors',
                                        inputs.velocity === vel.value
                                            ? 'bg-primary text-white border-primary'
                                            : 'bg-surface2 border-border text-text hover:border-primary'
                                    ]"
                                >
                                    {{ vel.label }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-3 text-text">
                                Workflows
                            </label>
                            <div class="space-y-2">
                                <label
                                    v-for="workflow in workflows"
                                    :key="workflow.value"
                                    class="flex items-center gap-3 cursor-pointer"
                                >
                                    <input
                                        v-model="inputs.workflows"
                                        :value="workflow.value"
                                        type="checkbox"
                                        class="w-5 h-5 rounded border-border text-primary focus:ring-primary"
                                    />
                                    <span class="text-sm text-muted">{{ workflow.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Output -->
                    <div>
                        <div class="marketing-card p-6 bg-primary/10 border-2 border-primary/30">
                            <h3 class="text-xl font-bold mb-4 text-text">Recommended Plan</h3>
                            <div class="text-4xl font-bold mb-2 text-primary">{{ suggestedPlan.name }}</div>
                            <p class="text-sm text-muted mb-6">{{ suggestedPlan.reason }}</p>
                            
                            <div class="space-y-3 mb-6">
                                <div>
                                    <div class="text-xs text-muted mb-1">Estimated Actions/Month</div>
                                    <div class="text-lg font-semibold text-text">
                                        {{ estimatedActions.min }} - {{ estimatedActions.max }}
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-surface rounded-lg border border-border">
                                <p class="text-xs text-muted">
                                    <strong class="text-text">Note:</strong> Links are not guaranteed. 
                                    Actions are tracked with evidence logs. Actual results may vary.
                                </p>
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

const inputs = ref({
    projects: 1,
    velocity: 'medium',
    workflows: ['comment', 'profile'],
});

const velocities = [
    { value: 'low', label: 'Low', multiplier: 0.8 },
    { value: 'medium', label: 'Medium', multiplier: 1.0 },
    { value: 'high', label: 'High', multiplier: 1.3 },
];

const workflows = [
    { value: 'comment', label: 'Comment Backlinks' },
    { value: 'profile', label: 'Profile Backlinks' },
    { value: 'forum', label: 'Forum Backlinks' },
    { value: 'guest', label: 'Guest Posts' },
];

const estimatedActions = computed(() => {
    const base = inputs.value.projects * 150;
    const velocityMultiplier = velocities.find(v => v.value === inputs.value.velocity)?.multiplier || 1.0;
    const guestMultiplier = inputs.value.workflows.includes('guest') ? 1.25 : 1.0;
    
    const total = Math.round(base * velocityMultiplier * guestMultiplier);
    return {
        min: Math.round(total * 0.8),
        max: Math.round(total * 1.2),
    };
});

const suggestedPlan = computed(() => {
    const maxActions = estimatedActions.value.max;
    
    if (maxActions <= 200) {
        return {
            name: 'Starter',
            reason: 'Perfect for solo marketers and small projects.',
        };
    } else if (maxActions <= 800) {
        return {
            name: 'Growth',
            reason: 'Ideal for growing teams and multiple projects.',
        };
    } else {
        return {
            name: 'Pro',
            reason: 'Best for agencies and high-volume operations.',
        };
    }
});
</script>
