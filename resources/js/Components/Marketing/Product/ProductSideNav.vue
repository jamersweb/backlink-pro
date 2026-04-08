<template>
    <aside class="product-side-nav">
        <nav class="marketing-card product-side-nav-shell">
            <div class="product-side-nav-copy">
                <span class="product-side-nav-kicker">Page Guide</span>
                <h3 class="text-sm font-semibold text-text uppercase tracking-wide">
                    On this page
                </h3>
                <p class="text-muted text-sm">Jump straight to the product areas most relevant to your review.</p>
            </div>

            <ul class="product-side-nav-list">
                <li
                    v-for="(item, idx) in navItems"
                    :key="idx"
                >
                    <a
                        :href="`#${item.id}`"
                        @click.prevent="scrollToSection(item.id)"
                        :class="[
                            'product-side-nav-link',
                            activeSection === item.id ? 'is-active' : ''
                        ]"
                    >
                        <span class="product-side-nav-dot"></span>
                        {{ item.label }}
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const navItems = [
    { id: 'modules', label: 'Modules' },
    { id: 'workflows', label: 'Workflows' },
    { id: 'guardrails', label: 'Guardrails' },
    { id: 'reports', label: 'Reports' },
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

<style scoped>
.product-side-nav {
    margin: 0 auto 2rem;
    max-width: 62rem;
}

.product-side-nav-shell {
    display: grid;
    grid-template-columns: minmax(0, 18rem) minmax(0, 1fr);
    gap: 1.25rem;
    align-items: center;
    padding: 1.15rem 1.35rem;
    border-radius: 1.5rem;
}

.product-side-nav-copy {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.product-side-nav-kicker {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 0.3rem 0.6rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.2);
    background: rgba(255, 110, 64, 0.08);
    color: rgba(255, 174, 143, 0.92);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.product-side-nav-copy h3 {
    margin: 0;
}

.product-side-nav-copy p {
    margin: 0;
    line-height: 1.6;
}

.product-side-nav-list {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.7rem;
    margin: 0;
    padding: 0;
}

.product-side-nav-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    min-height: 2.65rem;
    padding: 0 1rem 0 0.95rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.18);
    background: linear-gradient(180deg, rgba(255, 110, 64, 0.09), rgba(255, 110, 64, 0.04));
    color: rgba(255, 240, 232, 0.72);
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}

.product-side-nav-dot {
    width: 0.42rem;
    height: 0.42rem;
    border-radius: 999px;
    background: rgba(255, 188, 162, 0.42);
    transition: background 0.2s ease, box-shadow 0.2s ease;
}

.product-side-nav-link:hover,
.product-side-nav-link.is-active {
    color: #fff7f2;
    border-color: rgba(255, 110, 64, 0.48);
    background: rgba(255, 110, 64, 0.14);
    transform: translateY(-1px);
}

.product-side-nav-link:hover .product-side-nav-dot,
.product-side-nav-link.is-active .product-side-nav-dot {
    background: #ff8a65;
    box-shadow: 0 0 0 0.3rem rgba(255, 110, 64, 0.14);
}

@media (max-width: 900px) {
    .product-side-nav {
        margin-bottom: 0.75rem;
    }

    .product-side-nav-shell {
        grid-template-columns: 1fr;
    }

    .product-side-nav-list {
        justify-content: flex-start;
    }
}
</style>
