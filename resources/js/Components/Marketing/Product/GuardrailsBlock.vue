<template>
    <section id="guardrails" class="guardrails-block py-20 scroll-mt-24">
        <div class="marketing-container max-w-5xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-center mb-12 text-text" data-reveal>
                Guardrails & Controls
            </h2>

            <div class="guardrails-shell" data-reveal>
                <div class="guardrails-intro marketing-card">
                    <span class="guardrails-kicker">Risk Framing</span>
                    <h3 class="text-text">Set the rules before actions go live.</h3>
                    <p class="text-muted">
                        BacklinkPro keeps approvals, thresholds, and review checkpoints visible so teams can scale without losing control.
                    </p>
                </div>

                <div class="guardrails-grid">
                    <div class="marketing-card guardrails-card" data-reveal>
                        <div class="guardrails-card-head">
                            <span class="guardrails-card-icon is-positive">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-xl font-bold text-success">What You Control</h3>
                                <p class="text-muted text-sm">Everything teams can tune, approve, and monitor.</p>
                            </div>
                        </div>

                        <ul class="guardrails-list">
                            <li
                                v-for="(item, idx) in guardrails.do"
                                :key="idx"
                                class="guardrails-list-item"
                            >
                                <span class="guardrails-list-dot is-positive"></span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="marketing-card guardrails-card" data-reveal>
                        <div class="guardrails-card-head">
                            <span class="guardrails-card-icon is-caution">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-xl font-bold text-danger">What We Avoid</h3>
                                <p class="text-muted text-sm">Risk patterns that do not fit a safe workflow.</p>
                            </div>
                        </div>

                        <ul class="guardrails-list">
                            <li
                                v-for="(item, idx) in guardrails.never"
                                :key="idx"
                                class="guardrails-list-item"
                            >
                                <span class="guardrails-list-dot is-caution"></span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="marketing-card guardrails-callout" data-reveal>
                    <span class="guardrails-callout-label">Approval Gate</span>
                    <p class="text-text font-semibold">
                        Approvals can pause or gate actions above your configured risk threshold.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    guardrails: {
        type: Object,
        required: true,
    },
});
</script>

<style scoped>
.guardrails-shell {
    display: grid;
    gap: 1.35rem;
}

.guardrails-intro,
.guardrails-card,
.guardrails-callout {
    padding: 1.5rem;
    border-radius: 1.5rem;
}

.guardrails-kicker,
.guardrails-callout-label {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    margin-bottom: 0.85rem;
    padding: 0.32rem 0.65rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.2);
    background: rgba(255, 110, 64, 0.08);
    color: rgba(255, 174, 143, 0.92);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.guardrails-intro h3,
.guardrails-intro p,
.guardrails-callout p {
    margin: 0;
}

.guardrails-intro {
    max-width: 46rem;
    margin: 0 auto;
    text-align: center;
}

.guardrails-intro h3 {
    margin-bottom: 0.7rem;
    font-size: 1.25rem;
    font-weight: 800;
}

.guardrails-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.25rem;
}

.guardrails-card-head {
    display: flex;
    gap: 0.9rem;
    align-items: flex-start;
    margin-bottom: 1.15rem;
}

.guardrails-card-head h3,
.guardrails-card-head p {
    margin: 0;
}

.guardrails-card-head h3 {
    margin-bottom: 0.35rem;
}

.guardrails-card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 999px;
    flex-shrink: 0;
}

.guardrails-card-icon.is-positive,
.guardrails-list-dot.is-positive {
    background: rgba(255, 110, 64, 0.14);
    color: #ff9d7b;
}

.guardrails-card-icon.is-caution,
.guardrails-list-dot.is-caution {
    background: rgba(255, 177, 152, 0.12);
    color: #ffb198;
}

.guardrails-list {
    list-style: none;
    display: grid;
    gap: 0.8rem;
    margin: 0;
    padding: 0;
}

.guardrails-list-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: rgba(255, 240, 232, 0.72);
    line-height: 1.55;
}

.guardrails-list-dot {
    width: 0.5rem;
    height: 0.5rem;
    margin-top: 0.45rem;
    border-radius: 999px;
    flex-shrink: 0;
}

.guardrails-callout {
    text-align: center;
}

.guardrails-callout-label {
    margin-bottom: 0.7rem;
}

@media (max-width: 900px) {
    .guardrails-grid {
        grid-template-columns: 1fr;
    }

    .guardrails-intro,
    .guardrails-card,
    .guardrails-callout {
        padding: 1.2rem;
    }
}
</style>
