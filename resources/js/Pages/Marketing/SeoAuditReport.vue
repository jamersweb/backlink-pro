<template>
    <div class="marketing-dark min-h-screen marketing-linked-page seo-audit-page">
        <Head>
            <title>{{ meta.title }}</title>
            <meta name="description" :content="meta.description" />
        </Head>

        <HeaderNav v-if="!showAccountModal" />

        <main id="main-content">
            <section class="seo-audit-hero py-20 md:py-28">
                <div class="marketing-container">
                    <div class="max-w-4xl mx-auto text-center">
                        <div class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-primary mb-6">
                            Technical SEO snapshot
                        </div>
                        <h1 class="text-4xl md:text-6xl font-bold text-text mb-6">
                            SEO Audit Report
                        </h1>
                        <p class="text-xl text-muted mb-4">
                            Comprehensive website analysis with performance metrics, security checks, and actionable insights.
                        </p>
                        <p class="text-muted mb-10 max-w-2xl mx-auto">
                            Run a clean instant audit for crawl issues, performance blockers, and ranking opportunities without leaving the BacklinkPro flow.
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <a href="#audit-form" class="btn-primary inline-flex items-center justify-center rounded-full px-7 py-3.5 text-base font-semibold">
                                Start Free Audit
                            </a>
                            <a href="/pricing" class="btn-secondary inline-flex items-center justify-center rounded-full px-7 py-3.5 text-base font-semibold">
                                View pricing
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <section id="audit-form" class="seo-audit-form-section scroll-mt-28">
                <div class="marketing-container">
                    <div class="max-w-5xl mx-auto">
                        <div class="seo-audit-shell marketing-card rounded-[2rem] p-6 md:p-10">
                            <div class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                                <div>
                                    <div class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.22em] text-primary mb-5">
                                        Free audit
                                    </div>
                                    <h2 class="text-3xl md:text-4xl font-bold text-text mb-4">
                                        Start your SEO audit now
                                    </h2>
                                    <p class="text-base md:text-lg text-muted mb-4">
                                        Enter your website URL and get a comprehensive SEO report instantly.
                                    </p>
                                    <p class="text-sm text-muted mb-8">
                                        Includes overall score, top issues, performance metrics, and actionable recommendations.
                                    </p>

                                    <form @submit.prevent="submitAudit" class="space-y-6">
                                        <div>
                                            <label for="audit-url" class="block text-sm font-medium text-text mb-2.5">
                                                Website URL <span class="text-primary">*</span>
                                            </label>
                                            <input
                                                id="audit-url"
                                                v-model="form.url"
                                                type="url"
                                                placeholder="https://example.com"
                                                class="seo-audit-input w-full rounded-2xl px-5 py-4 text-base text-text placeholder-muted focus:outline-none transition-colors"
                                                :class="{ 'border-red-500': form.errors.url }"
                                                required
                                            />
                                            <p v-if="form.errors.url" class="mt-2 text-sm text-red-400">
                                                {{ form.errors.url }}
                                            </p>
                                        </div>

                                        <button
                                            type="submit"
                                            :disabled="form.processing"
                                            class="w-full btn-primary rounded-full py-4 text-lg font-semibold disabled:opacity-50 disabled:cursor-not-allowed transition-all"
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
                                </div>

                                <div class="seo-audit-side space-y-5">
                                    <div class="seo-audit-note rounded-[1.5rem] p-6">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary mb-3">
                                            Included in the report
                                        </p>
                                        <div class="grid gap-3">
                                            <div class="seo-audit-mini-card">
                                                <span class="seo-audit-dot"></span>
                                                <div>
                                                    <h3 class="text-sm font-semibold text-text">Technical health score</h3>
                                                    <p class="text-sm text-muted">A quick summary of crawlability, performance, and index readiness.</p>
                                                </div>
                                            </div>
                                            <div class="seo-audit-mini-card">
                                                <span class="seo-audit-dot"></span>
                                                <div>
                                                    <h3 class="text-sm font-semibold text-text">Top-priority issues</h3>
                                                    <p class="text-sm text-muted">Surface broken pages, missing metadata, and speed blockers first.</p>
                                                </div>
                                            </div>
                                            <div class="seo-audit-mini-card">
                                                <span class="seo-audit-dot"></span>
                                                <div>
                                                    <h3 class="text-sm font-semibold text-text">Actionable next steps</h3>
                                                    <p class="text-sm text-muted">Review practical fixes your team can ship immediately.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="seo-audit-trust rounded-[1.5rem] p-6">
                                        <div class="grid grid-cols-3 gap-3 text-center">
                                            <div>
                                                <p class="text-xl font-bold text-text">Free</p>
                                                <p class="text-xs uppercase tracking-[0.18em] text-muted">Forever</p>
                                            </div>
                                            <div>
                                                <p class="text-xl font-bold text-text">0</p>
                                                <p class="text-xs uppercase tracking-[0.18em] text-muted">Credit Card</p>
                                            </div>
                                            <div>
                                                <p class="text-xl font-bold text-text">Fast</p>
                                                <p class="text-xs uppercase tracking-[0.18em] text-muted">Results</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <Footer v-if="!showAccountModal" />
        <StickyMobileCTA v-if="!showAccountModal" />

        <div
            v-if="showAccountModal"
            class="seo-account-modal fixed inset-0 z-[120] flex items-center justify-center px-4 py-8 md:px-6 md:py-10"
            @click.self="closeAccountModal"
        >
            <div class="seo-account-backdrop absolute inset-0"></div>
            <div class="seo-account-panel relative z-10 w-full max-w-3xl rounded-[2rem] p-5 md:p-6">
                <button
                    type="button"
                    class="absolute right-5 top-5 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[#f6e6dc] transition hover:bg-white/10"
                    @click="closeAccountModal"
                    aria-label="Close"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6l-12 12" />
                    </svg>
                </button>

                <div class="mb-4 md:mb-5">
                    <h2 class="text-3xl md:text-[2.6rem] font-bold tracking-tight text-white">
                        Create your account
                    </h2>
                    <p class="mt-2 text-base text-[#ccb8ac]">
                        Start building quality backlinks in minutes
                    </p>
                </div>

                <form @submit.prevent="submitAccount" class="space-y-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="modal-name" class="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.08em] text-[#f1e9e4]">
                                Full Name
                            </label>
                            <div class="seo-modal-input-wrap" :class="{ 'seo-modal-input-wrap--active': accountForm.name }">
                                <span class="seo-modal-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <input id="modal-name" v-model="accountForm.name" type="text" placeholder="John Doe" class="seo-modal-input" />
                            </div>
                            <p v-if="accountForm.errors.name" class="mt-2 text-sm text-red-400">
                                {{ accountForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label for="modal-email" class="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.08em] text-[#f1e9e4]">
                                Email Address
                            </label>
                            <div class="seo-modal-input-wrap" :class="{ 'seo-modal-input-wrap--active': accountForm.email }">
                                <span class="seo-modal-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <input id="modal-email" v-model="accountForm.email" type="email" placeholder="you@example.com" class="seo-modal-input" />
                            </div>
                            <p v-if="accountForm.errors.email" class="mt-2 text-sm text-red-400">
                                {{ accountForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label for="modal-password" class="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.08em] text-[#f1e9e4]">
                                Password
                            </label>
                            <div class="seo-modal-input-wrap">
                                <span class="seo-modal-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                                <input id="modal-password" v-model="accountForm.password" :type="showPassword ? 'text' : 'password'" placeholder="........" class="seo-modal-input seo-modal-input--with-toggle" />
                                <button type="button" class="seo-modal-toggle" @click="showPassword = !showPassword" aria-label="Toggle password visibility">
                                    <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="accountForm.errors.password" class="mt-2 text-sm text-red-400">
                                {{ accountForm.errors.password }}
                            </p>
                        </div>

                        <div>
                            <label for="modal-password-confirmation" class="mb-1.5 block text-[11px] font-medium uppercase tracking-[0.08em] text-[#f1e9e4]">
                                Confirm Password
                            </label>
                            <div class="seo-modal-input-wrap">
                                <span class="seo-modal-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </span>
                                <input id="modal-password-confirmation" v-model="accountForm.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" placeholder="........" class="seo-modal-input seo-modal-input--with-toggle" />
                                <button type="button" class="seo-modal-toggle" @click="showConfirmPassword = !showConfirmPassword" aria-label="Toggle confirm password visibility">
                                    <svg v-if="showConfirmPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="accountForm.errors.password_confirmation" class="mt-2 text-sm text-red-400">
                                {{ accountForm.errors.password_confirmation }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="accountForm.processing"
                        class="w-full rounded-full border border-[#ffe0d0] bg-[linear-gradient(180deg,#fff7f2,#ffe7db)] px-6 py-4 text-base font-semibold text-[#16100d] shadow-[0_18px_40px_rgba(255,110,64,0.18)] transition-all duration-200 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span class="inline-flex items-center justify-center gap-2">
                            <template v-if="accountForm.processing">
                                Creating account...
                            </template>
                            <template v-else>
                                Create Account
                            </template>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </span>
                    </button>

                    <div class="relative pt-0.5">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-white/10"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="bg-[#171110] px-3 text-xs uppercase tracking-[0.2em] text-[#8f7f75]">
                                continue with
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a
                            v-for="provider in socialProviders"
                            :key="provider.key"
                            :href="provider.href"
                            :aria-label="`Continue with ${provider.label}`"
                            :title="provider.label"
                            class="group inline-flex h-14 w-14 items-center justify-center rounded-full border border-[#ffffff14] bg-[linear-gradient(180deg,rgba(255,255,255,0.06),rgba(255,255,255,0.025))] text-white transition-all duration-200 hover:-translate-y-0.5 hover:border-[#ff875c]/55 hover:shadow-[0_14px_32px_rgba(0,0,0,0.28)]"
                        >
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#ffffff12] bg-[rgba(0,0,0,0.22)]">
                                <component :is="provider.icon" class="h-5 w-5" />
                            </span>
                        </a>
                    </div>

                    <p class="text-center text-xs leading-5 text-[#9f8c80]">
                        By creating an account, you agree to our
                        <a href="/terms" class="text-[#ff946d] transition-colors hover:text-[#ffb08f]"> Terms </a>
                        and
                        <a href="/privacy-policy" class="text-[#ff946d] transition-colors hover:text-[#ffb08f]"> Privacy Policy</a>.
                    </p>
                </form>

                <p class="mt-3 text-center text-sm text-[#b9a79b]">
                    Already have an account?
                    <a href="/login" class="font-medium text-[#ff946d] transition-colors hover:text-[#ffb08f]"> Sign in </a>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineComponent, h, onBeforeUnmount, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import HeaderNav from '../../Components/Marketing/HeaderNav.vue';
import Footer from '../../Components/Marketing/Footer.vue';
import StickyMobileCTA from '../../Components/Marketing/StickyMobileCTA.vue';
import './shared-linked-page-theme.css';

defineProps({
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
});

const showAccountModal = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const accountForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    audit_url: '',
});

const GoogleIcon = defineComponent({
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'none' }, [
            h('path', { fill: '#EA4335', d: 'M12 10.2v3.9h5.5c-.2 1.2-.9 2.2-1.9 2.9l3.1 2.4c1.8-1.7 2.9-4.1 2.9-7 0-.7-.1-1.4-.2-2.1H12z' }),
            h('path', { fill: '#34A853', d: 'M12 22c2.6 0 4.8-.9 6.4-2.5l-3.1-2.4c-.9.6-2 1-3.3 1-2.5 0-4.7-1.7-5.5-4H3.3v2.5C4.9 19.8 8.1 22 12 22z' }),
            h('path', { fill: '#4A90E2', d: 'M6.5 14.1c-.2-.6-.3-1.3-.3-2.1s.1-1.4.3-2.1V7.4H3.3C2.5 8.9 2 10.4 2 12s.5 3.1 1.3 4.6l3.2-2.5z' }),
            h('path', { fill: '#FBBC05', d: 'M12 5.9c1.4 0 2.7.5 3.7 1.5l2.8-2.8C16.8 3 14.6 2 12 2 8.1 2 4.9 4.2 3.3 7.4l3.2 2.5c.8-2.3 3-4 5.5-4z' }),
        ]);
    },
});

const MicrosoftIcon = defineComponent({
    render() {
        return h('svg', { viewBox: '0 0 24 24' }, [
            h('rect', { x: '3', y: '3', width: '8', height: '8', fill: '#F25022' }),
            h('rect', { x: '13', y: '3', width: '8', height: '8', fill: '#7FBA00' }),
            h('rect', { x: '3', y: '13', width: '8', height: '8', fill: '#00A4EF' }),
            h('rect', { x: '13', y: '13', width: '8', height: '8', fill: '#FFB900' }),
        ]);
    },
});

const GitHubIcon = defineComponent({
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M12 2C6.48 2 2 6.58 2 12.22c0 4.5 2.87 8.31 6.84 9.66.5.1.68-.22.68-.49 0-.24-.01-1.04-.01-1.89-2.78.62-3.37-1.21-3.37-1.21-.45-1.18-1.1-1.49-1.1-1.49-.9-.63.07-.62.07-.62 1 .07 1.52 1.04 1.52 1.04.89 1.56 2.33 1.11 2.9.85.09-.66.35-1.11.63-1.37-2.22-.26-4.56-1.14-4.56-5.08 0-1.12.39-2.04 1.03-2.76-.1-.26-.45-1.3.1-2.7 0 0 .84-.28 2.75 1.05A9.3 9.3 0 0112 6.7c.85 0 1.71.12 2.51.35 1.91-1.33 2.75-1.05 2.75-1.05.55 1.4.2 2.44.1 2.7.64.72 1.03 1.64 1.03 2.76 0 3.95-2.34 4.81-4.57 5.07.36.32.67.93.67 1.88 0 1.35-.01 2.43-.01 2.76 0 .27.18.59.69.49A10.23 10.23 0 0022 12.22C22 6.58 17.52 2 12 2z' }),
        ]);
    },
});

