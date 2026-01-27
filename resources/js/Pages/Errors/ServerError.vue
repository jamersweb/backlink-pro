<template>
    <MarketingLayout :meta="meta" :show-announcement="false">
        <div class="min-h-[60vh] flex items-center justify-center py-20 px-4">
            <div class="max-w-2xl w-full">
                <!-- Error Card -->
                <div class="bg-surface border border-border rounded-2xl p-8 md:p-12 shadow-xl relative overflow-hidden">
                    <!-- Subtle border glow effect -->
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-red-500/10 via-transparent to-red-500/10 opacity-50 pointer-events-none"></div>
                    
                    <div class="relative z-10 text-center">
                        <!-- Error Icon -->
                        <div class="mb-6 flex justify-center">
                            <svg class="w-24 h-24 text-red-500/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>

                        <h1 class="text-4xl md:text-5xl font-bold text-text mb-4">
                            Something went wrong
                        </h1>
                        <p class="text-lg text-muted mb-8">
                            We encountered an error while processing your request. Please try again or contact support if the problem persists.
                        </p>

                        <!-- CTAs -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link
                                href="/contact"
                                class="btn-primary px-6 py-3 text-center focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-bg"
                                ref="primaryButtonRef"
                            >
                                Contact Support
                            </Link>
                            <button
                                @click="reload"
                                class="btn-ghost px-6 py-3 text-center focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-bg"
                            >
                                Reload Page
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '../../Layouts/MarketingLayout.vue';


const props = defineProps({
    meta: {
        type: Object,
        default: () => ({
            title: '500 — BacklinkPro',
            description: 'Server error.',
        }),
    },
});

const primaryButtonRef = ref(null);

const reload = () => {
    window.location.reload();
};

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
