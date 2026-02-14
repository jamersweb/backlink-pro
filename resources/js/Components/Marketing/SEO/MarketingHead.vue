<template>
    <Head>
        <!-- Title -->
        <title>{{ pageTitle }}</title>
        
        <!-- Meta Description -->
        <meta name="description" :content="metaDescription" />
        
        <!-- Canonical -->
        <link rel="canonical" :href="canonicalUrl" />
        
        <!-- OpenGraph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:title" :content="ogTitle" />
        <meta property="og:description" :content="ogDescription" />
        <meta property="og:image" :content="ogImage" />
        <meta property="og:site_name" :content="site.brand.name" />
        
        <!-- Twitter Card -->
        <meta name="twitter:card" :content="site.seo.twitter_card" />
        <meta name="twitter:title" :content="ogTitle" />
        <meta name="twitter:description" :content="ogDescription" />
        <meta name="twitter:image" :content="ogImage" />
        
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico" />
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <link rel="manifest" href="/site.webmanifest" />
    </Head>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const site = page.props.site;

// Title with template
const pageTitle = computed(() => {
    if (props.meta?.title) {
        return site.brand.title_template.replace('%s', props.meta.title);
    }
    return site.brand.default_title;
});

// Meta description
const metaDescription = computed(() => {
    return props.meta?.description || site.brand.default_description;
});

// Canonical URL (remove query params for cleaner canonical)
const canonicalUrl = computed(() => {
    try {
        const urlString = page.props.currentUrl || (typeof window !== 'undefined' ? window.location.href : '');
        if (!urlString) return '';
        const url = new URL(urlString);
        // Remove common tracking params
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'ref', 'fbclid', 'gclid'].forEach(param => {
            url.searchParams.delete(param);
        });
        return url.toString();
    } catch (e) {
        // Fallback to currentUrl as-is if URL parsing fails
        return page.props.currentUrl || '';
    }
});

// OG Title
const ogTitle = computed(() => {
    return props.meta?.og?.title || props.meta?.title || site.brand.default_title;
});

// OG Description
const ogDescription = computed(() => {
    return props.meta?.og?.description || props.meta?.description || site.brand.default_description;
});

// OG Image
const ogImage = computed(() => {
    const image = props.meta?.og?.image || site.seo.og_image;
    if (image?.startsWith('http')) {
        return image;
    }
    return `${site.urls.app_url}${image}`;
});

// JSON-LD Schema
const schemaJson = computed(() => {
    const appUrl = site.urls.app_url;
    const sameAs = [];
    
    if (site.social.x) sameAs.push(site.social.x);
    if (site.social.linkedin) sameAs.push(site.social.linkedin);
    if (site.social.youtube) sameAs.push(site.social.youtube);
    
    const schema = {
        '@context': 'https://schema.org',
        '@graph': [
            {
                '@type': 'Organization',
                '@id': `${appUrl}/#organization`,
                name: site.brand.name,
                url: appUrl,
                sameAs: sameAs.length > 0 ? sameAs : undefined,
            },
            {
                '@type': 'WebSite',
                '@id': `${appUrl}/#website`,
                url: appUrl,
                name: site.brand.name,
            },
        ],
    };
    
    // Remove undefined sameAs if empty
    if (!schema['@graph'][0].sameAs) {
        delete schema['@graph'][0].sameAs;
    }
    
    return JSON.stringify(schema);
});

// Inject JSON-LD script (Vue disallows <script> in templates)
const JSON_LD_ID = 'marketing-head-jsonld';
const injectJsonLd = () => {
    let el = document.getElementById(JSON_LD_ID);
    if (el) el.remove();
    el = document.createElement('script');
    el.id = JSON_LD_ID;
    el.type = 'application/ld+json';
    el.textContent = schemaJson.value;
    document.head.appendChild(el);
};
onMounted(injectJsonLd);
onBeforeUnmount(() => {
    const el = document.getElementById(JSON_LD_ID);
    if (el) el.remove();
});
watch(schemaJson, injectJsonLd);
</script>
