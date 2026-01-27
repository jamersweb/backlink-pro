<template>
    <aside class="product-side-nav hidden lg:block w-64 flex-shrink-0">
        <div class="sticky top-24">
            <nav class="marketing-card p-6">
                <h3 class="text-sm font-semibold text-text mb-4 uppercase tracking-wide">
                    On this page
                </h3>
                <ul class="space-y-2">
                    <li
                        v-for="(item, idx) in navItems"
                        :key="idx"
                    >
                        <a
                            :href="`#${item.id}`"
                            @click.prevent="scrollToSection(item.id)"
                            :class="[
                                'block text-sm transition-colors hover:text-primary',
                                activeSection === item.id ? 'text-primary font-semibold' : 'text-muted'
                            ]"
                        >
                            {{ item.label }}
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const navItems = [
    { id: 'modules', label: 'Modules' },
    { id: 'workflows', label: 'Workflows' },
    { id: 'guardrails', label: 'Guardrails' },
    { id: 'reports', label: 'Reports' },
    { id: 'faq', label: 'FAQ' },
];

const activeSection = ref('modules');

const scrollToSection = (id) => {
    const element = document.getElementById(id);
    if (element) {
        const offset = 120;
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = elementPosition - offset;
        window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
        activeSection.value = id;
    }
};

const updateActiveSection = () => {
    const sections = navItems.map(item => item.id);
    const scrollPosition = window.scrollY + 150;

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

onMounted(() => {
    window.addEventListener('scroll', updateActiveSection, { passive: true });
    updateActiveSection();
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateActiveSection);
});
</script>
