<template>
    <tr class="border-b border-border">
        <td class="py-4 px-4">
            <a
                :href="`/workflows/${item.slug}`"
                class="font-semibold text-text hover:text-primary hover:underline"
            >
                {{ item.title }}
            </a>
        </td>
        <td class="py-4 px-4 text-sm text-muted">{{ item.safetyProfile.risk }}</td>
        <td class="py-4 px-4 text-sm text-muted">{{ item.safetyProfile.timeToFirstSignals }}</td>
        <td class="py-4 px-4 text-sm text-muted">{{ item.safetyProfile.moderationVariance }}</td>
        <td class="py-4 px-4 text-sm text-muted">
            <div class="space-y-1">
                <div v-for="(use, idx) in item.bestFor.slice(0, 2)" :key="idx">
                    {{ use }}
                </div>
            </div>
        </td>
    </tr>
</template>

<script setup>
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>
