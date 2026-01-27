<template>
    <div class="risk-mode-selector">
        <label class="block text-sm font-semibold text-text mb-4">
            Risk Mode <span class="text-danger">*</span>
        </label>
        <div class="grid md:grid-cols-3 gap-4" role="radiogroup" aria-label="Risk mode">
            <label
                v-for="mode in modes"
                :key="mode.value"
                :class="[
                    'marketing-card p-4 cursor-pointer transition-all hover:scale-105',
                    modelValue === mode.value && 'ring-2 ring-primary'
                ]"
            >
                <input
                    type="radio"
                    :value="mode.value"
                    :checked="modelValue === mode.value"
                    @change="$emit('update:modelValue', mode.value)"
                    class="sr-only"
                />
                <div class="flex items-center gap-3 mb-2">
                    <div
                        :class="[
                            'w-5 h-5 rounded-full border-2 flex items-center justify-center',
                            modelValue === mode.value
                                ? 'border-primary bg-primary'
                                : 'border-border'
                        ]"
                    >
                        <div
                            v-if="modelValue === mode.value"
                            class="w-2 h-2 rounded-full bg-white"
                        ></div>
                    </div>
                    <span class="font-semibold text-text">{{ mode.label }}</span>
                </div>
            </label>
        </div>
        <p v-if="error" class="text-xs text-danger mt-2">{{ error }}</p>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    modes: {
        type: Array,
        required: true,
    },
    error: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
</style>
