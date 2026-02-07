<template>
    <section class="workflows-grid py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Four Powerful Workflows
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Choose the right workflow for each opportunity.
            </p>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="(workflow, idx) in workflows"
                    :key="idx"
                    data-reveal
                    class="marketing-card transition-all duration-300 ease-out hover:!border-[1.5px] hover:!border-white cursor-pointer group"
                >
                    <div class="mb-4">
                        <img :src="workflow.icon" :alt="workflow.name" class="w-16 h-16 transition-transform duration-300 ease-out group-hover:rotate-12 will-change-transform" style="transform-origin: center center;" />
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-text">{{ workflow.name }}</h3>
                    <p class="text-sm text-muted mb-4">{{ workflow.description }}</p>
                    <div class="space-y-2 mb-6 text-sm">
                        <div>
                            <span class="font-semibold text-text">Best for:</span>
                            <span class="text-muted ml-2">{{ workflow.bestFor }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-text">Timeline:</span>
                            <span class="text-muted ml-2">{{ workflow.timeline }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-text">Controls:</span>
                            <span class="text-muted ml-2">{{ workflow.controls }}</span>
                        </div>
                    </div>
                    <a href="#" class="text-primary hover:underline font-semibold text-sm">
                        View workflow →
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const workflows = [
    {
        icon: '/images/logos/comment-icon.svg',
        name: 'Comment',
        description: 'Engage with relevant blog posts and discussions.',
        bestFor: 'Blog posts, articles',
        timeline: '1-3 days',
        controls: 'Templates, filters',
    },
    {
        icon: '/images/logos/profile-icon.svg',
        name: 'Profile',
        description: 'Create profiles on relevant platforms.',
        bestFor: 'Forums, communities',
        timeline: '2-5 days',
        controls: 'Bio templates, rules',
    },
    {
        icon: '/images/logos/forum-icon.svg',
        name: 'Forum',
        description: 'Participate in forum discussions.',
        bestFor: 'Active forums',
        timeline: '3-7 days',
        controls: 'Topic filters, rules',
    },
    {
        icon: '/images/logos/guest-icon.svg',
        name: 'Guest',
        description: 'Submit guest post opportunities.',
        bestFor: 'High-authority blogs',
        timeline: '7-14 days',
        controls: 'Pitch templates, rules',
    },
];
</script>
