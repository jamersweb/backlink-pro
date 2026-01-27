<template>
    <section class="values-block py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Our Values
            </h2>
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div
                    v-for="(value, idx) in values"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <h3 class="text-xl font-bold mb-4 text-text">{{ value.title }}</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(bullet, bulletIdx) in value.bullets"
                            :key="bulletIdx"
                            class="flex items-start gap-2 text-sm text-muted"
                        >
                            <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ bullet }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    values: {
        type: Array,
        required: true,
    },
});
</script>
