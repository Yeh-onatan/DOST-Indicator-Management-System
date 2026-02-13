import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        https: false,
        strictPort: 5173,
        host: '127.0.0.1',
        hmr: {
            protocol: 'ws',
            host: '127.0.0.1',
        },
    },
})
