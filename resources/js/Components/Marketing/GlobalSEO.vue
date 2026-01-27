<template>
    <Head>
        <!-- Default Meta -->
        <title>{{ meta.title || defaultTitle }}</title>
        <meta name="description" :content="meta.description || defaultDescription" />
        
        <!-- Canonical -->
        <link rel="canonical" :href="canonicalUrl" />
        
        <!-- OpenGraph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:title" :content="meta.og?.title || meta.title || defaultTitle" />
        <meta property="og:description" :content="meta.og?.description || meta.description || defaultDescription" />
        <meta property="og:image" :content="meta.og?.image || defaultImage" />
        <meta property="og:site_name" content="BacklinkPro" />
        
        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="meta.og?.title || meta.title || defaultTitle" />
        <meta name="twitter:description" :content="meta.og?.description || meta.description || defaultDescription" />
        <meta name="twitter:image" :content="meta.og?.image || defaultImage" />
        
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        
        <!-- Schema.org JSON-LD -->
        <script type="application/ld+json" v-html="schemaJson"></script>
    </Head>
</template>

<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    meta: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const defaultTitle = 'BacklinkPro — Guardrailed Backlink Workflows with Approvals & Evidence';
const defaultDescription = 'Automate backlink workflows with guardrails, approvals, and evidence logs. No PBNs. Human oversight. Full transparency.';
const defaultImage = typeof window !== 'undefined' ? `${window.location.origin}/images/og-image.jpg` : '';

const canonicalUrl = computed(() => {
    if (typeof window === 'undefined') return '';
    return window.location.origin + window.location.pathname;
});

const schemaJson = computed(() => {
    const schema = {
        '@context': 'https://schema.org',
        '@graph': [
            {
                '@type': 'Organization',
                '@id': `${page.url}/#organization`,
                name: 'BacklinkPro',
                url: typeof window !== 'undefined' ? window.location.origin : '',
                logo: {
                    '@type': 'ImageObject',
                    url: `${typeof window !== 'undefined' ? window.location.origin : ''}/images/logo.png`,
                },
                sameAs: [
                    // Add social media URLs when available
                ],
            },
            {
                '@type': 'WebSite',
                '@id': `${page.url}/#website`,
                url: typeof window !== 'undefined' ? window.location.origin : '',
                name: 'BacklinkPro',
                potentialAction: {
                    '@type': 'SearchAction',
                    target: {
                        '@type': 'EntryPoint',
                        urlTemplate: `${typeof window !== 'undefined' ? window.location.origin : ''}/search?q={search_term_string}`,
                    },
                    'query-input': 'required name=search_term_string',
                },
            },
        ],
    };
    return JSON.stringify(schema);
});
</script>
