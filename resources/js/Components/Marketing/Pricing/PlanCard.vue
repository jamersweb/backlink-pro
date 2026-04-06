<template>
    <article
        :class="[
            'plan-card',
            plan.highlight && 'plan-card-featured'
        ]"
        data-reveal
    >
        <div v-if="plan.badge" class="plan-card-badge">
            {{ plan.badge }}
        </div>

        <div class="plan-card-head">
            <h3>{{ plan.name }}</h3>
            <p class="plan-card-tagline">{{ plan.tagline }}</p>
            <div class="plan-card-price">
                <strong>${{ currentPrice.amount }}</strong>
                <span>{{ billingCycle === 'annual' ? '/month' : '/month' }}</span>
            </div>
            <p class="plan-card-annual">
                Annual: ${{ annualPrice.amount }} / month billed yearly
            </p>
        </div>

        <div class="plan-card-limits">
            <div
                v-for="(limit, idx) in normalizedLimits"
                :key="`${plan.id}-limit-${idx}`"
                class="plan-card-limit"
            >
                <span>{{ limit.label }}</span>
                <strong>{{ limit.value }}</strong>
            </div>
        </div>

        <div class="plan-card-types-wrap">
            <h4>Backlink Types</h4>
            <div class="plan-card-types">
                <span v-for="type in normalizedTypes" :key="`${plan.id}-${type}`">
                    {{ type }}
                </span>
            </div>
        </div>

        <div class="plan-card-includes">
            <h4>What You Get</h4>
            <ul class="plan-card-list">
                <li v-for="(item, idx) in plan.includes" :key="idx">
                    {{ sanitizedCopy(item) }}
                </li>
            </ul>
        </div>

        <div class="plan-card-actions">
            <a :href="primaryCta.href" class="plan-card-action plan-card-action-primary">
                {{ primaryCta.label }}
            </a>
            <a
                v-if="secondaryCta"
                :href="secondaryCta.href"
                class="plan-card-action plan-card-action-secondary"
            >
                {{ secondaryCta.label }}
            </a>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    plan: {
        type: Object,
        required: true,
    },
    billingCycle: {
        type: String,
        default: 'monthly',
    },
});

const currentPrice = computed(() => {
    return props.plan.prices[props.billingCycle] || props.plan.prices.monthly;
});

const annualPrice = computed(() => props.plan.prices.annual || props.plan.prices.monthly);

const interval = computed(() => (props.billingCycle === 'annual' ? 'yearly' : 'monthly'));
const checkoutHref = computed(() => `/subscription/checkout/${props.plan.id}?interval=${interval.value}`);

const limitOrder = ['Domains', 'Projects', 'Monthly Actions', 'Team Seats'];

const normalizedLimits = computed(() => {
    const raw = Array.isArray(props.plan.limits) ? props.plan.limits : [];
    const mapped = raw.map((limit) => ({
        label: normalizeLimitLabel(limit.label),
        value: limit.value,
    }));

    return mapped.sort((a, b) => limitOrder.indexOf(a.label) - limitOrder.indexOf(b.label));
});

const normalizedTypes = computed(() => {
    const fallback = ['Comment', 'Profile'];
    const raw = Array.isArray(props.plan.types) && props.plan.types.length ? props.plan.types : fallback;

    return raw.map((type) => {
        const lower = String(type).toLowerCase();
        if (lower === 'comment') return 'Comment';
        if (lower === 'profile') return 'Profile';
        if (lower === 'forum') return 'Forum';
        if (lower === 'guest') return 'Guest';
        return String(type);
    });
});

const primaryCta = computed(() => {
    if (currentPrice.value.amount > 0) {
        return { href: checkoutHref.value, label: 'Subscribe Now' };
    }
    return props.plan.ctas?.primary ?? { href: '#', label: 'Get started' };
});

const secondaryCta = computed(() => {
    if (props.plan.id === 'pro') {
        return { label: 'Security & Trust', href: '/security' };
    }
    return props.plan.ctas?.secondary ?? null;
});

const normalizeLimitLabel = (label) => {
    const key = String(label || '').trim().toLowerCase();
    if (key === 'projects') return 'Projects';
    if (key === 'domains') return 'Domains';
    if (key === 'monthly actions') return 'Monthly Actions';
    if (key === 'team seats') return 'Team Seats';
    return String(label || '');
};

const sanitizedCopy = (value) => String(value || '')
    .replace(/\s*\(placeholder[^)]*\)/gi, '')
    .replace(/\s{2,}/g, ' ')
    .trim();
</script>

