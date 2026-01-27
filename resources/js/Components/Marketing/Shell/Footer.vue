<template>
    <footer class="footer border-t border-border py-12">
        <div class="marketing-container">
            <div class="grid md:grid-cols-5 gap-8 mb-8">
                <!-- Product Column -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Product</h3>
                    <ul class="space-y-2">
                        <li><Link href="/product" class="text-muted hover:text-text transition-colors">Product</Link></li>
                        <li><Link href="/how-it-works" class="text-muted hover:text-text transition-colors">How It Works</Link></li>
                        <li><Link href="/workflows" class="text-muted hover:text-text transition-colors">Workflows</Link></li>
                        <li><Link href="/pricing" class="text-muted hover:text-text transition-colors">Pricing</Link></li>
                        <li><Link href="/security" class="text-muted hover:text-text transition-colors">Security</Link></li>
                    </ul>
                </div>
                
                <!-- Proof Column -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Proof</h3>
                    <ul class="space-y-2">
                        <li><Link href="/case-studies" class="text-muted hover:text-text transition-colors">Case Studies</Link></li>
                        <li><Link href="/resources" class="text-muted hover:text-text transition-colors">Resources</Link></li>
                        <li><Link href="/security" class="text-muted hover:text-text transition-colors">Security</Link></li>
                    </ul>
                </div>
                
                <!-- Company Column -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Company</h3>
                    <ul class="space-y-2">
                        <li><Link href="/about" class="text-muted hover:text-text transition-colors">About</Link></li>
                        <li><Link href="/partners" class="text-muted hover:text-text transition-colors">Partners</Link></li>
                        <li><Link href="/contact" class="text-muted hover:text-text transition-colors">Contact</Link></li>
                    </ul>
                </div>
                
                <!-- Legal Column -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Legal</h3>
                    <ul class="space-y-2">
                        <li v-for="item in site.legal" :key="item.href">
                            <Link :href="item.href" class="text-muted hover:text-text transition-colors">
                                {{ item.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
                
                <!-- Social Column (if any social links exist) -->
                <div v-if="hasSocialLinks">
                    <h3 class="text-lg font-bold mb-4 text-text">Connect</h3>
                    <ul class="space-y-2">
                        <li v-if="site.social.x">
                            <a :href="site.social.x" target="_blank" rel="noopener noreferrer" class="text-muted hover:text-text transition-colors">
                                X (Twitter)
                            </a>
                        </li>
                        <li v-if="site.social.linkedin">
                            <a :href="site.social.linkedin" target="_blank" rel="noopener noreferrer" class="text-muted hover:text-text transition-colors">
                                LinkedIn
                            </a>
                        </li>
                        <li v-if="site.social.youtube">
                            <a :href="site.social.youtube" target="_blank" rel="noopener noreferrer" class="text-muted hover:text-text transition-colors">
                                YouTube
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-border pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-muted text-sm">
                    © {{ new Date().getFullYear() }} {{ site.brand.name }}. All rights reserved.
                </div>
                <div class="flex gap-6 text-sm">
                    <Link v-for="item in site.legal" :key="item.href" :href="item.href" class="text-muted hover:text-text transition-colors">
                        {{ item.label }}
                    </Link>
                    <Link href="/security" class="text-muted hover:text-text transition-colors">Security</Link>
                    <Link href="/contact" class="text-muted hover:text-text transition-colors">Contact</Link>
                </div>
            </div>
            
            <!-- Disclaimer -->
            <div class="border-t border-border pt-4 mt-4">
                <p class="text-xs text-muted text-center">
                    Outcomes vary. No guaranteed links.
                </p>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const site = page.props.site;

const hasSocialLinks = computed(() => {
    return !!(site.social.x || site.social.linkedin || site.social.youtube);
});
</script>
