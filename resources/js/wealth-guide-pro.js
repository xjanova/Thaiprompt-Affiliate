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
    // Initialize 3D Mind Map
    if (document.getElementById('wealth-3d-mindmap')) {
        try {
            mindMapInstance = new Wealth3DMindMap('wealth-3d-mindmap', {
                theme: 'gold',
                autoRotate: true,
                showLabels: true
            });

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

        } catch (error) {
            console.error('❌ Error initializing 3D Income Flow:', error);
            showFallbackMessage('wealth-3d-income-flow', 'ไม่สามารถโหลด Income Flow ได้ กรุณารีเฟรชหน้านี้');
        }
    }

    // Setup global functions
    setupGlobalFunctions();
});

// Setup mind map controls
function setupMindMapControls() {
    const toggleRotateBtn = document.getElementById('toggle-rotate');
    const resetCameraBtn = document.getElementById('reset-camera');

    if (toggleRotateBtn && mindMapInstance) {
        toggleRotateBtn.addEventListener('click', () => {
            const isRotating = mindMapInstance.toggleAutoRotate();
            toggleRotateBtn.textContent = isRotating ? 'หยุดหมุน' : 'หมุนอัตโนมัติ';
            toggleRotateBtn.classList.toggle('is-on', !!isRotating);
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

// แสดงรายละเอียด node เป็นป๊อปอัปธีมโนวา (nvm-modal) — สร้างด้วย DOM + textContent ไม่ต่อ HTML จากข้อมูล
function nvEl(tag, className, text) {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined && text !== null) el.textContent = String(text);
    return el;
}

function showNodeDetails(nodeData) {
    const data = (nodeData && nodeData.data) || {};
    const modal = nvEl('div', 'nvm-modal');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');

    const box = nvEl('div', 'nvm-card nvm-modal__box');
    if (nodeData && nodeData.icon) box.appendChild(nvEl('div', 'nvm-um-modal-ic', nodeData.icon));
    const title = nvEl('h2', null, data.title || '');
    title.id = 'nvm-um-node-title';
    modal.setAttribute('aria-labelledby', title.id);
    box.appendChild(title);
    if (data.description) box.appendChild(nvEl('p', null, data.description));

    if (data.amount) {
        const kv = nvEl('dl', 'nvm-kv');
        kv.appendChild(nvEl('dt', null, 'รายได้โดยประมาณ'));
        kv.appendChild(nvEl('dd', 'nvm-num', data.amount));
        box.appendChild(kv);
    }

    const close = nvEl('button', 'nvm-btn nvm-btn--gold nvm-btn--block nvm-gap-t', 'เข้าใจแล้ว');
    close.type = 'button';
    box.appendChild(close);
    modal.appendChild(box);

    const remove = () => { modal.remove(); document.removeEventListener('keydown', onKey); };
    const onKey = (e) => { if (e.key === 'Escape') remove(); };
    close.addEventListener('click', remove);
    modal.addEventListener('click', (e) => { if (e.target === modal) remove(); });
    document.addEventListener('keydown', onKey);

    document.body.appendChild(modal);
    close.focus();
}

// แสดงข้อความแทน เมื่อโหลด 3D ไม่สำเร็จ (เช่น เบราว์เซอร์ไม่รองรับ WebGL)
function showFallbackMessage(containerId, message) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.textContent = '';
    const box = nvEl('div', 'nvm-empty nvm-um-3d__fail');
    box.appendChild(nvEl('b', null, 'โหลดภาพ 3 มิติไม่สำเร็จ'));
    box.appendChild(nvEl('span', null, message));
    const btn = nvEl('button', 'nvm-btn nvm-btn--ghost nvm-btn--sm', 'รีเฟรชหน้านี้');
    btn.type = 'button';
    btn.addEventListener('click', () => location.reload());
    const wrap = nvEl('div');
    wrap.appendChild(btn);
    box.appendChild(wrap);
    container.appendChild(box);
}

// Setup global functions for buttons
function setupGlobalFunctions() {
    // Focus on mind map node
    window.focusMindMapNode = function(label) {
        if (mindMapInstance) {
            mindMapInstance.focusOnNode(label);
        }
    };

    // ปุ่ม "คำนวณ" ของแต่ละช่องทาง → พาไปเครื่องคำนวณในหน้า (เดิมเป็น prompt()/alert() และคิด 1,000฿/คู่ ขัดกับ 100฿/คู่ที่หน้าแสดง)
    window.calculateBinaryBonus = function() {
        if (typeof window.nvmUmCalcFocus === 'function') window.nvmUmCalcFocus('calc-binary-pairs');
    };

    window.calculateTeamBonus = function() {
        if (typeof window.nvmUmCalcFocus === 'function') window.nvmUmCalcFocus('calc-team-size');
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

// Export for external use
export { mindMapInstance, incomeFlowInstance };
