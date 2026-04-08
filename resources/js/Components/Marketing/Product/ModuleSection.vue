<template>
    <div class="module-section" data-reveal>
        <div class="module-section-grid">
            <div class="module-copy">
                <h3 class="text-2xl md:text-3xl font-bold mb-4 text-text">
                    {{ module.title }}
                </h3>
                <p class="text-lg text-muted mb-6 leading-relaxed module-summary">
                    {{ module.summary }}
                </p>
                <ul class="space-y-3 mb-6 module-bullets">
                    <li
                        v-for="(bullet, idx) in module.bullets"
                        :key="idx"
                        class="flex items-start gap-3 text-muted"
                    >
                        <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ bullet }}</span>
                    </li>
                </ul>
                <div v-if="module.learnMore" class="mt-6">
                    <a :href="module.learnMore.url" class="text-primary hover:underline font-semibold">
                        {{ module.learnMore.label }} ->
                    </a>
                </div>
            </div>

            <div class="module-preview">
                <MockPanel :type="module.mock" />
            </div>
        </div>
    </div>
</template>

<script setup>
import MockPanel from './MockPanel.vue';

defineProps({
    module: {
        type: Object,
        required: true,
    },
});
</script>

<style scoped>
.module-section-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
    gap: 2rem;
    align-items: stretch;
}

.module-copy {
    padding: 1rem 0;
}

.module-summary {
    max-width: 32rem;
}

.module-bullets {
    max-width: 30rem;
}

.module-preview {
    min-width: 0;
}

@media (max-width: 900px) {
    .module-section-grid {
        grid-template-columns: 1fr;
    }
}
</style>
