/**
 * Main Application JavaScript Entry Point
 *
 * @description จุดเริ่มต้นของ JavaScript สำหรับแอพพลิเคชัน
 * @uses Alpine.js - Lightweight JavaScript framework
 * @uses Alpine Stores - Global state management
 */

// Import Alpine.js
import Alpine from 'alpinejs';

// Import Alpine Plugins
import intersect from '@alpinejs/intersect';

// ลงทะเบียน Alpine Plugins (ต้องทำก่อน Alpine.start())
Alpine.plugin(intersect);

// Import Alpine Stores (V3)
import './alpine/stores/theme';
import './alpine/stores/sidebar';
import './alpine/stores/language';
import './alpine/stores/pinned-menus';

// Import Additional Stores (Theme Presets)
import './alpine-stores';

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine.js
Alpine.start();

console.log('✅ Thaiprompt Affiliate App V3 loaded (Google Translate Widget - ฟรี!)');
