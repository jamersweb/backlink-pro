<template>
    <section id="how-it-works" class="how-it-works py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                How It Works
            </h2>
            
            <!-- Desktop: Horizontal Stepper -->
            <div class="hidden md:block">
                <div class="relative">
                    <div class="absolute top-8 left-0 right-0 h-0.5 bg-border">
                        <div
                            :style="{ width: `${(activeStep / (steps.length - 1)) * 100}%` }"
                            class="h-full bg-primary transition-all duration-500"
                        ></div>
                    </div>
                    <div class="grid grid-cols-4 gap-8 relative">
                        <div
                            v-for="(step, idx) in steps"
                            :key="idx"
                            data-reveal
                            @mouseenter="activeStep = idx"
                            class="text-center cursor-pointer"
                        >
                            <div
                                :class="[
                                    'w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4 transition-all',
                                    activeStep === idx ? 'bg-primary text-white scale-110' : 'bg-surface border-2 border-border text-text'
                                ]"
                            >
                                {{ idx + 1 }}
                            </div>
                            <h3 class="text-lg font-bold mb-2 text-text">{{ step.title }}</h3>
                            <p class="text-sm text-muted">{{ step.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile: Vertical Accordion -->
            <div class="md:hidden space-y-4">
                <div
                    v-for="(step, idx) in steps"
                    :key="idx"
                    data-reveal
                    class="marketing-card"
                    @click="activeStepMobile = activeStepMobile === idx ? null : idx"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                                {{ idx + 1 }}
                            </div>
                            <div>
                                <h3 class="font-bold text-text">{{ step.title }}</h3>
                                <p class="text-sm text-muted">{{ step.description }}</p>
                            </div>
                        </div>
                        <svg
                            :class="['w-5 h-5 text-muted transition-transform', activeStepMobile === idx && 'rotate-180']"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <div v-if="activeStepMobile === idx" class="mt-4 pt-4 border-t border-border">
                        <div class="h-48 bg-surface rounded flex items-center justify-center text-muted text-sm">
                            Screenshot placeholder
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-12" data-reveal>
                <a href="/workflows" class="text-primary hover:underline font-semibold">
                    See full workflow →
                </a>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const activeStep = ref(0);
const activeStepMobile = ref(null);

const steps = [
    {
        title: 'Connect Project & Rules',
        description: 'Set your domain, target URLs, and safety rules.',
    },
    {
        title: 'Discover Opportunities',
        description: 'AI scans the web for relevant link opportunities.',
    },
    {
        title: 'AI Chooses & Executes',
        description: 'Selects workflow (comment/profile/forum/guest) and executes.',
    },
    {
        title: 'Approve & Track',
        description: 'Review, approve, and track every link with evidence.',
    },
];
</script>
