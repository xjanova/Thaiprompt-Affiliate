import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/crypto/app.js',
                'resources/js/wealth-guide-pro.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'crypto': ['ethers'],
                    'charts': ['chart.js'],
                    'animations': ['gsap'],
                    'threejs': ['three'],
                }
            }
        }
    },
    optimizeDeps: {
        include: ['ethers', 'chart.js', 'gsap', 'three'],
    },
});
