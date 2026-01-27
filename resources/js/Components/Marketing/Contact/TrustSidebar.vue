<template>
    <div class="trust-sidebar space-y-6">
        <!-- You Control -->
        <div class="marketing-card p-6" data-reveal>
            <h3 class="text-lg font-bold mb-4 text-success">You Control</h3>
            <ul class="space-y-2">
                <li
                    v-for="(item, idx) in youControl"
                    :key="idx"
                    class="flex items-start gap-2 text-sm text-muted"
                >
                    <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ item }}</span>
                </li>
            </ul>
        </div>

        <!-- We Avoid -->
        <div class="marketing-card p-6" data-reveal>
            <h3 class="text-lg font-bold mb-4 text-danger">We Avoid</h3>
            <ul class="space-y-2">
                <li
                    v-for="(item, idx) in weAvoid"
                    :key="idx"
                    class="flex items-start gap-2 text-sm text-muted"
                >
                    <svg class="w-5 h-5 text-danger flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ item }}</span>
                </li>
            </ul>
        </div>

        <!-- Security Link -->
        <div class="marketing-card p-4 bg-primary/10 border border-primary/20" data-reveal>
            <Link href="/security" class="flex items-center gap-2 text-primary hover:underline text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Learn more about Security & Trust</span>
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const youControl = [
    'Risk thresholds',
    'Approvals',
    'Velocity caps',
    'Anchor mix',
    'Whitelist/blacklist',
];

const weAvoid = [
    'PBNs',
    'Hacked sites',
    'Spam blasts',
    'Hidden links',
];
</script>
