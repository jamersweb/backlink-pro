<template>
    <section class="add-ons py-20">
        <div class="marketing-container max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Add-ons
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div
                    v-for="(addon, idx) in addOns"
                    :key="idx"
                    class="marketing-card p-6"
                    data-reveal
                >
                    <h3 class="text-lg font-bold mb-2 text-text">{{ addon.title }}</h3>
                    <p class="text-sm text-muted mb-4">{{ addon.desc }}</p>
                    <p class="text-sm text-muted italic">{{ addon.price }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    addOns: {
        type: Array,
        required: true,
    },
});
</script>
