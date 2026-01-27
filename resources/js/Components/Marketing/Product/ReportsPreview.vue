<template>
    <section id="reports" class="reports-preview py-20 bg-surface2 scroll-mt-24">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Reports & Evidence
            </h2>
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div
                    v-for="(report, idx) in reports"
                    :key="idx"
                    data-reveal
                    class="marketing-card p-6"
                >
                    <h3 class="text-lg font-bold mb-4 text-text">{{ report.title }}</h3>
                    <ul class="space-y-2 mb-4">
                        <li
                            v-for="(bullet, bulletIdx) in report.bullets"
                            :key="bulletIdx"
                            class="flex items-start gap-2 text-sm text-muted"
                        >
                            <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ bullet }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="flex flex-wrap gap-4 justify-center" data-reveal>
                <a href="/resources" class="btn-secondary text-lg px-8 py-4">
                    View sample report
                </a>
                <a href="/case-studies" class="btn-secondary text-lg px-8 py-4">
                    See proof
                </a>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    reports: {
        type: Array,
        required: true,
    },
});
</script>
