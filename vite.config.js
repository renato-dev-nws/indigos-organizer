import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        Components({
            resolvers: [PrimeVueResolver()],
            dts: false,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            // Don't auto-inject into HTML (we handle it in app.blade.php)
            injectRegister: false,
            includeAssets: ['icons/icon-192x192.png', 'icons/icon-512x512.png'],
            manifest: {
                name: 'Band Organizer',
                short_name: 'Band Org',
                description: 'Painel de operação da banda',
                theme_color: '#4f46e5',
                background_color: '#1e1b4b',
                display: 'standalone',
                start_url: '/dashboard',
                icons: [
                    {
                        src: '/icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                // Don't navigate-fallback (server handles routing)
                navigateFallback: null,
                navigateFallbackDenylist: [/^\/api\//],
            },
            devOptions: {
                enabled: true,
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});
