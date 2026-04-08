<template>
    <div v-if="show" class="sticky-mobile-cta fixed bottom-0 left-0 right-0 z-50 md:hidden">
        <div class="sticky-mobile-shell">
            <Link href="/contact" class="sticky-mobile-button">
                Contact Sales
            </Link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const show = ref(false);

const checkScroll = () => {
    show.value = window.scrollY > 360;
};

onMounted(() => {
    window.addEventListener('scroll', checkScroll);
    checkScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', checkScroll);
});
</script>

<style scoped>
.sticky-mobile-cta {
    backdrop-filter: blur(10px);
}

.sticky-mobile-shell {
    padding: 0.9rem 1rem calc(0.9rem + env(safe-area-inset-bottom));
    background: rgba(5, 5, 5, 0.92);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.sticky-mobile-button {
    display: inline-flex;
    width: 100%;
    min-height: 3.35rem;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 700;
    color: #16100d;
    background: linear-gradient(180deg, #fff7f2, #ffe7db);
    border: 1px solid rgba(255, 224, 208, 0.72);
}
</style>
