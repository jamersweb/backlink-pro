<template>
    <div class="legal-layout">
        <slot name="hero" />
        <div class="marketing-container max-w-7xl mx-auto">
            <div class="flex gap-8">
                <!-- Main Content -->
                <main class="flex-1 max-w-4xl">
                    <slot name="content" />
                </main>

                <!-- TOC Sidebar (Desktop only) -->
                <div class="hidden lg:block">
                    <slot name="toc" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// Layout wrapper component
</script>

<style scoped>
@media print {
    .legal-layout {
        background: white;
    }
}
</style>
