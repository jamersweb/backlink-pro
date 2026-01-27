<template>
    <section id="evidence" class="evidence-reporting py-20">
        <div class="marketing-container">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-4 text-text" data-reveal>
                Evidence & Reporting
            </h2>
            <p class="text-center text-muted mb-16 max-w-2xl mx-auto" data-reveal>
                Complete transparency with evidence logs and comprehensive reports.
            </p>

            <div class="grid md:grid-cols-3 gap-8">
                <div
                    v-for="(report, idx) in reports"
                    :key="idx"
                    data-reveal
                    class="marketing-card"
                >
                    <div class="text-4xl mb-4">{{ report.icon }}</div>
                    <h3 class="text-xl font-bold mb-4 text-text">{{ report.title }}</h3>
                    <ul class="space-y-2 mb-6 text-sm text-muted">
                        <li v-for="(item, iIdx) in report.items" :key="iIdx">• {{ item }}</li>
                    </ul>
                    <div class="mb-4">
                        <div class="text-xs text-muted mb-2">Export Formats</div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(format, fIdx) in report.formats"
                                :key="fIdx"
                                class="px-2 py-1 bg-surface2 rounded text-xs text-muted"
                            >
                                {{ format }}
                            </span>
                        </div>
                    </div>
                    <button class="btn-secondary w-full text-sm">
                        View Sample
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const reports = [
    {
        icon: '📊',
        title: 'Weekly Summary',
        items: [
            'Links placed this week',
            'Approval rate',
            'Risk score distribution',
            'Top opportunities',
            'Performance trends',
        ],
        formats: ['PDF', 'CSV'],
    },
    {
        icon: '📁',
        title: 'Link Evidence Pack',
        items: [
            'Screenshots per link',
            'HTML snippets',
            'Placement URLs',
            'Metadata logs',
            'Timeline of events',
        ],
        formats: ['PDF', 'ZIP'],
    },
    {
        icon: '📈',
        title: 'Monitoring Dashboard',
        items: [
            'Live link status',
            'Health checks',
            'Change alerts',
            'Removed links',
            'Real-time updates',
        ],
        formats: ['CSV', 'JSON'],
    },
];
</script>
