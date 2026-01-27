<template>
    <section class="mission-story py-20">
        <div class="marketing-container max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Why we built BacklinkPro
            </h2>
            
            <div class="grid md:grid-cols-2 gap-12">
                <!-- Left: The Problem -->
                <div data-reveal>
                    <h3 class="text-xl font-bold mb-4 text-danger">The Problem</h3>
                    <div class="space-y-4 text-muted">
                        <p>
                            Manual backlink building is slow, expensive, and doesn't scale. Agency services are opaque and hard to audit. Cheap automation tools lack guardrails and evidence—risking penalties and wasted spend.
                        </p>
                        <p>
                            Teams need automation that's <strong class="text-text">safe, reviewable, and auditable</strong>—not black-box services or risky blasts.
                        </p>
                    </div>
                </div>

                <!-- Right: The Solution -->
                <div data-reveal>
                    <h3 class="text-xl font-bold mb-4 text-success">The Solution</h3>
                    <div class="space-y-4 text-muted">
                        <p>
                            BacklinkPro combines automation with <strong class="text-text">guardrails, approvals, and evidence logs</strong>. Every action is tracked, gated by risk thresholds, and can require human review.
                        </p>
                        <p>
                            You control velocity, anchor mix, and whitelists. You see proof for every placement attempt. You can audit and export everything.
                        </p>
                    </div>
                </div>
            </div>

            <!-- What we never do -->
            <div class="mt-12 pt-8 border-t border-border" data-reveal>
                <h3 class="text-lg font-bold mb-4 text-text">What we never do</h3>
                <div class="flex flex-wrap gap-4">
                    <div
                        v-for="(item, idx) in neverDo"
                        :key="idx"
                        class="flex items-center gap-2 px-4 py-2 bg-danger/10 border border-danger/20 rounded-lg"
                    >
                        <svg class="w-5 h-5 text-danger flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm text-muted">{{ item }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const neverDo = [
    'PBNs',
    'Hacked sites',
    'Spam blasts',
];
</script>
