<template>
    <div class="url-input-card">
        <label for="website" class="block text-sm font-semibold text-text mb-2">
            Website URL <span class="text-danger">*</span>
        </label>
        <input
            id="website"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            type="url"
            placeholder="https://example.com"
            required
            class="w-full px-4 py-3 rounded-lg bg-surface border border-border text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary"
        />
        <p v-if="error" class="text-xs text-danger mt-1">{{ error }}</p>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>
