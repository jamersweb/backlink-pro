<template>
    <section id="vuln" class="vuln-disclosure py-20 scroll-mt-24">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Vulnerability reporting
            </h2>
            <p class="text-center text-muted mb-12 max-w-2xl mx-auto" data-reveal>
                A clear path for responsible disclosure.
            </p>

            <div class="marketing-card p-8 text-center" data-reveal>
                <h3 class="text-xl font-bold mb-4 text-text">{{ section.cta.label }}</h3>
                <a
                    :href="`mailto:${section.cta.hint.replace(/\(.*?\)/g, '').trim()}`"
                    class="text-primary hover:underline text-lg"
                >
                    {{ section.cta.hint }}
                </a>
                <p v-if="section.note" class="text-xs text-muted mt-4">
                    {{ section.note }}
                </p>
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
</script>
