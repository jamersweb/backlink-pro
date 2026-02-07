<template>
    <div v-if="shouldShow" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 rounded-lg shadow-lg mb-6">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h3 class="font-semibold text-lg mb-1">{{ title }}</h3>
                <p class="text-sm text-blue-100">{{ message }}</p>
            </div>
            <div class="flex gap-3 ml-4">
                <a
                    v-if="upgradeUrl"
                    :href="upgradeUrl"
                    class="px-4 py-2 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-colors"
                >
                    {{ ctaText }}
                </a>
                <a
                    v-if="organizationId && showManageBilling"
                    :href="`/orgs/${organizationId}/billing/portal`"
                    class="px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-400 transition-colors"
                >
                    Manage Billing
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    reason: {
        type: String,
        default: 'limit', // 'limit', 'past_due', 'feature'
    },
    feature: {
        type: String,
        default: null, // 'pdf_export', 'white_label', etc.
    },
    showManageBilling: {
        type: Boolean,
        default: true,
    },
});

const shouldShow = computed(() => {
    if (props.reason === 'past_due') {
        return props.organization.plan_status === 'past_due';
    }
    
    if (props.reason === 'limit') {
        // Show if on free plan or usage is high
        return props.organization.plan_key === 'free' || props.organization.plan_status !== 'active';
    }
    
    if (props.reason === 'feature') {
        return props.feature && props.organization.plan_key === 'free';
    }
    
    return false;
});

const title = computed(() => {
    if (props.reason === 'past_due') {
        return 'Payment Required';
    }
    if (props.reason === 'limit') {
        return 'Upgrade to Continue';
    }
    if (props.reason === 'feature') {
        return 'Feature Unavailable';
    }
    return 'Upgrade Required';
});

const message = computed(() => {
    if (props.reason === 'past_due') {
        return 'Your subscription payment failed. Please update your payment method to continue using BacklinkPro.';
    }
    if (props.reason === 'limit') {
        return 'You\'ve reached your plan limit. Upgrade to Pro or Agency to continue creating audits.';
    }
    if (props.reason === 'feature') {
        const featureNames = {
            pdf_export: 'PDF Export',
            white_label: 'White-Label Branding',
            custom_domain: 'Custom Domain',
        };
        return `${featureNames[props.feature] || 'This feature'} is only available on Pro or Agency plans.`;
    }
    return 'Upgrade your plan to unlock more features.';
});

const ctaText = computed(() => {
    if (props.reason === 'past_due') {
        return 'Update Payment';
    }
    return 'Upgrade Now';
});

const upgradeUrl = computed(() => {
    if (props.organization.id) {
        return `/orgs/${props.organization.id}/billing/plans`;
    }
    return '/billing/plans';
});

const organizationId = computed(() => props.organization.id);
</script>
