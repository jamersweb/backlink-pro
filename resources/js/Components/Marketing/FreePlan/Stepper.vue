<template>
    <div class="stepper mb-6">
        <div class="flex items-center justify-between">
            <div
                v-for="(step, idx) in steps"
                :key="idx"
                class="flex items-center flex-1"
            >
                <div class="flex items-center">
                    <div
                        :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center font-bold transition-colors',
                            idx < currentStep
                                ? 'bg-primary text-white'
                                : idx === currentStep
                                    ? 'bg-primary text-white'
                                    : 'bg-surface2 text-muted border-2 border-border'
                        ]"
                    >
                        <span v-if="idx < currentStep">✓</span>
                        <span v-else>{{ idx + 1 }}</span>
                    </div>
                    <div class="ml-3 hidden sm:block">
                        <div
                            :class="[
                                'text-sm font-semibold',
                                idx <= currentStep ? 'text-text' : 'text-muted'
                            ]"
                        >
                            {{ step.label }}
                        </div>
                    </div>
                </div>
                <div
                    v-if="idx < steps.length - 1"
                    :class="[
                        'flex-1 h-0.5 mx-4 transition-colors',
                        idx < currentStep ? 'bg-primary' : 'bg-border'
                    ]"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    currentStep: {
        type: Number,
        default: 0,
    },
    steps: {
        type: Array,
        required: true,
    },
});
</script>
