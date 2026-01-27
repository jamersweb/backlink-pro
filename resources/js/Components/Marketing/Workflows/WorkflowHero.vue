<template>
    <section class="workflow-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <!-- Breadcrumb -->
            <nav class="mb-8" aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-sm text-muted">
                    <li><Link href="/" class="hover:text-text">Product</Link></li>
                    <li>/</li>
                    <li><Link href="/workflows" class="hover:text-text">Workflows</Link></li>
                    <li>/</li>
                    <li class="text-text">{{ workflow.name }}</li>
                </ol>
            </nav>

            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Left: Text -->
                <div data-reveal>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                        {{ workflow.name }} workflow
                    </h1>
                    <p class="text-xl text-muted mb-8 leading-relaxed">
                        {{ workflow.summary }}
                    </p>
                    <div class="flex flex-wrap gap-4 mb-6">
                        <a href="#final-cta" @click.prevent="scrollToFinalCTA" class="btn-primary text-lg px-8 py-4">
                            {{ workflow.primaryCTA }}
                        </a>
                        <button @click="$emit('demo-open')" class="btn-secondary text-lg px-8 py-4">
                            {{ workflow.secondaryCTA }}
                        </button>
                        <Link href="/pricing" class="btn-ghost text-lg px-8 py-4">
                            View pricing
                        </Link>
                    </div>
                </div>

                <!-- Right: Media Card -->
                <div data-reveal class="gradient-glow">
                    <div class="marketing-card p-6">
                        <div class="aspect-video bg-surface2 rounded-lg overflow-hidden border border-border relative">
                            <video
                                v-if="shouldLoadVideo"
                                ref="videoRef"
                                :poster="workflow.heroMedia.poster"
                                loop
                                muted
                                playsinline
                                class="w-full h-full object-cover"
                            >
                                <source :src="workflow.heroMedia.video" type="video/mp4" />
                            </video>
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center cursor-pointer"
                                @click="loadVideo"
                            >
                                <div class="text-center">
                                    <div class="text-6xl mb-4">▶️</div>
                                    <p class="text-muted text-sm">Click to play demo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    workflow: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['demo-open']);

const videoRef = ref(null);
const shouldLoadVideo = ref(false);

const isMobile = computed(() => {
    return typeof window !== 'undefined' && window.innerWidth < 768;
});

const loadVideo = () => {
    shouldLoadVideo.value = true;
    if (videoRef.value) {
        videoRef.value.play().catch(() => {
            // Autoplay blocked, user can click to play
        });
    }
};

onMounted(() => {
    // Desktop: autoplay when visible
    if (!isMobile.value) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    shouldLoadVideo.value = true;
                    if (videoRef.value) {
                        videoRef.value.play().catch(() => {
                            // Autoplay blocked
                        });
                    }
                    observer.disconnect();
                }
            });
        }, { threshold: 0.5 });

        const card = document.querySelector('.workflow-hero .marketing-card');
        if (card) {
            observer.observe(card);
        }
    }
});

const scrollToFinalCTA = () => {
    const cta = document.getElementById('final-cta');
    if (cta) {
        cta.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<style scoped>
.workflow-hero {
    min-height: 70vh;
    display: flex;
    align-items: center;
}
</style>
