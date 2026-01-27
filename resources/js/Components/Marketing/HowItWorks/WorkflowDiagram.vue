<template>
    <section id="workflow-diagram" class="workflow-diagram py-20 bg-surface2">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-16 text-text" data-reveal>
                The Complete Workflow
            </h2>

            <!-- Desktop: Horizontal Pipeline -->
            <div class="hidden md:block" data-reveal>
                <div class="relative">
                    <!-- Connection Line -->
                    <div class="absolute top-1/2 left-0 right-0 h-0.5 bg-border transform -translate-y-1/2 z-0">
                        <div
                            :style="{ width: `${(activeNodeIndex / (nodes.length - 1)) * 100}%` }"
                            class="h-full bg-primary transition-all duration-500"
                        ></div>
                    </div>

                    <!-- Nodes -->
                    <div class="relative flex justify-between">
                        <div
                            v-for="(node, idx) in nodes"
                            :key="idx"
                            @click="setActiveNode(idx)"
                            :class="[
                                'flex-1 cursor-pointer transition-all',
                                activeNodeIndex === idx && 'scale-110'
                            ]"
                        >
                            <div
                                :class="[
                                    'relative z-10 mx-auto w-24 h-24 rounded-full flex items-center justify-center font-bold text-sm transition-all mb-4',
                                    activeNodeIndex === idx
                                        ? 'bg-primary text-white shadow-lg'
                                        : 'bg-surface border-2 border-border text-text hover:border-primary'
                                ]"
                            >
                                {{ idx + 1 }}
                            </div>
                            <h3
                                :class="[
                                    'text-center text-sm font-semibold mb-2',
                                    activeNodeIndex === idx ? 'text-primary' : 'text-text'
                                ]"
                            >
                                {{ node.title }}
                            </h3>
                        </div>
                    </div>
                </div>

                <!-- Detail Panel -->
                <div v-if="activeNode" class="mt-12 marketing-card p-8" data-reveal>
                    <h3 class="text-2xl font-bold mb-4 text-text">{{ activeNode.title }}</h3>
                    <p class="text-muted mb-6">{{ activeNode.description }}</p>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold text-text mb-3">What Happens</h4>
                            <ul class="space-y-2 text-sm text-muted">
                                <li v-for="(item, idx) in activeNode.whatHappens" :key="idx">• {{ item }}</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-text mb-3">Controls</h4>
                            <ul class="space-y-2 text-sm text-muted">
                                <li v-for="(item, idx) in activeNode.controls" :key="idx">• {{ item }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile: Vertical Pipeline -->
            <div class="md:hidden space-y-6">
                <div
                    v-for="(node, idx) in nodes"
                    :key="idx"
                    data-reveal
                    @click="setActiveNode(idx)"
                    class="marketing-card p-6 cursor-pointer"
                    :class="activeNodeIndex === idx && 'border-2 border-primary'"
                >
                    <div class="flex items-start gap-4">
                        <div
                            :class="[
                                'w-12 h-12 rounded-full flex items-center justify-center font-bold flex-shrink-0',
                                activeNodeIndex === idx
                                    ? 'bg-primary text-white'
                                    : 'bg-surface2 text-text'
                            ]"
                        >
                            {{ idx + 1 }}
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-text mb-2">{{ node.title }}</h3>
                            <p class="text-sm text-muted mb-3">{{ node.description }}</p>
                            <div v-if="activeNodeIndex === idx" class="space-y-3 text-sm">
                                <div>
                                    <h4 class="font-semibold text-text mb-2">What Happens</h4>
                                    <ul class="space-y-1 text-muted">
                                        <li v-for="(item, iIdx) in node.whatHappens" :key="iIdx">• {{ item }}</li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-text mb-2">Controls</h4>
                                    <ul class="space-y-1 text-muted">
                                        <li v-for="(item, iIdx) in node.controls" :key="iIdx">• {{ item }}</li>
                                    </ul>
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
import { ref, computed } from 'vue';

const activeNodeIndex = ref(0);

const nodes = [
    {
        title: 'Discover Opportunities',
        description: 'AI scans the web for relevant link opportunities across multiple sources.',
        whatHappens: [
            'Prospecting from blogs, forums, directories',
            'Topical relevance analysis',
            'Quality signal detection',
            'Spam filtering',
        ],
        controls: [
            'Source selection',
            'Relevance filters',
            'Quality thresholds',
        ],
    },
    {
        title: 'Quality Filtering',
        description: 'Each opportunity is scored and filtered based on safety and relevance.',
        whatHappens: [
            'Risk score calculation',
            'Domain authority check',
            'Indexing verification',
            'Blacklist/whitelist matching',
        ],
        controls: [
            'Risk thresholds',
            'Domain lists',
            'Quality rules',
        ],
    },
    {
        title: 'AI Action Selection',
        description: 'AI chooses the best workflow based on opportunity type and risk score.',
        whatHappens: [
            'Workflow matching (comment/profile/forum/guest)',
            'Template selection',
            'Content generation',
            'Risk evaluation',
        ],
        controls: [
            'Workflow preferences',
            'Template rules',
            'Auto-approval thresholds',
        ],
    },
    {
        title: 'Execution',
        description: 'Automated execution of the selected workflow with form-fill and submission.',
        whatHappens: [
            'Form detection and filling',
            'Content submission',
            'Verification email handling',
            'Placement confirmation',
        ],
        controls: [
            'Execution rules',
            'Retry logic',
            'Timeout settings',
        ],
    },
    {
        title: 'Approval Queue',
        description: 'Human review and approval before final placement.',
        whatHappens: [
            'Pending review',
            'Approve/reject decision',
            'Request changes',
            'Auto-approve low-risk',
        ],
        controls: [
            'Approval workflows',
            'Notification settings',
            'Auto-approve rules',
        ],
    },
    {
        title: 'Evidence Logs',
        description: 'Complete evidence capture for every placement attempt.',
        whatHappens: [
            'Screenshot capture',
            'HTML snippet storage',
            'URL recording',
            'Metadata logging',
        ],
        controls: [
            'Evidence requirements',
            'Storage settings',
            'Export formats',
        ],
    },
    {
        title: 'Reporting + Monitoring',
        description: 'Ongoing monitoring and comprehensive reporting.',
        whatHappens: [
            'Link health checks',
            'Change detection',
            'Weekly summaries',
            'Performance analytics',
        ],
        controls: [
            'Monitoring frequency',
            'Alert rules',
            'Report schedules',
        ],
    },
];

const activeNode = computed(() => nodes[activeNodeIndex.value]);

const setActiveNode = (idx) => {
    activeNodeIndex.value = idx;
};
</script>
