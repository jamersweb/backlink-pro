<template>
    <section id="faq" class="product-faq py-20 scroll-mt-24">
        <div class="marketing-container max-w-3xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Frequently Asked Questions
            </h2>

            <div class="space-y-4">
                <div
                    v-for="(item, idx) in faqs"
                    :key="idx"
                    data-reveal
                    class="marketing-card"
                >
                    <button
                        @click="toggle(idx)"
                        @keydown.enter="toggle(idx)"
                        @keydown.space.prevent="toggle(idx)"
                        class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-primary rounded-lg p-2 -m-2"
                        :aria-expanded="openIndex === idx"
                        :aria-controls="`product-faq-${idx}`"
                    >
                        <h3 class="text-lg font-semibold text-text pr-4">{{ item.q }}</h3>
                        <svg
                            :class="[
                                'w-6 h-6 text-muted flex-shrink-0 transition-transform',
                                openIndex === idx && 'rotate-180'
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div
                        :id="`product-faq-${idx}`"
                        v-show="openIndex === idx"
                        class="mt-4 pt-4 border-t border-border text-muted"
                    >
                        <p>{{ item.a }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    faqs: {
        type: Array,
        required: true,
    },
});

const openIndex = ref(null);

const toggle = (idx) => {
    openIndex.value = openIndex.value === idx ? null : idx;
};
</script>
