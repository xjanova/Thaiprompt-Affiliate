import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/theme-v4.css', // ธีมนวลทองคำ V4 (หลังบ้านธีมเดียว)
                'resources/css/storefront-aurora.css', // กระจก + 3D หนา + ลาวาแลมป์ (โหลดเฉพาะหน้าร้าน)
                'resources/js/app.js',
                'resources/js/crypto/app.js',
                'resources/js/wealth-guide-pro.js',
                'resources/js/service-worker-register.js', // Service Worker
                'resources/js/echo-setup.js', // Laravel Echo
                'resources/js/workflow-diagram.js', // n8n-style Workflow Diagram
                'resources/js/mlm-genealogy-premium.js', // MLM Binary/Unilevel Tree Viewer (legacy)
                'resources/js/mlm-genealogy-v3.js', // MLM Genealogy V3 - ผังสายงานยกเครื่องใหม่
                'resources/js/pos/app.js', // POS PWA App
                'resources/js/pos/database.js', // POS IndexedDB
                'resources/js/pos/sync.js', // POS Sync Service
                'resources/js/pos/hardware.js', // POS Hardware Integration
                'resources/js/taladsod/app.js', // ตลาดสดไทยพร๊อม - Fresh Market
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
