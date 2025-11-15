/**
 * Main Application JavaScript Entry Point
 *
 * @description จุดเริ่มต้นของ JavaScript สำหรับแอพพลิเคชัน
 * @uses Alpine.js - Lightweight JavaScript framework
 * @uses Alpine Stores - Global state management
 */

// Import Alpine.js
import Alpine from 'alpinejs';

// Import Alpine Stores (V3)
import './alpine/stores/theme';
import './alpine/stores/sidebar';

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine.js
Alpine.start();

console.log('✅ Thaiprompt Affiliate App V3 loaded');
