import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/marketing.css',
                'resources/js/app.jsx',
                'resources/js/app-vue.js'
            ],
            refresh: true,
        }),
        react({
            jsxRuntime: 'automatic',
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    optimizeDeps: {
        include: ['react', 'react-dom', 'react-dom/client', 'vue'],
    },
    esbuild: {
        jsx: 'automatic',
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
