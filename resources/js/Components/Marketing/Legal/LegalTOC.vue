<template>
    <!-- Desktop: Sticky TOC -->
    <aside class="legal-toc hidden lg:block w-64 flex-shrink-0">
        <div class="sticky top-24">
            <nav class="marketing-card p-6">
                <h3 class="text-sm font-semibold text-text mb-4 uppercase tracking-wide">
                    On this page
                </h3>
                <ul class="space-y-2">
                    <li
                        v-for="(section, idx) in sections"
                        :key="idx"
                    >
                        <a
                            :href="`#${section.id}`"
                            @click.prevent="scrollToSection(section.id)"
                            :class="[
                                'block text-sm transition-colors hover:text-primary',
                                activeSection === section.id ? 'text-primary font-semibold' : 'text-muted'
                            ]"
                        >
                            {{ section.h2 }}
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Mobile: Collapsible TOC -->
    <div class="legal-toc-mobile lg:hidden mb-8">
        <button
            @click="toggleMobileTOC"
            @keydown.enter="toggleMobileTOC"
            @keydown.space.prevent="toggleMobileTOC"
            class="w-full marketing-card p-4 flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-primary"
            :aria-expanded="mobileTOCOpen"
            :aria-controls="'mobile-toc-list'"
        >
            <span class="font-semibold text-text">On this page</span>
            <svg
                :class="[
                    'w-5 h-5 text-muted transition-transform',
                    mobileTOCOpen && 'rotate-180'
                ]"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <div
            v-show="mobileTOCOpen"
            id="mobile-toc-list"
            class="marketing-card p-4 mt-2"
        >
            <ul class="space-y-2">
                <li
                    v-for="(section, idx) in sections"
                    :key="idx"
                >
                    <a
                        :href="`#${section.id}`"
                        @click.prevent="scrollToSection(section.id)"
                        :class="[
                            'block text-sm transition-colors hover:text-primary',
                            activeSection === section.id ? 'text-primary font-semibold' : 'text-muted'
                        ]"
                    >
                        {{ section.h2 }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    sections: {
        type: Array,
        required: true,
    },
});

const activeSection = ref('');
const mobileTOCOpen = ref(false);

const scrollToSection = (id) => {
    const element = document.getElementById(id);
    if (element) {
        const offset = 120; // Account for sticky header
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth',
        });

        // Update active section
        activeSection.value = id;

        // Close mobile TOC after selection
        if (window.innerWidth < 1024) {
            mobileTOCOpen.value = false;
        }
    }
};

const updateActiveSection = () => {
    const sections = props.sections.map(s => s.id);
    const scrollPosition = window.scrollY + 150; // Offset for header

    for (let i = sections.length - 1; i >= 0; i--) {
        const element = document.getElementById(sections[i]);
        if (element) {
            const elementTop = element.offsetTop;
            if (scrollPosition >= elementTop) {
                activeSection.value = sections[i];
                return;
            }
        }
    }
    activeSection.value = sections[0] || '';
};

const toggleMobileTOC = () => {
    mobileTOCOpen.value = !mobileTOCOpen.value;
};

onMounted(() => {
    window.addEventListener('scroll', updateActiveSection, { passive: true });
    updateActiveSection();
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateActiveSection);
});
</script>
