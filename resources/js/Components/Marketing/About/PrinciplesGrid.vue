<template>
    <section class="principles-grid py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Our Principles
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(principle, idx) in principles"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <div class="w-12 h-12 rounded-full bg-primary/20 text-primary flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(idx)" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-text mb-2">{{ principle.title }}</h3>
                    <p class="text-sm text-muted">{{ principle.desc }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    principles: {
        type: Array,
        required: true,
    },
});

const getIconPath = (idx) => {
    const icons = [
        'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', // Check circle (proof)
        'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', // Shield (guardrails)
        'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', // Star (quality)
        'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', // Users (human)
    ];
    return icons[idx] || icons[0];
};
</script>