const AppleIcon = defineComponent({
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M16.37 12.37c.02 2.29 2 3.05 2.02 3.06-.02.05-.31 1.1-1.01 2.18-.61.93-1.24 1.86-2.24 1.88-.98.02-1.3-.6-2.42-.6-1.13 0-1.49.58-2.4.62-.96.04-1.7-.98-2.31-1.91C6.73 16.3 5.73 14 5.75 11.8c.01-2.03 1.31-3.93 3.25-3.97.9-.02 1.75.63 2.42.63.66 0 1.9-.77 3.2-.66.55.02 2.1.23 3.1 1.72-.08.05-1.85 1.1-1.83 2.85zM13.98 6.58c.5-.63.84-1.5.75-2.38-.72.03-1.6.5-2.11 1.12-.46.56-.86 1.46-.75 2.31.8.06 1.61-.42 2.11-1.05z' }),
        ]);
    },
});

const FacebookIcon = defineComponent({
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: '#1877F2' }, [
            h('path', { d: 'M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.03 4.39 11.02 10.12 11.93v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.93-1.95 1.88v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.09 24 18.1 24 12.07z' }),
        ]);
    },
});

const socialProviders = [
    { key: 'google', label: 'Google', href: '/auth/google/redirect', icon: GoogleIcon },
    { key: 'microsoft', label: 'Microsoft', href: '/auth/microsoft/redirect', icon: MicrosoftIcon },
    { key: 'github', label: 'GitHub', href: '/auth/github/redirect', icon: GitHubIcon },
    { key: 'apple', label: 'Apple', href: '/auth/apple/redirect', icon: AppleIcon },
    { key: 'facebook', label: 'Facebook', href: '/auth/facebook/redirect', icon: FacebookIcon },
];

const submitAudit = () => {
    form.clearErrors();

    if (!form.url) {
        form.setError('url', 'Website URL is required.');
        return;
    }

    accountForm.clearErrors();
    accountForm.audit_url = form.url;
    showAccountModal.value = true;
};

const closeAccountModal = () => {
    accountForm.clearErrors();
    showAccountModal.value = false;
};

const submitAccount = () => {
    accountForm.clearErrors();
    accountForm.audit_url = form.url;

    accountForm.post('/register', {
        preserveScroll: true,
        onError: () => {
            showAccountModal.value = true;
        },
    });
};

watch(showAccountModal, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>
