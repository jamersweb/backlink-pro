<template>
    <section id="reliability" class="reliability-block py-20 bg-surface2 scroll-mt-24">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Reliability & monitoring
            </h2>
            <p class="text-center text-muted mb-12 max-w-2xl mx-auto" data-reveal>
                Designed for predictable operations.
            </p>

            <div class="grid md:grid-cols-3 gap-6" data-reveal>
                <div
                    v-for="(card, idx) in cards"
                    :key="idx"
                    class="marketing-card p-6"
                >
                    <h3 class="text-xl font-bold mb-4 text-text">{{ card.title }}</h3>
                    <p class="text-muted text-sm">{{ card.desc }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});

const cards = props.section.cards || [];
</script>
