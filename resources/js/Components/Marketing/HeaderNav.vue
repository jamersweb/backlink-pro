<template>
    <header :class="['header-nav', { 'is-scrolled': isScrolled }]" ref="headerRef">
        <div class="marketing-container flex items-center justify-between h-full">
            <div class="flex items-center gap-8">
                <Link href="/" class="logo text-xl font-bold text-text hover:text-primary transition-colors">
                    BacklinkPro
                </Link>
                <nav class="hidden md:flex items-center gap-6" aria-label="Main navigation">
                    <Link href="/seo-audit-report" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors text-sm">
                        🔍 SEO Audit Report
                    </Link>
                    <Link href="/" class="nav-link">Home</Link>
                    <Link href="/product" class="nav-link">Product</Link>
                    <Link href="/how-it-works" class="nav-link">How It Works</Link>
                    <Link href="/workflows" class="nav-link">Workflows</Link>
                    <Link href="/pricing" class="nav-link">Pricing</Link>
                    <Link href="/case-studies" class="nav-link">Case Studies</Link>
                    <Link href="/resources" class="nav-link">Resources</Link>
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <Link href="/login" class="btn-ghost hidden sm:inline-block">Log In</Link>
                <Link href="/free-backlink-plan" class="btn-primary">Run Free Plan</Link>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const isScrolled = ref(false);
const headerRef = ref(null);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 16;
};

// Removed scrollToHero - now using direct link

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

.nav-link:hover {
    color: var(--text);
}
</style>
