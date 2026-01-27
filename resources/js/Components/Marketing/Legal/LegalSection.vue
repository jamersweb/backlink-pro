<template>
    <section :id="section.id" class="legal-section mb-12 scroll-mt-24">
        <h2 class="text-2xl md:text-3xl font-bold mb-6 text-text" data-reveal>
            {{ section.h2 }}
        </h2>
        <div class="space-y-4 text-muted leading-relaxed" data-reveal>
            <p v-for="(paragraph, idx) in section.p" :key="idx">
                {{ paragraph }}
            </p>
            <ul v-if="section.bullets" class="list-none space-y-2 ml-0">
                <li
                    v-for="(bullet, idx) in section.bullets"
                    :key="idx"
                    class="flex items-start gap-3"
                >
                    <svg class="w-5 h-5 text-primary flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ bullet }}</span>
                </li>
            </ul>
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
</script>
