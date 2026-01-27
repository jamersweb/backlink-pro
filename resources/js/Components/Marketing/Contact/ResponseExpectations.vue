<template>
    <div class="response-expectations marketing-card p-6" data-reveal>
        <h3 class="text-lg font-bold mb-4 text-text">What Happens Next</h3>
        <ul class="space-y-3">
            <li
                v-for="(item, idx) in expectations"
                :key="idx"
                class="flex items-start gap-3 text-sm text-muted"
            >
                <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ item }}</span>
            </li>
        </ul>
    </div>
</template>

<script setup>
const expectations = [
    'We\'ll review your site + goals',
    'We\'ll recommend the safest workflow + plan',
    'We\'ll share a sample evidence report',
];
</script>
