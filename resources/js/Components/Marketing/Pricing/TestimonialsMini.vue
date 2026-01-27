<template>
    <section class="testimonials-mini py-20 bg-surface2">
        <div class="marketing-container max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Trusted by Teams
            </h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div
                    v-for="(testimonial, idx) in testimonials"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <div class="flex items-center gap-1 mb-4">
                        <svg
                            v-for="i in 5"
                            :key="i"
                            class="w-5 h-5 text-warning"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <p class="text-muted mb-4 italic">"{{ testimonial.quote }}"</p>
                    <div>
                        <div class="font-semibold text-text">{{ testimonial.name }}</div>
                        <div class="text-sm text-muted">{{ testimonial.role }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    testimonials: {
        type: Array,
        required: true,
    },
});
</script>
