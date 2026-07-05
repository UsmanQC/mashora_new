import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
            fonts: [
                local('Thmanyah Sans', {
                    alias: 'thmanyah-sans',
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                    variants: [
                        { src: 'resources/fonts/thmanyah/ThmanyahSans-Light.woff2', weight: 300 },
                        { src: 'resources/fonts/thmanyah/ThmanyahSans-Regular.woff2', weight: 400 },
                        { src: 'resources/fonts/thmanyah/ThmanyahSans-Medium.woff2', weight: 500 },
                        { src: 'resources/fonts/thmanyah/ThmanyahSans-Bold.woff2', weight: 600 },
                        { src: 'resources/fonts/thmanyah/ThmanyahSans-Bold.woff2', weight: 700 },
                        { src: 'resources/fonts/thmanyah/ThmanyahSans-Black.woff2', weight: 900 },
                    ],
                    preload: [
                        { weight: 400 },
                        { weight: 500 },
                        { weight: 600 },
                    ],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
