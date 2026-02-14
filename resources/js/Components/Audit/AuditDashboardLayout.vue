<template>
    <div class="audit-dashboard-layout min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white">
        <!-- Navbar -->
        <header class="backdrop-blur-lg bg-white/5 border-b border-white/10 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center flex-wrap gap-4">
                <h1 class="text-xl sm:text-2xl font-bold tracking-wide">
                    <Link href="/" class="hover:text-blue-400 transition">AuditPro</Link>
                </h1>
                <nav class="flex items-center gap-4 sm:gap-6">
                    <Link href="/dashboard" class="text-gray-300 hover:text-blue-400 transition text-sm sm:text-base">Dashboard</Link>
                    <Link href="/audit" class="text-gray-300 hover:text-blue-400 transition text-sm sm:text-base">Reports</Link>
                    <Link href="/seo-audit-report" class="text-gray-300 hover:text-blue-400 transition text-sm sm:text-base">New Audit</Link>
                    <Link href="/audit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-xl transition text-sm font-medium">
                        Generate Report
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Main content slot -->
        <main class="audit-dashboard-main">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-white/10 py-6 text-center text-gray-500 text-sm">
            © {{ new Date().getFullYear() }} AuditPro — Advanced SEO Intelligence
        </footer>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
