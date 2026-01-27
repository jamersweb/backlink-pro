<template>
    <div class="weekly-schedule-table">
        <h3 class="text-xl font-bold mb-6 text-text">4-Week Schedule</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-border">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-text">Week</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-text">Actions</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-text">Focus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(week, idx) in schedule"
                        :key="idx"
                        class="border-b border-border"
                    >
                        <td class="py-3 px-4 text-sm font-semibold text-text">Week {{ week.week }}</td>
                        <td class="py-3 px-4 text-sm text-muted">{{ week.actions }}</td>
                        <td class="py-3 px-4 text-sm text-muted">{{ week.focus }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    schedule: {
        type: Array,
        required: true,
    },
});
</script>
