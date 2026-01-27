<template>
    <td class="px-4 py-3 text-center border-b border-border">
        <div v-if="value === true" class="flex justify-center">
            <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
        </div>
        <div v-else-if="value === false" class="flex justify-center">
            <span class="text-muted">—</span>
        </div>
        <div v-else class="flex justify-center">
            <span
                :class="[
                    'px-2 py-1 text-xs rounded',
                    isPlaceholder(value) ? 'bg-warning/20 text-warning' : 'bg-primary/20 text-primary'
                ]"
                :title="isPlaceholder(value) ? 'Planned / placeholder until shipped' : ''"
            >
                {{ value }}
            </span>
        </div>
    </td>
</template>

<script setup>
const props = defineProps({
    value: {
        type: [Boolean, String],
        required: true,
    },
});

const isPlaceholder = (val) => {
    if (typeof val !== 'string') return false;
    return val.toLowerCase().includes('placeholder') || val.toLowerCase().includes('planned');
};
</script>
