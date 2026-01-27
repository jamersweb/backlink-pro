<template>
    <aside class="side-nav hidden lg:block w-64 flex-shrink-0">
        <div class="sticky top-24">
            <nav aria-label="Case study sections">
                <ul class="space-y-2">
                    <li
                        v-for="(section, idx) in sections"
                        :key="idx"
                    >
                        <a
                            :href="`#${section.id}`"
                            :class="[
                                'block px-4 py-2 rounded-lg text-sm transition-colors',
                                activeSection === section.id
                                    ? 'bg-primary text-white'
                                    : 'text-muted hover:text-text hover:bg-surface2'
                            ]"
                            @click.prevent="scrollToSection(section.id)"
                        >
                            {{ section.label }}
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const sections = [
    { id: 'starting-point', label: 'Starting point' },
    { id: 'guardrails', label: 'Guardrails' },
    { id: 'execution', label: 'Execution' },
    { id: 'evidence', label: 'Evidence' },
    { id: 'outcomes', label: 'Outcomes' },
    { id: 'takeaways', label: 'Takeaways' },
];

const activeSection = ref('starting-point');

const updateActiveSection = () => {
    const scrollPosition = window.scrollY + 150;
    for (let i = sections.length - 1; i >= 0; i--) {
        const section = document.getElementById(sections[i].id);
        if (section && section.offsetTop <= scrollPosition) {
            activeSection.value = sections[i].id;
            break;
        }
    }
};

const scrollToSection = (id) => {
    const element = document.getElementById(id);
    if (element) {
        const offset = 120;
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = elementPosition - offset;
        window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
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
