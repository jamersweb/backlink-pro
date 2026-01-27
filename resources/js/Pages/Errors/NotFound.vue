<template>
    <MarketingLayout :meta="meta" :show-announcement="false">
        <div class="min-h-[60vh] flex items-center justify-center py-20 px-4">
            <div class="max-w-2xl w-full">
                <!-- Error Card -->
                <div class="bg-surface border border-border rounded-2xl p-8 md:p-12 shadow-xl relative overflow-hidden">
                    <!-- Subtle border glow effect -->
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-primary/10 via-transparent to-primary/10 opacity-50 pointer-events-none"></div>
                    
                    <div class="relative z-10 text-center">
                        <!-- Broken Link Icon -->
                        <div class="mb-6 flex justify-center">
                            <svg class="w-24 h-24 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </div>

                        <h1 class="text-4xl md:text-5xl font-bold text-text mb-4">
                            Page not found
                        </h1>
                        <p class="text-lg text-muted mb-8">
                            The page you're looking for doesn't exist or may have moved.
                        </p>

                        <!-- CTAs -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                            <Link
                                href="/free-backlink-plan"
                                class="btn-primary px-6 py-3 text-center focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-bg"
                                ref="primaryButtonRef"
                            >
                                Run Free Backlink Plan
                            </Link>
                            <Link
                                href="/"
                                class="btn-ghost px-6 py-3 text-center focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-bg"
                            >
                                Go to Home
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Helpful Links -->
                <div class="mt-12">
                    <h2 class="text-xl font-semibold text-text mb-6 text-center">Helpful Links</h2>
                    
                    <!-- Search Input (Optional) -->
                    <div class="mb-6 max-w-md mx-auto">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search links..."
                            class="w-full px-4 py-2 bg-surface border border-border rounded-lg text-text placeholder-muted focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            aria-label="Search helpful links"
                        />
                    </div>

                    <!-- Links Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <Link
                            v-for="link in filteredLinks"
                            :key="link.href"
                            :href="link.href"
                            class="block p-4 bg-surface border border-border rounded-lg hover:border-primary/50 transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-bg"
                        >
                            <span class="text-text font-medium">{{ link.label }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '../../Layouts/MarketingLayout.vue';

const props = defineProps({
    meta: {
        type: Object,
        default: () => ({
            title: '404 — BacklinkPro',
            description: 'Page not found.',
        }),
    },
});

const primaryButtonRef = ref(null);
const searchQuery = ref('');

const helpfulLinks = [
    { label: 'Product', href: '/product' },
    { label: 'Pricing', href: '/pricing' },
    { label: 'Workflows', href: '/workflows' },
    { label: 'Case Studies', href: '/case-studies' },
    { label: 'Contact', href: '/contact' },
];

const filteredLinks = computed(() => {
    if (!searchQuery.value.trim()) {
        return helpfulLinks;
    }
    const query = searchQuery.value.toLowerCase();
    return helpfulLinks.filter(link =>
        link.label.toLowerCase().includes(query) ||
        link.href.toLowerCase().includes(query)
    );
});

onMounted(() => {
    // Focus primary button on mount for keyboard accessibility
    if (primaryButtonRef.value) {
        // Inertia Link components expose $el after mount
        nextTick(() => {
            const el = primaryButtonRef.value?.$el;
            if (el && typeof el.focus === 'function') {
                el.focus();
            }
        });
    }
});
</script>
