<template>
    <section class="product-carousel py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                See It In Action
            </h2>
            
            <div class="max-w-5xl mx-auto">
                <!-- Tabs -->
                <div class="flex flex-wrap gap-4 justify-center mb-8" data-reveal>
                    <button
                        v-for="(tab, idx) in tabs"
                        :key="idx"
                        @click="activeTab = idx"
                        :class="[
                            'px-6 py-3 rounded-lg font-semibold transition-all',
                            activeTab === idx
                                ? 'bg-primary text-white'
                                : 'bg-surface text-muted hover:text-text border border-border'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Screenshot Area -->
                <div class="marketing-card p-8" data-reveal>
                    <div class="aspect-video bg-surface2 rounded-lg flex items-center justify-center border border-border">
                        <div class="text-center">
                            <div class="text-6xl mb-4">🖥️</div>
                            <p class="text-muted">{{ tabs[activeTab].label }} Screenshot</p>
                            <p class="text-xs text-muted mt-2">Placeholder for product screenshot</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const activeTab = ref(0);

const tabs = [
    { label: 'Opportunity Discovery', key: 'discovery' },
    { label: 'Approval Queue', key: 'approval' },
    { label: 'Evidence Log', key: 'evidence' },
    { label: 'Reporting Dashboard', key: 'reporting' },
];
</script>