<style scoped>
.plan-card {
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
    padding: 1.2rem;
    border-radius: 1.35rem;
    background:
        radial-gradient(circle at top, rgba(255, 110, 64, 0.08), transparent 42%),
        linear-gradient(180deg, rgba(18, 14, 14, 0.98), rgba(10, 10, 10, 0.99));
    border: 1px solid rgba(255, 110, 64, 0.32);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.plan-card:hover {
    transform: translateY(-2px);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.04),
        0 18px 44px rgba(255, 110, 64, 0.1);
}

.plan-card-featured {
    border-color: rgba(255, 110, 64, 0.78);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.04),
        0 20px 55px rgba(255, 110, 64, 0.12);
}

.plan-card-badge {
    position: absolute;
    top: 0.8rem;
    right: 0.9rem;
    z-index: 2;
    padding: 0.3rem 0.68rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.42);
    background: rgba(255, 110, 64, 0.16);
    color: #ffffff;
    font-size: 0.64rem;
    font-weight: 700;
}

.plan-card-head {
    margin-bottom: 1rem;
}

.plan-card-head h3,
.plan-card-types-wrap h4,
.plan-card-includes h4 {
    margin: 0;
    color: #ffffff;
    font-family: "Manrope", Inter, sans-serif;
}

.plan-card-head h3 {
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.plan-card-tagline {
    margin: 0.35rem 0 0;
    color: rgba(255, 234, 226, 0.78);
    font-size: 0.88rem;
    line-height: 1.45;
}

.plan-card-price {
    display: flex;
    align-items: flex-end;
    gap: 0.45rem;
    margin-top: 1rem;
    color: #ffffff;
}

.plan-card-price strong {
    font-size: 3rem;
    line-height: 0.95;
    font-weight: 800;
}

.plan-card-price span {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
}

.plan-card-annual {
    margin: 0.7rem 0 0;
    color: #ff8a65;
    font-size: 0.76rem;
    font-weight: 700;
}

.plan-card-limits {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
    margin-bottom: 1rem;
}

.plan-card-limit {
    padding: 0.78rem 0.88rem;
    border-radius: 0.78rem;
    border: 1px solid rgba(255, 110, 64, 0.28);
    background: rgba(24, 16, 16, 0.82);
}

.plan-card-limit span {
    display: block;
    margin-bottom: 0.35rem;
    color: rgba(255, 194, 171, 0.88);
    font-size: 0.68rem;
}

.plan-card-limit strong {
    color: #ffffff;
    font-size: 1rem;
    font-weight: 800;
}

.plan-card-types-wrap,
.plan-card-includes {
    margin-bottom: 1rem;
}

.plan-card-types-wrap h4,
.plan-card-includes h4 {
    margin-bottom: 0.7rem;
    font-size: 0.98rem;
    font-weight: 800;
}

.plan-card-types {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.plan-card-types span {
    padding: 0.26rem 0.62rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 110, 64, 0.45);
    background: rgba(255, 110, 64, 0.12);
    color: #ffb199;
    font-size: 0.72rem;
    font-weight: 700;
}

.plan-card-list {
    list-style: none;
    display: grid;
    gap: 0.72rem;
    margin: 0;
    padding: 0;
    flex: 1;
}

.plan-card-list li {
    position: relative;
    padding-left: 1rem;
    color: rgba(241, 245, 255, 0.92);
    font-size: 0.95rem;
    line-height: 1.45;
}

.plan-card-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0.47rem;
    width: 0.38rem;
    height: 0.38rem;
    border-radius: 999px;
    background: #ff6e40;
    box-shadow: 0 0 14px rgba(255, 110, 64, 0.35);
}

.plan-card-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.55rem;
    margin-top: auto;
    padding-top: 1rem;
}

.plan-card-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.7rem;
    border-radius: 0.58rem;
    text-decoration: none;
    font-size: 0.84rem;
    font-weight: 800;
    font-family: "Manrope", Inter, sans-serif;
    transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.plan-card-action:hover {
    transform: translateY(-1px);
}

.plan-card-action-primary {
    background: linear-gradient(180deg, #fff7f2, #ffe9de);
    border: 1px solid rgba(255, 214, 198, 0.7);
    color: #1a1210;
    box-shadow: 0 12px 28px rgba(255, 110, 64, 0.16);
}

.plan-card-action-secondary {
    background: rgba(255, 110, 64, 0.1);
    border: 1px solid rgba(255, 110, 64, 0.3);
    color: #ffffff;
}

@media (max-width: 640px) {
    .plan-card-actions,
    .plan-card-limits {
        grid-template-columns: 1fr;
    }
}
</style>
