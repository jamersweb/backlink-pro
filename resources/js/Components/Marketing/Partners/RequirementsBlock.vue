<template>
    <section class="requirements-block py-20">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Who We Work Best With
            </h2>
            
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <!-- Requirements -->
                <div data-reveal>
                    <h3 class="text-xl font-bold mb-6 text-text">Requirements</h3>
                    <div class="space-y-4">
                        <div
                            v-for="(req, idx) in requirements"
                            :key="idx"
                            class="flex items-start gap-3"
                        >
                            <svg class="w-6 h-6 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <h4 class="font-semibold text-text mb-1">{{ req.title }}</h4>
                                <p class="text-sm text-muted">{{ req.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- What We Avoid -->
                <div data-reveal>
                    <h3 class="text-xl font-bold mb-6 text-text">What We Avoid</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(item, idx) in weAvoid"
                            :key="idx"
                            class="flex items-start gap-3"
                        >
                            <svg class="w-6 h-6 text-danger flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-muted">{{ item }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    requirements: {
        type: Array,
        required: true,
    },
});

const weAvoid = [
    'Spam promises',
    'PBNs',
    'Hacked sites',
    'Guaranteed backlinks',
];
</script>
