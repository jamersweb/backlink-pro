<template>
    <section class="faq py-20 bg-surface2">
        <div class="marketing-container max-w-3xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Frequently Asked Questions
            </h2>
            
            <div class="space-y-4">
                <div
                    v-for="(item, idx) in faqs"
                    :key="idx"
                    data-reveal
                    class="marketing-card"
                >
                    <button
                        @click="toggle(idx)"
                        @keydown.enter="toggle(idx)"
                        @keydown.space.prevent="toggle(idx)"
                        class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-primary rounded-lg p-2 -m-2"
                        :aria-expanded="openIndex === idx"
                        :aria-controls="`faq-${idx}`"
                    >
                        <h3 class="text-lg font-semibold text-text pr-4">{{ item.question }}</h3>
                        <svg
                            :class="[
                                'w-6 h-6 text-muted flex-shrink-0 transition-transform',
                                openIndex === idx && 'rotate-180'
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div
                        :id="`faq-${idx}`"
                        v-show="openIndex === idx"
                        class="mt-4 pt-4 border-t border-border text-muted"
                    >
                        <p>{{ item.answer }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';

const openIndex = ref(null);

const toggle = (idx) => {
    openIndex.value = openIndex.value === idx ? null : idx;
};

const faqs = [
    {
        question: 'How safe is automated link building?',
        answer: 'BacklinkPro includes multiple safety layers: risk scoring for every opportunity, human-in-the-loop approvals, blacklist/whitelist controls, and anchor distribution rules. We never use PBNs, hacked sites, or spam tactics.',
    },
    {
        question: 'How long until I see results?',
        answer: 'Most users see their first approved links within 1-2 weeks. Full campaign results typically appear after 1-3 months, depending on your approval rate and workflow selection.',
    },
    {
        question: 'Can I control which links get placed?',
        answer: 'Yes. Every link goes through an approval queue where you can review opportunities, risk scores, and evidence before approving. You can also set rules for automatic approval of low-risk opportunities.',
    },
    {
        question: 'How do you verify link placement?',
        answer: 'We capture evidence logs with screenshots for every link placement attempt. You can monitor link health in real-time and receive alerts if a link is removed or becomes unavailable.',
    },
    {
        question: 'What happens if a link gets removed?',
        answer: 'Our monitoring system detects removed links and alerts you immediately. You can then choose to re-attempt placement or mark the opportunity as unavailable.',
    },
    {
        question: 'Do you integrate with SEO tools?',
        answer: 'Yes, we integrate with Google Search Console, Ahrefs, and other popular SEO platforms. You can also export data via API.',
    },
    {
        question: 'Can I use this for multiple projects?',
        answer: 'Yes. All plans support multiple projects. Growth and Pro plans include advanced project management and team collaboration features.',
    },
    {
        question: 'What if I need to pause my campaign?',
        answer: 'You can pause or resume campaigns at any time from the dashboard. Paused campaigns don\'t consume your monthly action credits.',
    },
];
</script>
