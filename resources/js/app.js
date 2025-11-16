/**
 * Main Application JavaScript Entry Point
 *
 * @description จุดเริ่มต้นของ JavaScript สำหรับแอพพลิเคชัน
 * @uses Alpine.js - Lightweight JavaScript framework
 * @uses Alpine Stores - Global state management
 * @uses Alpine Directives - x-sortable for drag & drop
 */

// Import Alpine.js
import Alpine from 'alpinejs';

// Import Alpine Directives (V3)
import sortable from './alpine/directives/sortable';

// Import Alpine Stores (V3)
import './alpine/stores/theme';
import './alpine/stores/sidebar';
import './alpine/stores/language';

// Import Additional Stores (Theme Presets)
import './alpine-stores';

// Register Alpine Directives
Alpine.plugin(sortable);

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine.js
Alpine.start();

console.log('✅ Thaiprompt Affiliate App V3 loaded with SortableJS');
