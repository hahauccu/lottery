import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/lottery.js', 'resources/js/danmaku.js', 'resources/js/home.js', 'resources/css/home1.css', 'resources/js/home1.js', 'resources/css/testSound.css', 'resources/js/testSound.js'],
            refresh: true,
        }),
    ],
});
