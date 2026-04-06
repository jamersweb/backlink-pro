<template>
    <section id="final-cta" class="final-cta">
        <div class="marketing-container final-cta-shell">
            <div class="final-cta-copy">
                <span class="final-cta-kicker">Need a rollout plan?</span>
                <h2>Get a guided backlink plan built around your goals.</h2>
                <p>
                    Talk with the team about approvals, reporting, placements, and the right workflow for your site.
                </p>
            </div>

            <div class="final-cta-actions">
                <Link href="/contact" class="final-cta-primary">Contact Sales</Link>
                <Link href="/pricing" class="final-cta-secondary">View Pricing</Link>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<style scoped>
.final-cta {
    padding: 6rem 0 0;
}

.final-cta-shell {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) auto;
    gap: 2rem;
    align-items: center;
    padding: 2rem;
    border-radius: 2rem;
    background:
        radial-gradient(circle at top left, rgba(255, 110, 64, 0.18), transparent 28%),
        linear-gradient(180deg, rgba(18, 14, 14, 0.98), rgba(10, 10, 10, 0.99));
    border: 1px solid rgba(255, 110, 64, 0.24);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.24);
}

.final-cta-kicker {
    display: inline-flex;
    margin-bottom: 0.9rem;
    color: #ff8a65;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.68rem;
    font-weight: 800;
}

.final-cta-copy h2 {
    color: #fff7f2;
    font-size: clamp(2rem, 4vw, 3.2rem);
    line-height: 1.02;
    letter-spacing: -0.04em;
    margin: 0 0 0.9rem;
}

.final-cta-copy p {
    margin: 0;
    max-width: 38rem;
    color: rgba(255, 244, 239, 0.62);
    line-height: 1.75;
}

.final-cta-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.final-cta-primary,
.final-cta-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 3.4rem;
    padding: 0 1.4rem;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 700;
}

.final-cta-primary {
    color: #16100d;
    background: linear-gradient(180deg, #fff7f2, #ffe7db);
    border: 1px solid rgba(255, 224, 208, 0.72);
}

.final-cta-secondary {
    color: #fff7f2;
    border: 1px solid rgba(255, 110, 64, 0.28);
    background: rgba(255, 110, 64, 0.08);
}

@media (max-width: 860px) {
    .final-cta-shell {
        grid-template-columns: 1fr;
    }

    .final-cta-actions {
        flex-wrap: wrap;
    }
}
</style>
