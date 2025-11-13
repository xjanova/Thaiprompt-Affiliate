/**
 * Wealth Guide Pro - Main JavaScript Entry Point
 * Initializes all 3D visualizations and interactive components
 */

import Wealth3DMindMap from './wealth-3d-mindmap.js';
import Wealth3DIncomeFlow from './wealth-3d-income-flow.js';

// Global instances
let mindMapInstance = null;
let incomeFlowInstance = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing Wealth Guide Pro...');

    // Initialize 3D Mind Map
    if (document.getElementById('wealth-3d-mindmap')) {
        try {
            mindMapInstance = new Wealth3DMindMap('wealth-3d-mindmap', {
                theme: 'gold',
                autoRotate: true,
                showLabels: true
            });

            console.log('✅ 3D Mind Map initialized');

            // Setup mind map controls
            setupMindMapControls();

            // Listen for node click events
            document.getElementById('wealth-3d-mindmap').addEventListener('nodeClick', (event) => {
                const nodeData = event.detail;
                showNodeDetails(nodeData);
            });
        } catch (error) {
            console.error('❌ Error initializing 3D Mind Map:', error);
            showFallbackMessage('wealth-3d-mindmap', 'ไม่สามารถโหลด 3D Mind Map ได้ กรุณารีเฟรชหน้านี้');
        }
    }

    // Initialize 3D Income Flow
    if (document.getElementById('wealth-3d-income-flow')) {
        try {
            // Get user's actual income data if available
            const incomeData = getUserIncomeData();

            incomeFlowInstance = new Wealth3DIncomeFlow('wealth-3d-income-flow', {
                showAmounts: true,
                animationSpeed: 1.0,
                incomeData: incomeData
            });

            console.log('✅ 3D Income Flow initialized');
        } catch (error) {
            console.error('❌ Error initializing 3D Income Flow:', error);
            showFallbackMessage('wealth-3d-income-flow', 'ไม่สามารถโหลด Income Flow ได้ กรุณารีเฟรชหน้านี้');
        }
    }

    // Setup global functions
    setupGlobalFunctions();

    console.log('✨ Wealth Guide Pro loaded successfully!');
});

// Setup mind map controls
function setupMindMapControls() {
    const toggleRotateBtn = document.getElementById('toggle-rotate');
    const resetCameraBtn = document.getElementById('reset-camera');

    if (toggleRotateBtn && mindMapInstance) {
        toggleRotateBtn.addEventListener('click', () => {
            const isRotating = mindMapInstance.toggleAutoRotate();
            toggleRotateBtn.textContent = isRotating ? '⏸️ Pause Rotate' : '▶️ Auto Rotate';
            toggleRotateBtn.classList.toggle('bg-yellow-500/20');
        });
    }

    if (resetCameraBtn && mindMapInstance) {
        resetCameraBtn.addEventListener('click', () => {
            mindMapInstance.focusOnNode('ธุรกิจหลัก');
        });
    }
}

// Get user's income data from backend or use defaults
function getUserIncomeData() {
    // Try to get data from a global variable set by Blade
    if (typeof window.userIncomeData !== 'undefined') {
        return window.userIncomeData;
    }

    // Default example data
    return {
        direct: 15000,
        binary: 8000,
        rank: 25000,
        sponsor: 5000,
        marketplace: 12000,
        team: 18000
    };
}

// Show node details in a modal or sidebar
function showNodeDetails(nodeData) {
    console.log('Node clicked:', nodeData);

    // Create a beautiful modal
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80';
    modal.innerHTML = `
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 max-w-md w-full border-2 border-cyan-500/50 shadow-2xl transform scale-95 animate-scale-in">
            <div class="text-center mb-6">
                <div class="text-6xl mb-4">${nodeData.icon}</div>
                <h3 class="text-3xl font-bold text-cyan-400 mb-2">${nodeData.data.title}</h3>
                <p class="text-gray-300">${nodeData.data.description}</p>
            </div>

            ${nodeData.data.amount ? `
                <div class="p-4 bg-cyan-500/10 border border-cyan-500/30 rounded-xl mb-6">
                    <div class="text-sm text-cyan-300 mb-1">รายได้โดยประมาณ</div>
                    <div class="text-3xl font-bold text-cyan-400">${nodeData.data.amount}</div>
                </div>
            ` : ''}

            <button onclick="this.closest('.fixed').remove()"
                    class="w-full px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 text-white font-bold rounded-xl hover:from-cyan-600 hover:to-blue-600 transition-all">
                เข้าใจแล้ว ✓
            </button>
        </div>
    `;

    document.body.appendChild(modal);

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });

    // Animate in
    setTimeout(() => {
        modal.querySelector('div').classList.remove('scale-95');
        modal.querySelector('div').classList.add('scale-100');
    }, 10);
}

// Show fallback message if 3D fails to load
function showFallbackMessage(containerId, message) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center p-8">
                    <div class="text-6xl mb-4">⚠️</div>
                    <p class="text-white text-lg mb-4">${message}</p>
                    <button onclick="location.reload()"
                            class="px-6 py-3 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-all">
                        รีเฟรชหน้านี้
                    </button>
                </div>
            </div>
        `;
    }
}

// Setup global functions for buttons
function setupGlobalFunctions() {
    // Focus on mind map node
    window.focusMindMapNode = function(label) {
        if (mindMapInstance) {
            mindMapInstance.focusOnNode(label);
        }
    };

    // Calculate binary bonus
    window.calculateBinaryBonus = function() {
        const pairs = prompt('กรุณาใส่จำนวนคู่ต่อสัปดาห์:', '10');
        if (pairs !== null) {
            const bonus = parseInt(pairs) * 1000 * 4; // per month
            alert(`💰 Binary Bonus ต่อเดือน:\n\n${pairs} คู่/สัปดาห์ × 1,000฿ × 4 สัปดาห์\n= ฿${bonus.toLocaleString()}`);
        }
    };

    // Calculate team bonus
    window.calculateTeamBonus = function() {
        const teamSize = prompt('กรุณาใส่ขนาดทีม (คน):', '100');
        const avgSales = prompt('ยอดขายเฉลี่ยต่อคน/เดือน (฿):', '2000');

        if (teamSize !== null && avgSales !== null) {
            const totalSales = parseInt(teamSize) * parseInt(avgSales);
            const override = totalSales * 0.05; // 5% override
            alert(`💰 Team Override Bonus:\n\nทีม ${teamSize} คน × ฿${parseInt(avgSales).toLocaleString()}\n= ฿${totalSales.toLocaleString()} (ยอดรวม)\n\nOverride 5% = ฿${Math.floor(override).toLocaleString()}`);
        }
    };

    // Update income flow with new data
    window.updateIncomeFlow = function(newData) {
        if (incomeFlowInstance) {
            incomeFlowInstance.updateIncomeData(newData);
        }
    };
}

// Handle visibility change to pause/resume animations
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, pause animations
        if (incomeFlowInstance) {
            incomeFlowInstance.pauseAnimation();
        }
    } else {
        // Page is visible, resume animations
        if (incomeFlowInstance) {
            incomeFlowInstance.resumeAnimation();
        }
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (mindMapInstance) {
        mindMapInstance.destroy();
    }
    if (incomeFlowInstance) {
        incomeFlowInstance.destroy();
    }
});

// Add CSS animation for modal
const style = document.createElement('style');
style.textContent = `
    @keyframes scale-in {
        from {
            transform: scale(0.95);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
    .animate-scale-in {
        animation: scale-in 0.3s ease-out forwards;
    }
`;
document.head.appendChild(style);

// Export for external use
export { mindMapInstance, incomeFlowInstance };
