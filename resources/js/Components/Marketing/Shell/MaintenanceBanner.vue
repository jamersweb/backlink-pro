<template>
    <div
        v-if="shouldShow"
        class="maintenance-banner border-b border-border bg-surface/50 backdrop-blur-sm"
        role="alert"
        aria-live="polite"
    >
        <div class="marketing-container py-3">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <!-- Icon based on style -->
                    <div :class="iconClasses" aria-hidden="true">
                        <svg v-if="style === 'info'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else-if="style === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <svg v-else-if="style === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm text-text flex-1 min-w-0">
                        {{ message }}
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <Link
                        v-if="ctaLabel && ctaHref"
                        :href="ctaHref"
                        class="text-sm font-medium text-primary hover:text-primary/80 transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface rounded px-2 py-1"
                    >
                        {{ ctaLabel }}
                    </Link>
                    <button
                        @click="dismiss"
                        class="text-muted hover:text-text transition-colors p-1 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface rounded"
                        aria-label="Dismiss maintenance banner"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const maintenance = computed(() => page.props.maintenance || {});

const isDismissed = ref(false);

const shouldShow = computed(() => {
    if (!maintenance.value?.enabled) {
        return false;
    }
    if (isDismissed.value) {
        return false;
    }
    return true;
});

const style = computed(() => maintenance.value?.style || 'info');
const message = computed(() => maintenance.value?.message || '');
const ctaLabel = computed(() => maintenance.value?.cta_label || '');
const ctaHref = computed(() => maintenance.value?.cta_href || '');

const iconClasses = computed(() => {
    const base = 'flex-shrink-0';
    if (style.value === 'info') {
        return `${base} text-primary`;
    } else if (style.value === 'warning') {
        return `${base} text-yellow-500`;
    } else if (style.value === 'success') {
        return `${base} text-green-500`;
    }
    return base;
});

const dismiss = () => {
    isDismissed.value = true;
    if (typeof window !== 'undefined') {
        const expiry = Date.now() + (24 * 60 * 60 * 1000); // 24 hours
        localStorage.setItem('bp_banner_dismissed', expiry.toString());
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const dismissed = localStorage.getItem('bp_banner_dismissed');
        if (dismissed) {
            const expiry = parseInt(dismissed, 10);
            if (Date.now() < expiry) {
                isDismissed.value = true;
            } else {
                localStorage.removeItem('bp_banner_dismissed');
            }
        }
    }
});
</script>

<style scoped>
.maintenance-banner {
    /* Reserve height to prevent layout shift */
    min-height: 48px;
}
</style>
