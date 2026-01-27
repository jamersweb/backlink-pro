<template>
    <nav
        v-if="show && sections.length > 0"
        class="article-side-nav fixed left-0 top-1/2 transform -translate-y-1/2 z-40 hidden lg:block"
        aria-label="On this page"
    >
        <div class="bg-surface border border-border rounded-r-lg p-4 shadow-lg">
            <div class="text-xs font-semibold text-muted mb-3 uppercase tracking-wider">
                On This Page
            </div>
            <ul class="space-y-2">
                <li v-for="(section, idx) in sections" :key="idx">
                    <a
                        :href="`#section-${idx}`"
                        @click.prevent="scrollTo(`section-${idx}`)"
                        :class="[
                            'block px-3 py-2 rounded text-sm transition-colors',
                            activeSection === idx
                                ? 'bg-primary/20 text-primary font-semibold'
                                : 'text-muted hover:text-text hover:bg-surface2'
                        ]"
                    >
                        {{ section.h2 }}
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
    sections: {
        type: Array,
        required: true,
    },
});

const activeSection = ref(0);

const scrollTo = (id) => {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const updateActiveSection = () => {
    const sections = props.sections.map((_, idx) => document.getElementById(`section-${idx}`)).filter(Boolean);
    const scrollPosition = window.scrollY + 150;

    for (let i = sections.length - 1; i >= 0; i--) {
        const section = sections[i];
        if (section.offsetTop <= scrollPosition) {
            activeSection.value = i;
            break;
        }
    }
};

onMounted(() => {
    window.addEventListener('scroll', updateActiveSection);
    updateActiveSection();
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateActiveSection);
});
</script>

<style scoped>
.article-side-nav {
    left: 1rem;
}
</style>
