<template>
    <section class="comparison-table py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Compare Your Options
            </h2>
            
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto" data-reveal>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="text-left py-4 px-6 text-text font-semibold">Feature</th>
                            <th
                                v-for="(option, idx) in options"
                                :key="idx"
                                :class="[
                                    'text-center py-4 px-6 font-semibold',
                                    option.highlight ? 'bg-primary/10 text-primary' : 'text-muted'
                                ]"
                            >
                                {{ option.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(feature, fIdx) in features"
                            :key="fIdx"
                            class="border-b border-border"
                        >
                            <td class="py-4 px-6 text-text font-medium">{{ feature.label }}</td>
                            <td
                                v-for="(option, oIdx) in options"
                                :key="oIdx"
                                :class="[
                                    'text-center py-4 px-6',
                                    option.highlight ? 'bg-primary/5' : ''
                                ]"
                            >
                                <span v-if="feature.values[oIdx] === true" class="text-success text-2xl">✓</span>
                                <span v-else-if="feature.values[oIdx] === false" class="text-muted text-2xl">—</span>
                                <span v-else class="text-muted">{{ feature.values[oIdx] }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-6">
                <div
                    v-for="(option, idx) in options"
                    :key="idx"
                    data-reveal
                    :class="[
                        'marketing-card',
                        option.highlight && 'border-2 border-primary'
                    ]"
                >
                    <h3
                        :class="[
                            'text-xl font-bold mb-4',
                            option.highlight ? 'text-primary' : 'text-text'
                        ]"
                    >
                        {{ option.name }}
                    </h3>
                    <div class="space-y-3">
                        <div
                            v-for="(feature, fIdx) in features"
                            :key="fIdx"
                            class="flex items-center justify-between"
                        >
                            <span class="text-sm text-muted">{{ feature.label }}</span>
                            <span class="text-sm font-semibold text-text">
                                <span v-if="feature.values[idx] === true" class="text-success">✓</span>
                                <span v-else-if="feature.values[idx] === false" class="text-muted">—</span>
                                <span v-else>{{ feature.values[idx] }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const options = [
    { name: 'Manual', highlight: false },
    { name: 'Agency', highlight: false },
    { name: 'Cheap Automation', highlight: false },
    { name: 'BacklinkPro', highlight: true },
];

const features = [
    {
        label: 'Cost Predictability',
        values: ['High', 'Low', 'Medium', 'High'],
    },
    {
        label: 'Transparency',
        values: [true, false, false, true],
    },
    {
        label: 'Safety',
        values: [true, 'Unknown', false, true],
    },
    {
        label: 'Speed',
        values: [false, true, true, true],
    },
    {
        label: 'Evidence',
        values: [false, false, false, true],
    },
    {
        label: 'Collaboration',
        values: [false, false, false, true],
    },
];
</script>
