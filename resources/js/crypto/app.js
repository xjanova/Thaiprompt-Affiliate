/**
 * Crypto Wallet Main Application
 * Entry point for wallet frontend functionality
 */

import './wallet-connector.js';
import { walletUI } from './wallet-ui.js';
import { tradingDashboard, portfolioManager } from './charts.js';
import animations from './animations.js';

// Make components available globally for Alpine.js
window.walletUI = walletUI;
window.tradingDashboard = tradingDashboard;
window.portfolioManager = portfolioManager;
window.cryptoAnimations = animations;

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Crypto Wallet module loaded');
    console.log('📊 Trading & Portfolio charts available');
    console.log('✨ Premium animations enabled');
});
