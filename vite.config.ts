import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({

    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/src/main.ts'],
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
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js/src'),
            '@shared': path.resolve(__dirname, 'resources/js/src/shared'),
            '@modules': path.resolve(__dirname, 'resources/js/src/modules'),
        },
    },
})
