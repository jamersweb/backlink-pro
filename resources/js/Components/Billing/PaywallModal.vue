<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="close">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">{{ title }}</h2>
                    <button @click="close" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="text-gray-600 mb-6">{{ message }}</p>

                <!-- Plan Comparison -->
                <div class="grid md:grid-cols-2 gap-4 mb-6">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-lg mb-2">Free</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>✓ 5 pages per audit</li>
                            <li>✓ 10 audits per day</li>
                            <li>✗ PDF Export</li>
                            <li>✗ White-Label</li>
                        </ul>
                    </div>
                    <div class="border-2 border-blue-500 rounded-lg p-4 bg-blue-50">
                        <h3 class="font-semibold text-lg mb-2">Pro</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>✓ 50 pages per audit</li>
                            <li>✓ 200 audits per day</li>
                            <li>✓ PDF Export</li>
                            <li>✓ 5 Lighthouse pages</li>
                        </ul>
                    </div>
                </div>

                <div class="flex gap-3">
                    <a
                        :href="upgradeUrl"
                        class="flex-1 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors text-center"
                    >
                        Upgrade to Pro
                    </a>
                    <button
                        @click="close"
                        class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Maybe Later
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    feature: {
        type: String,
        required: true,
    },
    organizationId: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const title = computed(() => {
    const titles = {
        pdf_export: 'PDF Export Unavailable',
        white_label: 'White-Label Unavailable',
        custom_domain: 'Custom Domain Unavailable',
        audit_limit: 'Audit Limit Reached',
    };
    return titles[props.feature] || 'Upgrade Required';
});

const message = computed(() => {
    const messages = {
        pdf_export: 'PDF export is only available on Pro and Agency plans. Upgrade now to export professional audit reports.',
        white_label: 'White-label branding is only available on Agency plans. Upgrade to customize your reports with your brand.',
        custom_domain: 'Custom domains are only available on Agency plans. Upgrade to use your own domain for audit reports.',
        audit_limit: 'You\'ve reached your daily audit limit. Upgrade to Pro or Agency to create more audits.',
    };
    return messages[props.feature] || 'This feature requires a paid plan.';
});

const upgradeUrl = computed(() => {
    if (props.organizationId) {
        return `/orgs/${props.organizationId}/billing/plans`;
    }
    return '/billing/plans';
});
</script>
