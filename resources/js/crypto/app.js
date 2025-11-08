/**
 * Crypto Wallet Main Application
 * Entry point for wallet frontend functionality
 */

import './wallet-connector.js';
import { walletUI } from './wallet-ui.js';

// Make walletUI available globally for Alpine.js
window.walletUI = walletUI;

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('Crypto Wallet module loaded');
});
