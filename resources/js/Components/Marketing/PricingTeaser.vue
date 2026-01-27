<template>
    <section id="pricing" class="pricing-teaser py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Simple, Transparent Pricing
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Start with a free plan, scale as you grow.
            </p>
            
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div
                    v-for="(plan, idx) in plans"
                    :key="idx"
                    data-reveal
                    :class="[
                        'marketing-card',
                        plan.featured && 'border-2 border-primary relative'
                    ]"
                >
                    <div v-if="plan.featured" class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-primary text-white px-4 py-1 rounded-full text-sm font-semibold">
                            Most Popular
                        </span>
                    </div>
                    <h3 class="text-2xl font-bold mb-2 text-text">{{ plan.name }}</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-text">${{ plan.price }}</span>
                        <span class="text-muted">/month</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li
                            v-for="(feature, fIdx) in plan.features"
                            :key="fIdx"
                            class="flex items-start gap-3 text-sm text-muted"
                        >
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ feature }}</span>
                        </li>
                    </ul>
                    <div class="flex flex-col gap-3">
                        <Link :href="`/pricing?plan=${plan.key}`" class="btn-primary w-full text-center">
                            Start Trial
                        </Link>
                        <Link :href="`/pricing?plan=${plan.key}`" class="btn-ghost w-full text-center text-sm">
                            See Full Pricing
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const plans = [
    {
        key: 'starter',
        name: 'Starter',
        price: '99',
        featured: false,
        features: [
            '1 project',
            '50 actions/month',
            'Basic approvals',
            'Evidence logs',
        ],
    },
    {
        key: 'growth',
        name: 'Growth',
        price: '299',
        featured: true,
        features: [
            '5 projects',
            '200 actions/month',
            'Priority approvals',
            'Advanced rules',
        ],
    },
    {
        key: 'pro',
        name: 'Pro',
        price: '599',
        featured: false,
        features: [
            'Unlimited projects',
            'Unlimited actions',
            'Advanced rules + roles',
            'Dedicated support',
        ],
    },
];
</script>
