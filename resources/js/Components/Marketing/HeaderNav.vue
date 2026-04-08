<template>
    <header :class="['header-nav', { 'header-nav-dark': isScrolled }]">
        <div class="marketing-container header-shell">
            <div class="header-brand-wrap">
                <Link class="header-brand" href="/">
                    <span class="header-brand-mark">
                        <span class="header-brand-mark-core"></span>
                        <span class="header-brand-mark-ring"></span>
                    </span>
                    <span>BacklinkPro</span>
                </Link>

                <nav class="header-links" aria-label="Primary">
                    <div class="header-nav-dropdown" v-for="group in navGroups" :key="group.label">
                        <button class="header-nav-trigger" type="button">
                            <span>{{ group.label }}</span>
                            <span class="header-nav-caret" aria-hidden="true"></span>
                        </button>

                        <div class="header-nav-menu">
                            <Link
                                v-for="item in group.items"
                                :key="item.href"
                                class="header-nav-menu-item"
                                :href="item.href"
                            >
                                <span class="header-nav-menu-label">{{ item.label }}</span>
                            </Link>
                        </div>
                    </div>
                </nav>
            </div>

            <div class="header-actions">
                <a class="header-text-link" href="/login" @click.prevent="navigateTo('/login')">Sign In</a>
                <Link class="header-button header-button-light" href="/contact">Contact Sales</Link>
            </div>
        </div>

        <div class="header-mobile-nav">
            <div class="marketing-container">
                <div class="header-mobile-groups">
                    <details class="header-mobile-dropdown" v-for="group in navGroups" :key="`${group.label}-mobile`">
                        <summary>
                            <span>{{ group.label }}</span>
                            <span class="header-nav-caret" aria-hidden="true"></span>
                        </summary>

                        <div class="header-mobile-menu">
                            <Link
                                v-for="item in group.items"
                                :key="item.href"
                                :href="item.href"
                                class="header-mobile-menu-item"
                            >
                                <span class="header-nav-menu-label">{{ item.label }}</span>
                            </Link>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const isScrolled = ref(false);

const navigateTo = (url) => {
    window.location.href = url;
};

const navGroups = [
    {
        label: 'Product',
        items: [
            { label: 'Platform Overview', href: '/product' },
            { label: 'How It Works', href: '/how-it-works' },
            { label: 'Pricing', href: '/pricing' },
            { label: 'Free Plan', href: '/free-plan' },
            { label: 'SEO Audit Report', href: '/seo-audit-report' },
        ],
    },
    {
        label: 'Company',
        items: [
            { label: 'About Us', href: '/about' },
            { label: 'Case Studies', href: '/case-studies' },
            { label: 'Resources', href: '/resources' },
            { label: 'Contact', href: '/contact' },
        ],
    },
];

const handleScroll = () => {
    isScrolled.value = window.scrollY > 120;
};

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
    padding: 1.25rem 0 0;
}

.header-shell,
.header-brand-wrap,
.header-actions,
.header-links,
.header-brand {
    display: flex;
    align-items: center;
}

.header-shell {
    justify-content: space-between;
    gap: 1.5rem;
}

.header-brand-wrap {
    gap: 0.7rem;
    padding: 0.7rem 0.95rem;
    border-radius: 0.95rem;
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(16px) saturate(140%);
    -webkit-backdrop-filter: blur(16px) saturate(140%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
}

.header-brand {
    gap: 0.75rem;
    color: rgba(255, 247, 242, 0.94);
    text-decoration: none;
    font-size: 0.92rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.header-brand-mark {
    position: relative;
    width: 1.35rem;
    height: 1.35rem;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.28), rgba(255, 255, 255, 0.12));
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.header-brand-mark-core,
.header-brand-mark-ring {
    position: absolute;
    inset: 0.18rem;
    border-radius: 999px;
}

.header-brand-mark-core {
    background: #ffffff;
}

.header-brand-mark-ring {
    border: 1.7px solid #ffffff;
    border-left-color: transparent;
    border-bottom-color: transparent;
    background: transparent;
}

.header-links {
    position: relative;
}

.header-nav-dropdown {
    position: relative;
}

.header-nav-trigger,
.header-text-link {
    color: rgba(255, 244, 239, 0.84);
    text-decoration: none;
    transition: color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    font-family: "Manrope", Inter, sans-serif;
}

.header-nav-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    background: transparent;
    border: none;
    padding: 0.55rem 0.8rem;
    border-radius: 0.7rem;
    cursor: pointer;
}

.header-nav-trigger:hover,
.header-text-link:hover {
    color: #ffffff;
}

