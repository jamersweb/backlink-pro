<template>
    <footer class="footer">
        <div class="marketing-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <Link href="/" class="footer-logo">
                        <span class="footer-logo-mark">
                            <span class="footer-logo-core"></span>
                            <span class="footer-logo-ring"></span>
                        </span>
                        <span>BacklinkPro</span>
                    </Link>
                    <p>
                        Safe backlink growth with approvals, evidence logs, and cleaner reporting for modern SEO teams.
                    </p>
                </div>

                <div class="footer-col">
                    <h3>Product</h3>
                    <ul>
                        <li><Link href="/product">Product</Link></li>
                        <li><Link href="/how-it-works">How It Works</Link></li>
                        <li><Link href="/pricing">Pricing</Link></li>
                        <li><Link href="/security">Security</Link></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>Company</h3>
                    <ul>
                        <li><Link href="/about">About</Link></li>
                        <li><Link href="/partners">Partners</Link></li>
                        <li><Link href="/contact">Contact Sales</Link></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>Resources</h3>
                    <ul>
                        <li><Link href="/case-studies">Case Studies</Link></li>
                        <li><Link href="/resources">Resources Hub</Link></li>
                        <li><Link href="/privacy-policy">Privacy Policy</Link></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>&copy; {{ new Date().getFullYear() }} BacklinkPro. All rights reserved.</div>
                <div class="footer-links">
                    <Link href="/terms">Terms</Link>
                    <Link href="/privacy-policy">Privacy</Link>
                    <Link href="/security">Security</Link>
                    <Link href="/contact">Contact Sales</Link>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<style scoped>
.footer {
    margin-top: 5rem;
    padding: 5rem 0 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    background:
        radial-gradient(circle at bottom center, rgba(255, 110, 64, 0.08), transparent 32%),
        linear-gradient(180deg, rgba(12, 12, 12, 0), rgba(255, 110, 64, 0.05));
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.4fr repeat(3, minmax(0, 1fr));
    gap: 2rem;
    padding-bottom: 2.5rem;
}

.footer-logo,
.footer-col h3 {
    color: #fff7f2;
}

.footer-logo {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    margin-bottom: 1rem;
}

.footer-logo-mark {
    position: relative;
    width: 1.35rem;
    height: 1.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.footer-logo-core,
.footer-logo-ring {
    position: absolute;
    border-radius: 999px;
}

.footer-logo-core {
    inset: 0;
    background: rgba(255, 255, 255, 0.12);
}

.footer-logo-ring {
    inset: 0.15rem;
    border: 1.6px solid rgba(255, 255, 255, 0.9);
}

.footer-logo-mark::after {
    content: '';
    position: absolute;
    inset: 0.28rem;
    border-radius: 999px;
    border-top: 1.7px solid #ff8a65;
    border-left: 1.7px solid #ff8a65;
}

.footer-brand p,
.footer-col a,
.footer-bottom {
    color: rgba(255, 244, 239, 0.58);
    line-height: 1.75;
}

.footer-col h3 {
    margin: 0 0 1rem;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
}

.footer-col ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.7rem;
}

.footer-col a,
.footer-links a {
    text-decoration: none;
    transition: color 0.2s ease;
}

.footer-col a:hover,
.footer-links a:hover {
    color: #ffffff;
}

.footer-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    font-size: 0.85rem;
}

.footer-links {
    display: flex;
    gap: 1rem;
}

@media (max-width: 900px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
    }

    .footer-bottom {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 640px) {
    .footer-grid {
        grid-template-columns: 1fr;
    }

    .footer-links {
        flex-wrap: wrap;
    }
}
</style>
