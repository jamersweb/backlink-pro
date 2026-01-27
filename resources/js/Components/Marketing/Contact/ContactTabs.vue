<template>
    <div class="contact-tabs">
        <div class="flex gap-2 border-b border-border" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="selectTab(tab.value)"
                :aria-selected="activeTab === tab.value"
                :class="[
                    'px-6 py-3 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                    activeTab === tab.value
                        ? 'border-b-2 border-primary text-primary'
                        : 'text-muted hover:text-text'
                ]"
                role="tab"
                :aria-controls="`tab-${tab.value}`"
                :data-tab="tab.value"
            >
                {{ tab.label }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['update:tab']);

const activeTab = ref('contact');

const tabs = [
    { value: 'contact', label: 'Contact' },
    { value: 'demo', label: 'Book a demo' },
];

const selectTab = (tab) => {
    activeTab.value = tab;
    if (typeof window !== 'undefined') {
        localStorage.setItem('bp_contact_tab', tab);
    }
    emit('update:tab', tab);
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('bp_contact_tab');
        if (saved && ['contact', 'demo'].includes(saved)) {
            activeTab.value = saved;
            emit('update:tab', saved);
        }
    }
});
</script>
