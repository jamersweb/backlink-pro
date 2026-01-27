<template>
    <section class="partner-tiers py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Partner Tiers
            </h2>
            <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <PartnerTierCard
                    v-for="tier in tiers"
                    :key="tier.id"
                    :tier="tier"
                    @select="selectTier"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
import PartnerTierCard from './PartnerTierCard.vue';

const props = defineProps({
    tiers: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['select-tier']);

const selectTier = (tierId) => {
    emit('select-tier', tierId);
};
</script>
