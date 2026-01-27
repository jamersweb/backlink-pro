<template>
    <div class="marketing-dark min-h-screen flex flex-col">
        <MarketingHead :meta="meta" />
        
        <AnnouncementBar v-if="showAnnouncement" />
        <MaintenanceBanner />
        <HeaderNav />
        
        <main class="flex-1">
            <slot />
        </main>
        
        <StickyMobileCTA />
        <Footer />
    </div>
</template>

<script setup>
import MarketingHead from '../Components/Marketing/SEO/MarketingHead.vue';
import HeaderNav from '../Components/Marketing/Shell/HeaderNav.vue';
import Footer from '../Components/Marketing/Shell/Footer.vue';
import AnnouncementBar from '../Components/Marketing/AnnouncementBar.vue';
import MaintenanceBanner from '../Components/Marketing/Shell/MaintenanceBanner.vue';
import StickyMobileCTA from '../Components/Marketing/StickyMobileCTA.vue';

const props = defineProps({
    meta: {
        type: Object,
        default: () => ({}),
    },
    showAnnouncement: {
        type: Boolean,
        default: true,
    },
});
</script>
