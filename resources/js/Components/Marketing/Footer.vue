<template>
    <footer class="footer border-t border-border py-12">
        <div class="marketing-container">
            <div class="grid md:grid-cols-5 gap-8 mb-8">
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Product</h3>
                    <ul class="space-y-2">
                        <li><Link href="/product" class="text-muted hover:text-text transition-colors">Product</Link></li>
                        <li><Link href="/how-it-works" class="text-muted hover:text-text transition-colors">How It Works</Link></li>
                        <li><Link href="/pricing" class="text-muted hover:text-text transition-colors">Pricing</Link></li>
                        <li><Link href="/security" class="text-muted hover:text-text transition-colors">Security</Link></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Workflows</h3>
                    <ul class="space-y-2">
                        <li><Link href="/workflows" class="text-muted hover:text-text transition-colors">All Workflows</Link></li>
                        <li><Link href="/workflows/comment" class="text-muted hover:text-text transition-colors">Comment</Link></li>
                        <li><Link href="/workflows/profile" class="text-muted hover:text-text transition-colors">Profile</Link></li>
                        <li><Link href="/workflows/forum" class="text-muted hover:text-text transition-colors">Forum</Link></li>
                        <li><Link href="/workflows/guest" class="text-muted hover:text-text transition-colors">Guest Post</Link></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Resources</h3>
                    <ul class="space-y-2">
                        <li><Link href="/resources" class="text-muted hover:text-text transition-colors">Resources Hub</Link></li>
                        <li><Link href="/case-studies" class="text-muted hover:text-text transition-colors">Case Studies</Link></li>
                        <li><Link href="/partners" class="text-muted hover:text-text transition-colors">Partners</Link></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Company</h3>
                    <ul class="space-y-2">
                        <li><Link href="/about" class="text-muted hover:text-text transition-colors">About</Link></li>
                        <li><Link href="/contact" class="text-muted hover:text-text transition-colors">Contact</Link></li>
                        <li><Link href="/partners" class="text-muted hover:text-text transition-colors">Partners</Link></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4 text-text">Legal</h3>
                    <ul class="space-y-2">
                        <li><Link href="/privacy-policy" class="text-muted hover:text-text transition-colors">Privacy Policy</Link></li>
                        <li><Link href="/terms" class="text-muted hover:text-text transition-colors">Terms of Service</Link></li>
                        <li><Link href="/security" class="text-muted hover:text-text transition-colors">Security & Trust</Link></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-border pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-muted text-sm">
                    © {{ new Date().getFullYear() }} BacklinkPro. All rights reserved.
                </div>
                <div class="flex gap-6 text-sm">
                    <Link href="/terms" class="text-muted hover:text-text transition-colors">Terms</Link>
                    <Link href="/privacy-policy" class="text-muted hover:text-text transition-colors">Privacy</Link>
                    <Link href="/security" class="text-muted hover:text-text transition-colors">Security</Link>
                    <Link href="/contact" class="text-muted hover:text-text transition-colors">Contact</Link>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
