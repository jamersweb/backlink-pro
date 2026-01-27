<template>
    <section id="templates" class="workflow-templates py-20 bg-surface2">
        <div class="marketing-container max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Safe Templates Library
            </h2>
            <p class="text-center text-muted mb-12" data-reveal>
                Professional templates with personalization rules. No spam, no low-quality content.
            </p>

            <div class="marketing-card p-8 mb-8" data-reveal>
                <h3 class="text-xl font-bold mb-6 text-text">Template Categories</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div
                        v-for="(category, idx) in templateCategories"
                        :key="idx"
                        class="p-4 bg-surface rounded border border-border"
                    >
                        <h4 class="font-semibold text-text mb-2">{{ category.name }}</h4>
                        <p class="text-sm text-muted mb-3">{{ category.desc }}</p>
                        <div class="text-xs text-muted italic">
                            Example: "{{ category.example }}"
                        </div>
                    </div>
                </div>
            </div>

            <div class="marketing-card p-8" data-reveal>
                <h3 class="text-xl font-bold mb-4 text-text">Personalization Rules</h3>
                <ul class="space-y-3 text-muted">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Context injection from target page content</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Brand mention integration</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Natural language generation</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-success flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Spam signal detection and avoidance</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>

<script setup>
const templateCategories = [
    {
        name: 'Neutral',
        desc: 'Balanced, informative responses',
        example: 'Thanks for sharing this insight. I found the point about [topic] particularly relevant.',
    },
    {
        name: 'Helpful',
        desc: 'Value-first contributions',
        example: 'This is a great resource. I\'ve also found that [related insight] can be helpful in similar situations.',
    },
    {
        name: 'Question',
        desc: 'Engaging questions',
        example: 'Have you considered [alternative approach]? I\'m curious about your experience with [related topic].',
    },
    {
        name: 'Resource Mention',
        desc: 'Natural resource sharing',
        example: 'I came across [resource] that might be relevant here. It covers [related aspect] in detail.',
    },
];
</script>
