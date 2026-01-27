<template>
    <section class="benefits-grid py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Partner Benefits
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(benefit, idx) in benefits"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <div class="w-12 h-12 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-xl mb-4">
                        {{ idx + 1 }}
                    </div>
                    <h3 class="text-lg font-bold text-text mb-2">{{ benefit.title }}</h3>
                    <p class="text-sm text-muted">{{ benefit.desc }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    benefits: {
        type: Array,
        required: true,
    },
});
</script>
