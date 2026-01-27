<template>
    <section class="logo-strip py-12">
        <div class="marketing-container">
            <div class="flex items-center justify-center gap-8 md:gap-12 flex-wrap opacity-60" data-reveal>
                <div
                    v-for="(logo, idx) in logos"
                    :key="idx"
                    class="grayscale hover:grayscale-0 transition-all"
                >
                    <div class="h-8 w-24 bg-surface2 rounded flex items-center justify-center text-muted text-xs">
                        {{ logo.name }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    logos: {
        type: Array,
        default: () => [],
    },
});
</script>
