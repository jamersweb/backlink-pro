<template>
    <div class="targets-repeater">
        <label class="block text-sm font-semibold text-text mb-2">
            Target Pages (optional)
        </label>
        <p class="text-xs text-muted mb-4">Add up to 5 URLs you want to prioritize.</p>
        <div class="space-y-3">
            <div
                v-for="(target, idx) in targets"
                :key="idx"
                class="flex gap-2"
            >
                <input
                    v-model="targets[idx]"
                    type="url"
                    placeholder="https://example.com/page"
                    class="flex-1 px-4 py-2 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
                    @input="updateTargets"
                />
                <button
                    v-if="targets.length > 1"
                    type="button"
                    @click="removeTarget(idx)"
                    class="px-3 py-2 text-danger hover:bg-danger/10 rounded-lg transition-colors"
                    aria-label="Remove target"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <button
                v-if="targets.length < 5"
                type="button"
                @click="addTarget"
                class="text-sm text-primary hover:underline"
            >
                + Add target page
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);

const targets = ref(props.modelValue.length > 0 ? [...props.modelValue] : ['']);

const addTarget = () => {
    if (targets.value.length < 5) {
        targets.value.push('');
        updateTargets();
    }
};

const removeTarget = (idx) => {
    targets.value.splice(idx, 1);
    updateTargets();
};

const updateTargets = () => {
    const filtered = targets.value.filter(t => t && t.trim());
    emit('update:modelValue', filtered);
};

watch(() => props.modelValue, (newVal) => {
    if (newVal.length === 0 && targets.value.length === 0) {
        targets.value = [''];
    }
});
</script>
