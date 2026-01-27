<template>
    <header :class="['header-nav', { 'is-scrolled': isScrolled }]" ref="headerRef">
        <div class="marketing-container flex items-center justify-between h-full">
            <div class="flex items-center gap-8">
                <Link href="/" class="logo text-xl font-bold text-text hover:text-primary transition-colors">
                    {{ site.brand.name }}
                </Link>
                <nav class="hidden md:flex items-center gap-6" aria-label="Main navigation">
                    <Link
                        v-for="item in site.nav"
                        :key="item.href"
                        :href="item.href"
                        :class="['nav-link', { 'is-active': isActive(item.href) }]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <Link href="/login" class="btn-ghost hidden sm:inline-block">Log In</Link>
                <Link href="/free-backlink-plan" class="btn-primary">Run Free Backlink Plan</Link>
                <button
                    @click="toggleMobileMenu"
                    @keydown.escape="closeMobileMenu"
                    class="md:hidden p-2 text-muted hover:text-text transition-colors focus:outline-none focus:ring-2 focus:ring-primary rounded"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="mobile-menu"
                    aria-label="Toggle navigation menu"
                >
                    <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div
            v-if="mobileMenuOpen"
            id="mobile-menu"
            class="md:hidden border-t border-border bg-bg"
            @keydown.escape="closeMobileMenu"
        >
            <nav class="marketing-container py-4 space-y-2" aria-label="Mobile navigation">
                <Link
                    v-for="item in site.nav"
                    :key="item.href"
                    :href="item.href"
                    :class="['block px-4 py-2 text-muted hover:text-text hover:bg-surface transition-colors rounded', { 'text-text bg-surface': isActive(item.href) }]"
                    @click="closeMobileMenu"
                >
                    {{ item.label }}
                </Link>
                <div class="pt-4 border-t border-border mt-2 space-y-2">
                    <Link
                        v-for="item in site.navSecondary"
                        :key="item.href"
                        :href="item.href"
                        :class="['block px-4 py-2 text-muted hover:text-text hover:bg-surface transition-colors rounded', { 'text-text bg-surface': isActive(item.href) }]"
                        @click="closeMobileMenu"
                    >
                        {{ item.label }}
                    </Link>
                </div>
            </nav>
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const site = page.props.site;

const isScrolled = ref(false);
const headerRef = ref(null);
const mobileMenuOpen = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 16;
};

const isActive = (href) => {
    const currentPath = page.url.split('?')[0];
    if (href === '/') {
        return currentPath === '/';
    }
    return currentPath.startsWith(href);
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

// Close menu on route change
router.on('navigate', () => {
    closeMobileMenu();
});

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
.header-nav {
    position: sticky;
    top: 0;
    z-index: 100;
    background-color: var(--bg);
    border-bottom: 1px solid var(--border);
    height: 72px;
    transition: height 0.3s ease, background-color 0.3s ease;
}

.header-nav.is-scrolled {
    height: 60px;
    background-color: rgba(11, 15, 20, 0.95);
    backdrop-filter: blur(10px);
}

.nav-link {
    color: var(--muted);
    font-weight: 500;
    text-decoration: none;
    transition: color 0.2s;
}

.nav-link:hover,
.nav-link.is-active {
    color: var(--text);
}
</style>
