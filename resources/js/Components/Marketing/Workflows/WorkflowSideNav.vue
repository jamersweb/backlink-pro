<template>
    <nav
        v-if="show"
        class="workflow-side-nav fixed left-0 top-1/2 transform -translate-y-1/2 z-40 hidden lg:block"
        aria-label="On this page"
    >
        <div class="bg-surface border border-border rounded-r-lg p-4 shadow-lg">
            <div class="text-xs font-semibold text-muted mb-3 uppercase tracking-wider">
                On This Page
            </div>
            <ul class="space-y-2">
                <li v-for="(item, idx) in navItems" :key="idx">
                    <a
                        :href="`#${item.anchor}`"
                        @click.prevent="scrollTo(item.anchor)"
                        :class="[
                            'block px-3 py-2 rounded text-sm transition-colors',
                            activeAnchor === item.anchor
                                ? 'bg-primary/20 text-primary font-semibold'
                                : 'text-muted hover:text-text hover:bg-surface2'
                        ]"
                    >
                        {{ item.label }}
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: true,
    },
});

const activeAnchor = ref('overview');

const navItems = [
    { anchor: 'overview', label: 'Overview' },
    { anchor: 'steps', label: 'Steps' },
    { anchor: 'controls', label: 'Controls' },
    { anchor: 'safety', label: 'Safety' },
    { anchor: 'evidence', label: 'Evidence' },
    { anchor: 'templates', label: 'Templates' },
    { anchor: 'faq', label: 'FAQ' },
];

const scrollTo = (anchor) => {
    const element = document.getElementById(anchor);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const updateActiveAnchor = () => {
    const sections = navItems.map(item => document.getElementById(item.anchor)).filter(Boolean);
    const scrollPosition = window.scrollY + 150;

    for (let i = sections.length - 1; i >= 0; i--) {
        const section = sections[i];
        if (section.offsetTop <= scrollPosition) {
            activeAnchor.value = navItems[i].anchor;
            break;
        }
    }
};

onMounted(() => {
    window.addEventListener('scroll', updateActiveAnchor);
    updateActiveAnchor();
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateActiveAnchor);
});
</script>

<style scoped>
.workflow-side-nav {
    left: 1rem;
}
</style>
