<template>
    <div class="min-h-screen bg-gray-50">
        <Head>
            <title>SEO Audit - Free Website SEO Checker</title>
            <meta name="description" content="Get a free SEO audit of your website. Check your SEO score, find issues, and get actionable recommendations." />
        </Head>

        <!-- Simple Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">SEO Audit</h1>
                    <Link v-if="$page.props.auth?.user" href="/dashboard" class="text-sm text-gray-600 hover:text-gray-900">
                        Dashboard
                    </Link>
                    <div v-else class="flex gap-4">
                        <Link href="/login" class="text-sm text-gray-600 hover:text-gray-900">Login</Link>
                        <Link href="/register" class="text-sm text-blue-600 hover:text-blue-700">Sign Up</Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Hero Section -->
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Free SEO Audit Tool
                </h2>
                <p class="text-xl text-gray-600">
                    Get instant insights into your website's SEO performance
                </p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <form @submit.prevent="submit">
                    <div class="mb-6">
                        <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                            Website URL
                        </label>
                        <input
                            id="url"
                            v-model="form.url"
                            type="text"
                            placeholder="https://example.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': form.errors.url }"
                            required
                        />
                        <p v-if="form.errors.url" class="mt-1 text-sm text-red-600">
                            {{ form.errors.url }}
                        </p>
                    </div>

                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email (Optional)
                        </label>
                        <input
                            id="email"
                            v-model="form.lead_email"
                            type="email"
                            placeholder="your@email.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': form.errors.lead_email }"
                        />
                        <p class="mt-1 text-sm text-gray-500">
                            We'll send you the audit results via email
                        </p>
                        <p v-if="form.errors.lead_email" class="mt-1 text-sm text-red-600">
                            {{ form.errors.lead_email }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-blue-600 text-white py-3 px-6 rounded-md font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">Running Audit...</span>
                        <span v-else>Run SEO Audit</span>
                    </button>
                </form>
            </div>

            <!-- Benefits Section -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">What You'll Get</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-4xl mb-4">📊</div>
                        <h4 class="font-semibold text-gray-900 mb-2">Overall Score</h4>
                        <p class="text-sm text-gray-600">
                            Get a comprehensive SEO score with category breakdowns
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl mb-4">🔧</div>
                        <h4 class="font-semibold text-gray-900 mb-2">Fix Plan</h4>
                        <p class="text-sm text-gray-600">
                            Actionable recommendations prioritized by impact and effort
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl mb-4">📄</div>
                        <h4 class="font-semibold text-gray-900 mb-2">PDF Export</h4>
                        <p class="text-sm text-gray-600">
                            Download your audit report as a PDF for sharing
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    url: '',
    lead_email: '',
});

const submit = () => {
    form.post('/audit');
};
</script>
