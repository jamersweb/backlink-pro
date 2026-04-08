<template>
    <section id="modules" class="module-tabs py-20 scroll-mt-24">
        <div class="marketing-container max-w-7xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Core Modules
            </h2>

            <div class="flex flex-col lg:flex-row gap-8">
                <div class="lg:w-64 flex-shrink-0">
                    <div class="hidden lg:block">
                        <div class="marketing-card p-2" role="tablist" aria-label="Product modules">
                            <button
                                v-for="(module, idx) in modules"
                                :key="idx"
                                @click="selectModule(idx)"
                                @keydown.enter="selectModule(idx)"
                                @keydown.arrow-up.prevent="navigateTab(idx, -1)"
                                @keydown.arrow-down.prevent="navigateTab(idx, 1)"
                                :class="[
                                    'w-full text-left px-4 py-3 rounded-lg mb-2 transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                                    activeIndex === idx
                                        ? 'bg-primary text-white'
                                        : 'text-muted hover:text-text hover:bg-surface2'
                                ]"
                                :aria-selected="activeIndex === idx"
                                :aria-controls="`module-panel-${idx}`"
                                role="tab"
                                :id="`module-tab-${idx}`"
                            >
                                {{ module.title }}
                            </button>
                        </div>
                    </div>

                    <div class="lg:hidden overflow-x-auto pb-4 -mx-4 px-4">
                        <div class="flex gap-2 min-w-max" role="tablist" aria-label="Product modules">
                            <button
                                v-for="(module, idx) in modules"
                                :key="idx"
                                @click="selectModule(idx)"
                                @keydown.enter="selectModule(idx)"
                                @keydown.arrow-left.prevent="navigateTab(idx, -1)"
                                @keydown.arrow-right.prevent="navigateTab(idx, 1)"
                                :class="[
                                    'px-4 py-2 rounded-lg whitespace-nowrap transition-colors focus:outline-none focus:ring-2 focus:ring-primary',
                                    activeIndex === idx
                                        ? 'bg-primary text-white'
                                        : 'text-muted hover:text-text bg-surface2'
                                ]"
                                :aria-selected="activeIndex === idx"
                                :aria-controls="`module-panel-${idx}`"
                                role="tab"
                                :id="`module-tab-${idx}`"
                            >
                                {{ module.title }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex-1">
                    <div
                        v-for="(module, idx) in modules"
                        :key="idx"
                        :id="`module-panel-${idx}`"
                        role="tabpanel"
                        :aria-labelledby="`module-tab-${idx}`"
                        v-show="activeIndex === idx"
                    >
                        <ModuleSection :module="module" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ModuleSection from './ModuleSection.vue';

const props = defineProps({
    modules: {
        type: Array,
        required: true,
    },
});

const activeIndex = ref(0);
let rotationInterval = null;

const stopRotation = () => {
    if (rotationInterval) {
        window.clearInterval(rotationInterval);
        rotationInterval = null;
    }
};

const startRotation = () => {
    stopRotation();
    rotationInterval = window.setInterval(() => {
        activeIndex.value = (activeIndex.value + 1) % props.modules.length;
    }, 5000);
};

const restartRotation = () => {
    startRotation();
};

const selectModule = (idx) => {
    activeIndex.value = idx;
    restartRotation();
};

const navigateTab = (currentIdx, direction) => {
    const newIdx = currentIdx + direction;
    if (newIdx >= 0 && newIdx < props.modules.length) {
        activeIndex.value = newIdx;
        restartRotation();
        const tabId = `module-tab-${newIdx}`;
        const tab = document.getElementById(tabId);
        if (tab) {
            tab.focus();
        }
    }
};

onMounted(() => {
    activeIndex.value = 0;
    startRotation();
});

onUnmounted(() => {
    stopRotation();
});
</script>
