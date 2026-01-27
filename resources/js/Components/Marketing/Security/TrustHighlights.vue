<template>
    <section class="trust-highlights py-20">
        <div class="marketing-container">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(point, idx) in trustPoints"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <div class="w-12 h-12 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-xl mb-4">
                        {{ idx + 1 }}
                    </div>
                    <h3 class="text-lg font-bold text-text mb-2">{{ point.title }}</h3>
                    <p class="text-sm text-muted">{{ point.desc }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    trustPoints: {
        type: Array,
        required: true,
    },
});
</script>
