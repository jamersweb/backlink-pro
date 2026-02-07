<template>
    <MarketingLayout :meta="meta || {}">
        <!-- Hero Section -->
        <section class="marketing-container py-20 md:py-32">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-text mb-6">
                    SEO Audit Report
                </h1>
                <p class="text-xl text-muted mb-4">
                    Comprehensive website analysis with performance metrics, security checks, and actionable insights
                </p>
                <p class="text-muted mb-8">
                    Get instant SEO insights with our free audit tool. No credit card required.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                    <a href="#audit-form" class="btn-primary text-lg px-8 py-4 scroll-smooth">
                        Start Free Audit Now
                    </a>
                    <Link href="/dashboard" class="btn-ghost text-lg px-8 py-4">
                        View Dashboard
                    </Link>
                </div>
            </div>
        </section>

        <!-- Quick Start - Audit Form -->
        <section id="audit-form" class="marketing-container py-16 bg-surface/50 scroll-mt-20">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-text mb-4">
                        Start Your SEO Audit Now
                    </h2>
                    <p class="text-lg text-muted mb-2">
                        Enter your website URL and get a comprehensive SEO audit report instantly
                    </p>
                    <p class="text-sm text-muted">
                        Free audit includes: Overall score, top issues, performance metrics, and actionable recommendations
                    </p>
                </div>
                <div class="bg-bg border border-border rounded-xl p-8 shadow-lg">
                    <form @submit.prevent="submitAudit" class="space-y-6">
                        <div>
                            <label for="audit-url" class="block text-sm font-medium text-text mb-2">
                                Website URL <span class="text-red-400">*</span>
                            </label>
                            <input
                                id="audit-url"
                                v-model="form.url"
                                type="url"
                                placeholder="https://example.com"
                                class="w-full px-4 py-3 bg-surface border border-border rounded-lg text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                                :class="{ 'border-red-500': form.errors.url }"
                                required
                            />
                            <p v-if="form.errors.url" class="mt-2 text-sm text-red-400">
                                {{ form.errors.url }}
                            </p>
                        </div>

                        <div>
                            <label for="audit-email" class="block text-sm font-medium text-text mb-2">
                                Email Address <span class="text-muted text-xs">(Optional - to receive report)</span>
                            </label>
                            <input
                                id="audit-email"
                                v-model="form.lead_email"
                                type="email"
                                placeholder="your@email.com"
                                class="w-full px-4 py-3 bg-surface border border-border rounded-lg text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                                :class="{ 'border-red-500': form.errors.lead_email }"
                            />
                            <p v-if="form.errors.lead_email" class="mt-2 text-sm text-red-400">
                                {{ form.errors.lead_email }}
                            </p>
                            <p class="mt-2 text-sm text-muted">
                                We'll send you a link to your audit report when it's ready
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full btn-primary py-4 text-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                        >
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Running Audit...
                            </span>
                            <span v-else>Run Free SEO Audit</span>
                        </button>
                    </form>

                    <div class="mt-6 pt-6 border-t border-border">
                        <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-muted">
                            <div class="flex items-center gap-2">
                                <span>Free Forever</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span>No Credit Card</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span>Instant Results</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        </MarketingLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import MarketingLayout from '../../Layouts/MarketingLayout.vue';

const props = defineProps({
    meta: {
        type: Object,
        default: () => ({
            title: 'SEO Audit Report - BacklinkPro',
            description: 'Comprehensive SEO audit and analysis',
        }),
    },
});

const form = useForm({
    url: '',
    lead_email: '',
});

const submitAudit = () => {
    form.post('/audit', {
        preserveScroll: true,
        onSuccess: () => {
            // Form will redirect automatically via Inertia
            // The controller redirects to audit.show route
        },
        onError: (errors) => {
            console.error('Audit submission error:', errors);
        },
    });
};

onMounted(() => {
    console.log('SeoAuditReport component mounted');
    console.log('Meta prop:', props.meta);
});
</script>
