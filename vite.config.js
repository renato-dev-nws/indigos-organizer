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
                compilerOptions: {
                    isCustomElement: (tag) => tag === 'iconify-icon',
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
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            includeAssets: ['icons/io-icon-192x192.png', 'icons/io-icon-512x512.png'],
            manifest: {
                name: 'Índigos - Artist Organizer',
                short_name: 'Índigos',
                description: 'Painel de operação da banda',
                theme_color: '#4f46e5',
                background_color: '#1e1b4b',
                display: 'standalone',
                scope: '/',
                start_url: '/dashboard',
                icons: [
                    {
                        src: '/icons/io-icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/icons/io-icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                ],
            },
            injectManifest: {
                injectionPoint: 'self.__WB_MANIFEST',
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                modifyURLPrefix: {
                    '': '/build/',
                },
            },
            devOptions: {
                enabled: true,
                type: 'module',
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});
