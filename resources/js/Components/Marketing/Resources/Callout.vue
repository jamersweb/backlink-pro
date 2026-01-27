<template>
    <div :class="['marketing-card p-6', getCalloutClass(type)]">
        <div class="flex items-start gap-3">
            <div class="text-2xl">{{ getIcon(type) }}</div>
            <div>
                <h3 class="font-bold text-text mb-2">{{ getTitle(type) }}</h3>
                <p class="text-sm text-muted">{{ getContent(type) }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    type: {
        type: String,
        required: true,
    },
});

const getCalloutClass = (type) => {
    const classes = {
        safety: 'bg-success/10 border-success/20',
        approval: 'bg-primary/10 border-primary/20',
        evidence: 'bg-warning/10 border-warning/20',
    };
    return classes[type] || classes.safety;
};

const getIcon = (type) => {
    const icons = {
        safety: '🛡️',
        approval: '✅',
        evidence: '📋',
    };
    return icons[type] || '💡';
};

const getTitle = (type) => {
    const titles = {
        safety: 'Safety Tip',
        approval: 'Approval Checklist',
        evidence: 'Evidence Pack Example',
    };
    return titles[type] || 'Tip';
};

const getContent = (type) => {
    const contents = {
        safety: 'Always review risk scores and relevance before execution. Start conservative and adjust based on results.',
        approval: 'Review high-risk actions manually. Configure auto-approval for low-risk actions to improve efficiency.',
        evidence: 'Every action includes: placement URL, screenshot/snippet, timestamp, and operator/audit trail.',
    };
    return contents[type] || 'Keep this in mind when building links.';
};
</script>
