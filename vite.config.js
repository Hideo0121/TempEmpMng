import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const host = process.env.VITE_DEV_HOST ?? 'localhost';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host,
        hmr: { host },
    },
});