.header-nav-caret {
    width: 0.42rem;
    height: 0.42rem;
    margin-top: -0.02rem;
    border-right: 1.5px solid currentColor;
    border-bottom: 1.5px solid currentColor;
    opacity: 0.62;
    transform-origin: center;
    transform: rotate(45deg);
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.header-nav-dropdown:hover .header-nav-caret,
.header-mobile-dropdown[open] .header-nav-caret {
    transform: rotate(225deg);
}

.header-nav-menu {
    position: absolute;
    top: calc(100% + 0.72rem);
    left: 0;
    min-width: 11.75rem;
    max-width: 12.5rem;
    display: grid;
    gap: 0.18rem;
    padding: 0.38rem;
    border-radius: 0.95rem;
    background: rgba(16, 18, 24, 0.74);
    border: 1px solid rgba(255, 255, 255, 0.14);
    backdrop-filter: blur(18px) saturate(150%);
    -webkit-backdrop-filter: blur(18px) saturate(150%);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.28);
    opacity: 0;
    visibility: hidden;
    transform: translateY(8px);
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
}

.header-nav-dropdown:hover .header-nav-menu,
.header-nav-dropdown:focus-within .header-nav-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.header-nav-menu-item,
.header-mobile-menu-item {
    display: grid;
    gap: 0;
    padding: 0.7rem 0.82rem;
    border-radius: 0.76rem;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.2s ease;
}

.header-nav-menu-item:hover,
.header-mobile-menu-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.header-nav-menu-label {
    color: #ffffff;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    font-family: "Manrope", Inter, sans-serif;
}

.header-actions {
    gap: 0.85rem;
}

.header-text-link {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.95rem 1.3rem;
    border-radius: 0.95rem;
    background: rgba(255, 255, 255, 0.14);
    backdrop-filter: blur(16px) saturate(140%);
    -webkit-backdrop-filter: blur(16px) saturate(140%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
}

.header-text-link:hover {
    background: rgba(255, 255, 255, 0.22);
    box-shadow: 0 18px 42px rgba(0, 0, 0, 0.14);
    transform: translateY(-1px);
}

.header-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 0.95rem 1.55rem;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.header-button-light {
    color: #111827;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.12);
}

.header-button-light:hover {
    transform: translateY(-1px);
    box-shadow: 0 20px 42px rgba(15, 23, 42, 0.16);
}

.header-nav-dark .header-brand-wrap,
.header-nav-dark .header-text-link,
.header-nav-dark .header-mobile-dropdown {
    background: rgba(16, 18, 24, 0.54);
    box-shadow: 0 18px 44px rgba(0, 0, 0, 0.22);
}

.header-nav-dark .header-brand,
.header-nav-dark .header-nav-trigger,
.header-nav-dark .header-text-link,
.header-nav-dark .header-mobile-dropdown summary {
    color: rgba(255, 255, 255, 0.94);
}

.header-nav-dark .header-brand-mark {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.12));
}

.header-nav-dark .header-brand-mark-core,
.header-nav-dark .header-brand-mark-ring {
    background: #ffffff;
    border-color: #ffffff;
    border-left-color: transparent;
    border-bottom-color: transparent;
}

.header-nav-dark .header-nav-trigger:hover,
.header-nav-dark .header-text-link:hover {
    color: #ffffff;
}

.header-nav-dark .header-text-link:hover {
    background: rgba(255, 255, 255, 0.18);
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.2);
}

.header-nav-dark .header-nav-menu {
    background: rgba(16, 18, 24, 0.74);
    border-color: rgba(255, 255, 255, 0.14);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.28);
}

.header-nav-dark .header-nav-menu-item:hover,
.header-nav-dark .header-mobile-menu-item:hover {
    background: rgba(255, 255, 255, 0.08);
}

.header-nav-dark .header-nav-menu-label {
    color: #ffffff;
}

.header-mobile-nav {
    display: none;
}

@media (max-width: 820px) {
    .header-nav {
        padding-top: 0.9rem;
    }

    .header-shell {
        align-items: flex-start;
    }

    .header-links {
        display: none;
    }

    .header-brand-wrap,
    .header-actions {
        flex: 1;
    }

    .header-brand-wrap {
        padding: 0.75rem 0.9rem;
    }

    .header-actions {
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .header-text-link {
        display: none;
    }

    .header-button {
        padding: 0.9rem 1.2rem;
    }

    .header-mobile-nav {
        display: block;
        margin-top: 0.75rem;
    }

    .header-mobile-groups {
        display: grid;
        gap: 0.65rem;
    }

    .header-mobile-dropdown {
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.16);
        backdrop-filter: blur(16px) saturate(140%);
        -webkit-backdrop-filter: blur(16px) saturate(140%);
        overflow: hidden;
    }

.header-mobile-dropdown summary {
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.95rem 1rem;
        color: rgba(255, 247, 242, 0.94);
        font-weight: 600;
        cursor: pointer;
    }

    .header-mobile-dropdown summary::-webkit-details-marker {
        display: none;
    }

    .header-mobile-menu {
        display: grid;
        gap: 0.35rem;
        padding: 0 0.55rem 0.55rem;
    }
}
</style>
