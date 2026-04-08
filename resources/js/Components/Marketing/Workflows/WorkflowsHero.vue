<template>
    <section class="workflows-hero py-20 md:py-32 relative overflow-hidden">
        <div class="bg-grid-pattern absolute inset-0 opacity-30"></div>
        <div class="marketing-container relative z-10">
            <div class="text-center max-w-4xl mx-auto" data-reveal>
                <div class="workflow-hero-kicker">
                    <span class="workflow-hero-kicker-dot"></span>
                    Workflow library for safer backlink execution
                </div>

                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-text">
                    Backlink workflows with guardrails
                </h1>

                <p class="text-xl text-muted mb-8 leading-relaxed">
                    Choose the right workflow for your risk mode and timeline. Every path stays wrapped in approvals, evidence logs, and safer execution rules.
                </p>

                <div class="workflow-hero-pills">
                    <span>Human approvals</span>
                    <span>Evidence logs</span>
                    <span>Risk controls</span>
                    <span>Monitoring</span>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
// No props needed
</script>

<style scoped>
.workflows-hero {
    min-height: 60vh;
    display: flex;
    align-items: center;
}

.workflow-hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    margin-bottom: 1.2rem;
    padding: 0.42rem 0.78rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.18);
    background: rgba(255, 110, 64, 0.08);
    color: rgba(255, 221, 208, 0.84);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.workflow-hero-kicker-dot {
    width: 0.42rem;
    height: 0.42rem;
    border-radius: 999px;
    background: #ff8a65;
}

.workflow-hero-pills {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem;
}

.workflow-hero-pills span {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    padding: 0 1rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.2);
    background: rgba(255, 110, 64, 0.08);
    color: rgba(255, 240, 232, 0.72);
    font-size: 0.88rem;
    font-weight: 700;
}
</style>
