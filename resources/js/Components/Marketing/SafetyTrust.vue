<template>
    <section class="safety-trust py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Quality & Safety First
            </h2>
            
            <div class="grid md:grid-cols-2 gap-12 max-w-4xl mx-auto">
                <!-- What We Do -->
                <div data-reveal>
                    <h3 class="text-2xl font-bold mb-6 text-success flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        What We Do
                    </h3>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, idx) in whatWeDo"
                            :key="idx"
                            class="flex items-start gap-3 text-muted"
                        >
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <!-- What We Never Do -->
                <div data-reveal>
                    <h3 class="text-2xl font-bold mb-6 text-danger flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        What We Never Do
                    </h3>
                    <ul class="space-y-4">
                        <li
                            v-for="(item, idx) in whatWeNeverDo"
                            :key="idx"
                            class="flex items-start gap-3 text-muted"
                        >
                            <svg class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 text-center p-6 bg-primary/10 border border-primary/20 rounded-lg max-w-3xl mx-auto" data-reveal>
                <p class="text-lg font-semibold text-text">
                    Human-in-the-loop approvals for every placement.
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
const whatWeDo = [
    'Real, editorial backlinks from quality sites',
    'Risk scoring before every action',
    'Human review and approval workflow',
    'Evidence logs with screenshots',
    'Link monitoring and health checks',
    'Transparent reporting and analytics',
];

const whatWeNeverDo = [
    'PBNs (Private Blog Networks)',
    'Hacked or compromised sites',
    'Spam blasts or mass submissions',
    'Link farms or directories',
    'Automated comment spam',
    'Paid link schemes',
];
</script>
