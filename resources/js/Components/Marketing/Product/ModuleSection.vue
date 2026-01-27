<template>
    <div class="module-section" data-reveal>
        <div class="grid md:grid-cols-2 gap-8 items-start">
            <!-- Left: Text Content -->
            <div>
                <h3 class="text-2xl md:text-3xl font-bold mb-4 text-text">
                    {{ module.title }}
                </h3>
                <p class="text-lg text-muted mb-6 leading-relaxed">
                    {{ module.summary }}
                </p>
                <ul class="space-y-3 mb-6">
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
                        {{ module.learnMore.label }} →
                    </a>
                </div>
            </div>

            <!-- Right: Mock Panel -->
            <div>
                <MockPanel :type="module.mock" />
            </div>
        </div>
    </div>
</template>

<script setup>
import MockPanel from './MockPanel.vue';

const props = defineProps({
    module: {
        type: Object,
        required: true,
    },
});
</script>
