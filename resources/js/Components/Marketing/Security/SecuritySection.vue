<template>
    <section :id="section.id" class="security-section py-20 scroll-mt-24" :class="section.id === 'overview' ? '' : 'bg-surface2'">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                {{ section.title }}
            </h2>
            <p class="text-center text-muted mb-12 max-w-2xl mx-auto" data-reveal>
                {{ section.lead }}
            </p>

            <!-- Cards Layout -->
            <div v-if="section.cards" class="grid md:grid-cols-3 gap-6" data-reveal>
                <div
                    v-for="(card, idx) in section.cards"
                    :key="idx"
                    class="marketing-card p-6"
                >
                    <h3 class="text-xl font-bold mb-4 text-text">{{ card.title }}</h3>
                    <ul v-if="card.bullets" class="space-y-2">
                        <li
                            v-for="(bullet, bIdx) in card.bullets"
                            :key="bIdx"
                            class="flex items-start gap-2 text-muted text-sm"
                        >
                            <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ bullet }}</span>
                        </li>
                    </ul>
                    <p v-if="card.desc" class="text-muted text-sm">{{ card.desc }}</p>
                </div>
            </div>

            <!-- Lists Layout -->
            <div v-if="section.lists" class="grid md:grid-cols-2 gap-8 mb-8" data-reveal>
                <div
                    v-for="(list, idx) in section.lists"
                    :key="idx"
                    class="marketing-card p-6"
                >
                    <h3 class="text-lg font-bold mb-4 text-text">{{ list.label }}</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(item, iIdx) in list.items"
                            :key="iIdx"
                            class="flex items-start gap-2 text-muted text-sm"
                        >
                            <span :class="list.label.includes('avoid') ? 'text-danger' : 'text-success'">
                                {{ list.label.includes('avoid') ? '✗' : '•' }}
                            </span>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Callout -->
            <div v-if="section.callout" class="marketing-card p-6 bg-primary/10 border-2 border-primary/30 mb-8" data-reveal>
                <h3 class="font-bold text-text mb-2">{{ section.callout.title }}</h3>
                <p class="text-muted">{{ section.callout.text }}</p>
            </div>

            <!-- Bullets Layout -->
            <div v-if="section.bullets" class="marketing-card p-8" data-reveal>
                <ul class="space-y-4">
                    <li
                        v-for="(bullet, idx) in section.bullets"
                        :key="idx"
                        class="flex items-start gap-3 text-muted"
                    >
                        <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ bullet }}</span>
                    </li>
                </ul>
            </div>

            <!-- Two Column Layout -->
            <div v-if="section.twoCol" class="grid md:grid-cols-2 gap-8 mb-8" data-reveal>
                <div
                    v-for="(col, idx) in section.twoCol"
                    :key="idx"
                    class="marketing-card p-6"
                >
                    <h3 class="text-xl font-bold mb-4 text-text" :class="col.title.includes('never') ? 'text-danger' : 'text-success'">
                        {{ col.title }}
                    </h3>
                    <ul class="space-y-3">
                        <li
                            v-for="(item, iIdx) in col.items"
                            :key="iIdx"
                            class="flex items-start gap-3 text-muted"
                        >
                            <svg
                                :class="[
                                    'w-5 h-5 flex-shrink-0 mt-0.5',
                                    col.title.includes('never') ? 'text-danger' : 'text-success'
                                ]"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    v-if="!col.title.includes('never')"
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                                <path
                                    v-else
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>{{ item }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Disclaimer -->
            <div v-if="section.disclaimer" class="mt-8 p-4 bg-warning/10 border border-warning/20 rounded-lg" data-reveal>
                <p class="text-sm text-muted">
                    <strong class="text-text">Note:</strong> {{ section.disclaimer }}
                </p>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    section: {
        type: Object,
        required: true,
    },
});
</script>
