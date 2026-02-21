/**
 * MLM Genealogy Premium Viewer
 * Google Maps-style interactive tree with advanced features
 * Features: Pan, Zoom, Search, Mini-map, Beautiful UI
 */

class MlmGenealogyPremium {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            type: options.type || 'binary', // 'unilevel' or 'binary'
            memberCode: options.memberCode,
            apiUrl: options.apiUrl || '/api/mlm/genealogy',
            maxDepth: options.maxDepth || 5,
            nodeWidth: 180,
            nodeHeight: 120,
            horizontalSpacing: 80,
            verticalSpacing: 100,
            showMinimap: true,
            showStats: true,
            enableSearch: true,
            animationDuration: 300,
            ...options
        };

        this.data = null;
        this.svg = null;
        this.transform = { x: 0, y: 0, k: 1 };
        this.selectedNode = null;
        this.highlightedNodes = new Set();

        this.init();
    }

    async init() {
        this.createContainer();
        this.createControls();
        await this.loadData();
        this.render();
        this.initializeInteractions();
    }

    createContainer() {
        this.container.innerHTML = `
            <div class="mlm-genealogy-premium">
                <!-- Header -->
                <div class="mlm-header">
                    <div class="mlm-header-left">
                        <h2 class="mlm-title">
                            <span class="mlm-icon">🌳</span>
                            ผังสายงาน MLM
                        </h2>
                        <div class="mlm-breadcrumb" id="mlm-breadcrumb"></div>
                    </div>
                    <div class="mlm-header-right">
                        <div class="mlm-type-switcher">
                            <button class="mlm-type-btn active" data-type="binary">
                                <span>🔀</span> Binary
                            </button>
                            <button class="mlm-type-btn" data-type="unilevel">
                                <span>📊</span> Unilevel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="mlm-toolbar">
                    <div class="mlm-search-box">
                        <input type="text" id="mlm-search" placeholder="🔍 ค้นหาสมาชิก (รหัส, ชื่อ, อีเมล...)" />
                        <div class="mlm-search-results" id="search-results"></div>
                    </div>

                    <div class="mlm-toolbar-actions">
                        <button class="mlm-btn" id="btn-expand-all" title="ขยายทั้งหมด">
                            <span>📂</span> ขยายทั้งหมด
                        </button>
                        <button class="mlm-btn" id="btn-collapse-all" title="ย่อทั้งหมด">
                            <span>📁</span> ย่อทั้งหมด
                        </button>
                        <button class="mlm-btn" id="btn-fullscreen" title="เต็มจอ">
                            <span>⛶</span> เต็มจอ
                        </button>
                        <button class="mlm-btn" id="btn-export" title="ส่งออก">
                            <span>💾</span> Export
                        </button>
                    </div>
                </div>

                <!-- Main Canvas Area -->
                <div class="mlm-canvas-wrapper">
                    <!-- SVG Canvas -->
                    <div class="mlm-canvas" id="mlm-canvas">
                        <svg id="mlm-svg"></svg>
                    </div>

                    <!-- Zoom Controls -->
                    <div class="mlm-zoom-controls">
                        <button class="mlm-zoom-btn" id="btn-zoom-in" title="ซูมเข้า">
                            <span>+</span>
                        </button>
                        <div class="mlm-zoom-level" id="zoom-level">100%</div>
                        <button class="mlm-zoom-btn" id="btn-zoom-out" title="ซูมออก">
                            <span>−</span>
                        </button>
                        <button class="mlm-zoom-btn" id="btn-zoom-reset" title="รีเซ็ต">
                            <span>⟲</span>
                        </button>
                    </div>

                    <!-- Mini Map -->
                    <div class="mlm-minimap" id="minimap">
                        <canvas id="minimap-canvas"></canvas>
                        <div class="mlm-minimap-viewport" id="minimap-viewport"></div>
                    </div>

                    <!-- Stats Panel -->
                    <div class="mlm-stats-panel" id="stats-panel">
                        <h3>📊 สถิติ</h3>
                        <div class="mlm-stat-item">
                            <span class="mlm-stat-label">สมาชิกทั้งหมด:</span>
                            <span class="mlm-stat-value" id="stat-total-members">0</span>
                        </div>
                        <div class="mlm-stat-item">
                            <span class="mlm-stat-label">สมาชิก Active:</span>
                            <span class="mlm-stat-value text-green" id="stat-active-members">0</span>
                        </div>
                        <div class="mlm-stat-item">
                            <span class="mlm-stat-label">PV รวม:</span>
                            <span class="mlm-stat-value text-purple" id="stat-total-pv">0</span>
                        </div>
                        <div class="mlm-stat-item">
                            <span class="mlm-stat-label">ความลึก:</span>
                            <span class="mlm-stat-value" id="stat-depth">0</span>
                        </div>
                    </div>

                    <!-- Loading -->
                    <div class="mlm-loading" id="mlm-loading">
                        <div class="mlm-spinner"></div>
                        <p>กำลังโหลดข้อมูล...</p>
                    </div>
                </div>

                <!-- Node Detail Modal -->
                <div class="mlm-modal" id="node-modal">
                    <div class="mlm-modal-content">
                        <span class="mlm-modal-close" id="modal-close">&times;</span>
                        <div id="modal-body"></div>
                    </div>
                </div>
            </div>
        `;

        this.attachStyles();
    }

    attachStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .mlm-genealogy-premium {
                width: 100%;
                height: 800px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                display: flex;
                flex-direction: column;
            }

            .mlm-header {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                padding: 20px 24px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .mlm-title {
                font-size: 24px;
                font-weight: 800;
                color: #1f2937;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .mlm-icon {
                font-size: 32px;
            }

            .mlm-breadcrumb {
                font-size: 14px;
                color: #6b7280;
                margin-top: 4px;
            }

            .mlm-type-switcher {
                display: flex;
                gap: 8px;
                background: #f3f4f6;
                padding: 4px;
                border-radius: 12px;
            }

            .mlm-type-btn {
                padding: 8px 16px;
                border: none;
                background: transparent;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                color: #6b7280;
                transition: all 0.2s;
            }

            .mlm-type-btn.active {
                background: white;
                color: #7c3aed;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .mlm-toolbar {
                background: rgba(255, 255, 255, 0.9);
                padding: 16px 24px;
                display: flex;
                gap: 16px;
                align-items: center;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }

            .mlm-search-box {
                flex: 1;
                position: relative;
            }

            .mlm-search-box input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                font-size: 14px;
                transition: all 0.2s;
            }

            .mlm-search-box input:focus {
                outline: none;
                border-color: #7c3aed;
                box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            }

            .mlm-search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                max-height: 300px;
                overflow-y: auto;
                margin-top: 8px;
                z-index: 10;
                display: none;
            }

            .mlm-search-results.show {
                display: block;
            }

            .mlm-search-item {
                padding: 12px 16px;
                cursor: pointer;
                border-bottom: 1px solid #f3f4f6;
                transition: background 0.2s;
            }

            .mlm-search-item:hover {
                background: #f9fafb;
            }

            .mlm-toolbar-actions {
                display: flex;
                gap: 8px;
            }

            .mlm-btn {
                padding: 8px 16px;
                border: 2px solid #e5e7eb;
                background: white;
                border-radius: 10px;
                cursor: pointer;
                font-weight: 600;
                font-size: 13px;
                color: #374151;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .mlm-btn:hover {
                border-color: #7c3aed;
                color: #7c3aed;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
            }

            .mlm-canvas-wrapper {
                flex: 1;
                position: relative;
                overflow: hidden;
            }

            .mlm-canvas {
                width: 100%;
                height: 100%;
                position: relative;
                overflow: hidden;
                cursor: grab;
            }

            .mlm-canvas:active {
                cursor: grabbing;
            }

            #mlm-svg {
                width: 100%;
                height: 100%;
            }

            .mlm-zoom-controls {
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                padding: 8px;
                display: flex;
                flex-direction: column;
                gap: 4px;
                z-index: 5;
            }

            .mlm-zoom-btn {
                width: 40px;
                height: 40px;
                border: none;
                background: white;
                border-radius: 8px;
                cursor: pointer;
                font-size: 20px;
                font-weight: bold;
                color: #7c3aed;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mlm-zoom-btn:hover {
                background: #7c3aed;
                color: white;
                transform: scale(1.1);
            }

            .mlm-zoom-level {
                text-align: center;
                font-size: 12px;
                font-weight: 600;
                color: #6b7280;
                padding: 4px;
            }

            .mlm-minimap {
                position: absolute;
                bottom: 20px;
                right: 20px;
                width: 200px;
                height: 150px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                overflow: hidden;
                z-index: 5;
            }

            #minimap-canvas {
                width: 100%;
                height: 100%;
                cursor: pointer;
            }

            .mlm-minimap-viewport {
                position: absolute;
                border: 2px solid #7c3aed;
                background: rgba(124, 58, 237, 0.1);
                pointer-events: none;
                transition: left 0.15s ease, top 0.15s ease;
            }

            .mlm-stats-panel {
                position: absolute;
                bottom: 20px;
                left: 20px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                padding: 16px;
                min-width: 200px;
                z-index: 5;
            }

            .mlm-stats-panel h3 {
                margin: 0 0 12px 0;
                font-size: 16px;
                font-weight: 700;
                color: #1f2937;
            }

            .mlm-stat-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
                font-size: 13px;
            }

            .mlm-stat-label {
                color: #6b7280;
            }

            .mlm-stat-value {
                font-weight: 700;
                color: #1f2937;
            }

            .mlm-stat-value.text-green {
                color: #10b981;
            }

            .mlm-stat-value.text-purple {
                color: #7c3aed;
            }

            /* Node Styles */
            .mlm-node {
                cursor: pointer;
                transition: all 0.3s;
            }

            .mlm-node:hover .mlm-node-card {
                transform: scale(1.05);
                filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.2));
            }

            .mlm-node-card {
                transition: all 0.3s;
            }

            .mlm-node-active .mlm-node-card {
                filter: drop-shadow(0 4px 15px rgba(16, 185, 129, 0.5));
            }

            .mlm-node-inactive .mlm-node-card {
                opacity: 0.6;
            }

            .mlm-node-selected .mlm-node-card {
                filter: drop-shadow(0 0 20px rgba(124, 58, 237, 0.8));
            }

            .mlm-connection {
                stroke: #9ca3af;
                stroke-width: 2;
                fill: none;
                transition: all 0.3s;
            }

            .mlm-connection-highlight {
                stroke: #7c3aed;
                stroke-width: 3;
            }

            /* Loading */
            .mlm-loading {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.95);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 20;
            }

            .mlm-spinner {
                width: 50px;
                height: 50px;
                border: 4px solid #e5e7eb;
                border-top-color: #7c3aed;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            /* Modal */
            .mlm-modal {
                display: none;
                position: fixed;
                z-index: 100;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(5px);
            }

            .mlm-modal.show {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mlm-modal-content {
                background: white;
                border-radius: 16px;
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                position: relative;
                padding: 24px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }

            .mlm-modal-close {
                position: absolute;
                top: 16px;
                right: 16px;
                font-size: 28px;
                font-weight: bold;
                color: #9ca3af;
                cursor: pointer;
                transition: color 0.2s;
            }

            .mlm-modal-close:hover {
                color: #1f2937;
            }
        `;
        document.head.appendChild(style);
    }

    createControls() {
        // Zoom controls
        this.container.querySelector('#btn-zoom-in').addEventListener('click', () => this.zoomIn());
        this.container.querySelector('#btn-zoom-out').addEventListener('click', () => this.zoomOut());
        this.container.querySelector('#btn-zoom-reset').addEventListener('click', () => this.resetView());

        // Type switcher
        this.container.querySelectorAll('.mlm-type-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.container.querySelectorAll('.mlm-type-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.switchType(btn.dataset.type);
            });
        });

        // Search
        const searchInput = this.container.querySelector('#mlm-search');
        searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));

        // Fullscreen
        this.container.querySelector('#btn-fullscreen').addEventListener('click', () => this.toggleFullscreen());

        // Modal close
        this.container.querySelector('#modal-close').addEventListener('click', () => this.closeModal());
    }

    async loadData() {
        const loading = this.container.querySelector('#mlm-loading');
        loading.style.display = 'flex';

        try {
            const response = await fetch(`${this.options.apiUrl}?type=${this.options.type}&depth=${this.options.maxDepth}`);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();

            if (!result.success || !result.data) {
                throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลผังสายงานได้');
            }

            this.data = result.data;
            this.updateStats();
        } catch (error) {
            console.error('Error loading genealogy data:', error);

            // แสดงข้อความ error แทน demo data
            const errorDiv = document.createElement('div');
            errorDiv.className = 'flex flex-col items-center justify-center p-8 text-center';
            errorDiv.innerHTML = `
                <div class="text-5xl mb-4">⚠️</div>
                <div class="text-lg font-bold text-gray-800 dark:text-white mb-2">โหลดผังสายงานไม่สำเร็จ</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">${error.message}</div>
                <button onclick="location.reload()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm transition">
                    ลองใหม่
                </button>
            `;
            const canvas = this.container.querySelector('#mlm-canvas') || this.container.querySelector('svg');
            if (canvas) {
                canvas.parentNode.insertBefore(errorDiv, canvas);
                canvas.style.display = 'none';
            }

            this.data = null;
        } finally {
            loading.style.display = 'none';
        }
    }

    generateDemoData() {
        // Generate demo tree data for testing
        return {
            id: 1,
            name: 'John Doe',
            member_code: 'MLM001',
            email: 'john@example.com',
            status: 'active',
            total_pv: 10000,
            direct_referrals: 2,
            total_team_members: 15,
            left: {
                id: 2,
                name: 'Alice Smith',
                member_code: 'MLM002',
                status: 'active',
                total_pv: 5000,
                direct_referrals: 2,
                left: {
                    id: 4,
                    name: 'Bob Johnson',
                    member_code: 'MLM004',
                    status: 'active',
                    total_pv: 2000
                },
                right: {
                    id: 5,
                    name: 'Carol Williams',
                    member_code: 'MLM005',
                    status: 'inactive',
                    total_pv: 1500
                }
            },
            right: {
                id: 3,
                name: 'David Brown',
                member_code: 'MLM003',
                status: 'active',
                total_pv: 3000,
                direct_referrals: 1,
                left: {
                    id: 6,
                    name: 'Eve Davis',
                    member_code: 'MLM006',
                    status: 'active',
                    total_pv: 2500
                }
            }
        };
    }

    render() {
        const svg = this.container.querySelector('#mlm-svg');
        svg.innerHTML = '';

        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('id', 'main-group');
        svg.appendChild(g);

        if (this.options.type === 'binary') {
            this.renderBinaryTree(this.data, 400, 50, g);
        } else {
            this.renderUnilevelTree(this.data, 400, 50, g);
        }

        this.resetView();
    }

    renderBinaryTree(node, x, y, parent, depth = 0) {
        if (!node || depth > this.options.maxDepth) return;

        const spacing = this.options.horizontalSpacing / Math.pow(1.5, depth);

        // Draw connections first (so they appear behind nodes)
        if (node.left) {
            const leftX = x - spacing * 100;
            const childY = y + this.options.verticalSpacing;
            this.drawConnection(parent, x, y + this.options.nodeHeight / 2, leftX, childY, 'left');
            this.renderBinaryTree(node.left, leftX, childY, parent, depth + 1);
        }

        if (node.right) {
            const rightX = x + spacing * 100;
            const childY = y + this.options.verticalSpacing;
            this.drawConnection(parent, x, y + this.options.nodeHeight / 2, rightX, childY, 'right');
            this.renderBinaryTree(node.right, rightX, childY, parent, depth + 1);
        }

        // Draw node
        this.drawNode(parent, node, x, y);
    }

    renderUnilevelTree(node, x, y, parent, depth = 0) {
        if (!node || depth > this.options.maxDepth) return;

        this.drawNode(parent, node, x, y);

        const children = node.children || [];
        if (children.length === 0) return;

        const totalWidth = children.length * (this.options.nodeWidth + this.options.horizontalSpacing);
        const startX = x - totalWidth / 2 + this.options.nodeWidth / 2;
        const childY = y + this.options.verticalSpacing;

        children.forEach((child, index) => {
            const childX = startX + index * (this.options.nodeWidth + this.options.horizontalSpacing);
            this.drawConnection(parent, x, y + this.options.nodeHeight, childX, childY);
            this.renderUnilevelTree(child, childX, childY, parent, depth + 1);
        });
    }

    drawNode(parent, node, x, y) {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', `mlm-node mlm-node-${node.retention_status || node.status}`);
        g.setAttribute('data-node-id', node.id);
        g.setAttribute('transform', `translate(${x - this.options.nodeWidth / 2}, ${y})`);

        // Determine colors based on retention status
        const retentionStatus = node.retention_status || node.status;
        let statusColor, cardGradient, borderColor, statusBg, statusText;

        switch (retentionStatus) {
            case 'active':
                statusColor = '#10b981';
                cardGradient = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                borderColor = '#10b981';
                statusBg = '#dcfce7';
                statusText = '#059669';
                break;
            case 'grace_period':
                statusColor = '#f59e0b';
                cardGradient = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                borderColor = '#f59e0b';
                statusBg = '#fef3c7';
                statusText = '#d97706';
                break;
            case 'inactive':
                statusColor = '#ef4444';
                cardGradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
                borderColor = '#ef4444';
                statusBg = '#fee2e2';
                statusText = '#dc2626';
                break;
            default:
                statusColor = '#6b7280';
                cardGradient = 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)';
                borderColor = '#6b7280';
                statusBg = '#f3f4f6';
                statusText = '#6b7280';
        }

        g.innerHTML = `
            <foreignObject width="${this.options.nodeWidth}" height="${this.options.nodeHeight}">
                <div xmlns="http://www.w3.org/1999/xhtml" class="mlm-node-card" style="
                    width: 100%;
                    height: 100%;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    font-family: system-ui, -apple-system, sans-serif;
                    border: 3px solid ${borderColor};
                ">
                    <div style="
                        background: ${cardGradient};
                        padding: 12px;
                        color: white;
                    ">
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px;">
                            ${node.name || 'Unknown'}
                        </div>
                        <div style="font-size: 11px; opacity: 0.9;">
                            ${node.member_code}
                        </div>
                    </div>
                    <div style="padding: 12px; font-size: 11px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #6b7280;">PV:</span>
                            <span style="font-weight: 700; color: #7c3aed;">${node.monthly_pv || node.total_pv || 0}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #6b7280;">Refs:</span>
                            <span style="font-weight: 700;">${node.direct_referrals || 0}</span>
                        </div>
                        <div style="
                            background: ${statusBg};
                            color: ${statusText};
                            text-align: center;
                            padding: 4px;
                            border-radius: 6px;
                            font-size: 10px;
                            font-weight: 600;
                            text-transform: uppercase;
                        ">
                            ${retentionStatus === 'grace_period' ? '⚠️ Grace' : retentionStatus === 'inactive' ? '❌ Inactive' : '✓ Active'}
                        </div>
                    </div>
                </div>
            </foreignObject>
        `;

        // Click handler — แสดงรายละเอียดเฉพาะเมื่อไม่ได้ลาก
        g.addEventListener('click', (e) => {
            e.stopPropagation();
            if (this.hasDragged) return; // ถ้าลากอยู่ ไม่ต้องแสดง
            this.showNodeDetails(node);
        });
        parent.appendChild(g);
    }

    drawConnection(parent, x1, y1, x2, y2, leg = null) {
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

        // Curved path for better aesthetics
        const midY = (y1 + y2) / 2;
        const d = `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`;

        path.setAttribute('d', d);
        path.setAttribute('class', 'mlm-connection');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');

        if (leg === 'left') {
            path.setAttribute('stroke', '#10b981');
        } else if (leg === 'right') {
            path.setAttribute('stroke', '#ef4444');
        } else {
            path.setAttribute('stroke', '#9ca3af');
        }

        parent.insertBefore(path, parent.firstChild);
    }

    initializeInteractions() {
        const svg = this.container.querySelector('#mlm-svg');
        const canvas = this.container.querySelector('#mlm-canvas');

        // ตัวแปรสำหรับ drag/pan
        this.isPanning = false;
        this.hasDragged = false; // แยก click กับ drag
        this.dragThreshold = 5; // ระยะ pixel ขั้นต่ำที่ถือว่าลาก
        this.mouseDownPoint = { x: 0, y: 0 };
        let startPoint = { x: 0, y: 0 };

        // Pan — ลากได้จากทุกจุด (grid + node)
        canvas.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return; // เฉพาะ left click
            this.isPanning = true;
            this.hasDragged = false;
            this.mouseDownPoint = { x: e.clientX, y: e.clientY };
            startPoint = {
                x: e.clientX - this.transform.x,
                y: e.clientY - this.transform.y
            };
            canvas.style.cursor = 'grabbing';
            e.preventDefault(); // ป้องกัน text selection
        });

        // ใช้ document เพื่อให้ลากต่อเนื่องแม้เมาส์ออกจาก canvas
        document.addEventListener('mousemove', (e) => {
            if (!this.isPanning) return;

            const dx = e.clientX - this.mouseDownPoint.x;
            const dy = e.clientY - this.mouseDownPoint.y;
            if (Math.sqrt(dx * dx + dy * dy) > this.dragThreshold) {
                this.hasDragged = true;
            }

            this.transform.x = e.clientX - startPoint.x;
            this.transform.y = e.clientY - startPoint.y;
            this.applyTransform();
            this.updateMinimap();
        });

        document.addEventListener('mouseup', () => {
            if (!this.isPanning) return;
            this.isPanning = false;
            canvas.style.cursor = 'grab';
        });

        // Touch events — สำหรับมือถือ/แท็บเล็ต
        canvas.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                this.isPanning = true;
                this.hasDragged = false;
                this.mouseDownPoint = { x: touch.clientX, y: touch.clientY };
                startPoint = {
                    x: touch.clientX - this.transform.x,
                    y: touch.clientY - this.transform.y
                };
            }
        }, { passive: true });

        canvas.addEventListener('touchmove', (e) => {
            if (this.isPanning && e.touches.length === 1) {
                const touch = e.touches[0];
                const dx = touch.clientX - this.mouseDownPoint.x;
                const dy = touch.clientY - this.mouseDownPoint.y;
                if (Math.sqrt(dx * dx + dy * dy) > this.dragThreshold) {
                    this.hasDragged = true;
                }
                this.transform.x = touch.clientX - startPoint.x;
                this.transform.y = touch.clientY - startPoint.y;
                this.applyTransform();
                this.updateMinimap();
            }
        }, { passive: true });

        canvas.addEventListener('touchend', () => {
            this.isPanning = false;
        });

        // Zoom — scroll wheel
        canvas.addEventListener('wheel', (e) => {
            e.preventDefault();
            const rect = canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            const oldK = this.transform.k;
            const newK = Math.max(0.1, Math.min(3, oldK * delta));
            if (newK === oldK) return;

            // ซูมที่ตำแหน่งเมาส์
            const ratio = newK / oldK;
            this.transform.x = mouseX - (mouseX - this.transform.x) * ratio;
            this.transform.y = mouseY - (mouseY - this.transform.y) * ratio;
            this.transform.k = newK;

            this.applyTransform();
            this.updateZoomLevel();
            this.updateMinimap();
        }, { passive: false });

        // Minimap click — คลิกบน minimap เพื่อนำทาง
        this.initMinimapInteraction();
    }

    /**
     * Minimap click/drag interaction
     */
    initMinimapInteraction() {
        const minimapCanvas = this.container.querySelector('#minimap-canvas');
        if (!minimapCanvas) return;

        let minimapDragging = false;

        const navigateFromMinimap = (e) => {
            if (!this.data) return;
            const rect = minimapCanvas.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const clickY = e.clientY - rect.top;

            // คำนวณ bounds ของ tree
            const stats = this.calculateStats(this.data);
            const bounds = this.getTreeBounds();
            if (!bounds) return;

            const padding = 10;
            const scale = Math.min(
                (minimapCanvas.width - padding * 2) / bounds.width,
                (minimapCanvas.height - padding * 2) / bounds.height
            ) * 0.8;

            // แปลง minimap → world coordinates
            const worldX = (clickX - minimapCanvas.width / 2) / scale + bounds.centerX;
            const worldY = (clickY - padding) / scale + bounds.minY;

            // เลื่อน canvas ให้จุดนี้อยู่ตรงกลาง
            const canvasEl = this.container.querySelector('#mlm-canvas');
            const viewRect = canvasEl.getBoundingClientRect();
            this.transform.x = viewRect.width / 2 - worldX * this.transform.k;
            this.transform.y = viewRect.height / 2 - worldY * this.transform.k;

            this.applyTransform();
            this.updateMinimap();
        };

        minimapCanvas.style.cursor = 'pointer';
        minimapCanvas.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            minimapDragging = true;
            navigateFromMinimap(e);
        });
        document.addEventListener('mousemove', (e) => {
            if (minimapDragging) {
                e.preventDefault();
                navigateFromMinimap(e);
            }
        });
        document.addEventListener('mouseup', () => {
            minimapDragging = false;
        });
    }

    /**
     * คำนวณขอบเขตของ tree ทั้งหมด
     */
    getTreeBounds() {
        const nodes = this.container.querySelectorAll('.mlm-node');
        if (nodes.length === 0) return null;

        let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;

        nodes.forEach(node => {
            const transform = node.getAttribute('transform');
            const match = transform.match(/translate\(([^,]+),\s*([^)]+)\)/);
            if (match) {
                const x = parseFloat(match[1]);
                const y = parseFloat(match[2]);
                minX = Math.min(minX, x);
                maxX = Math.max(maxX, x + this.options.nodeWidth);
                minY = Math.min(minY, y);
                maxY = Math.max(maxY, y + this.options.nodeHeight);
            }
        });

        return {
            minX, maxX, minY, maxY,
            width: maxX - minX || 1,
            height: maxY - minY || 1,
            centerX: (minX + maxX) / 2,
            centerY: (minY + maxY) / 2
        };
    }

    /**
     * อัพเดท Minimap แสดงตำแหน่งปัจจุบัน
     */
    updateMinimap() {
        const minimapCanvas = this.container.querySelector('#minimap-canvas');
        const viewport = this.container.querySelector('#minimap-viewport');
        if (!minimapCanvas || !this.data) return;

        const ctx = minimapCanvas.getContext('2d');
        const bounds = this.getTreeBounds();
        if (!bounds) return;

        // ตั้งค่า canvas
        minimapCanvas.width = 200;
        minimapCanvas.height = 150;

        // พื้นหลัง
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, minimapCanvas.width, minimapCanvas.height);

        // วาด node เป็นจุด
        const padding = 10;
        const scale = Math.min(
            (minimapCanvas.width - padding * 2) / bounds.width,
            (minimapCanvas.height - padding * 2) / bounds.height
        ) * 0.8;

        ctx.fillStyle = '#7c3aed';
        const nodes = this.container.querySelectorAll('.mlm-node');
        nodes.forEach(node => {
            const transform = node.getAttribute('transform');
            const match = transform.match(/translate\(([^,]+),\s*([^)]+)\)/);
            if (match) {
                const x = parseFloat(match[1]) + this.options.nodeWidth / 2;
                const y = parseFloat(match[2]) + this.options.nodeHeight / 2;
                const px = (x - bounds.centerX) * scale + minimapCanvas.width / 2;
                const py = (y - bounds.minY) * scale + padding;
                ctx.beginPath();
                ctx.arc(px, py, 3, 0, Math.PI * 2);
                ctx.fill();
            }
        });

        // อัพเดท viewport rectangle
        if (viewport) {
            const canvasEl = this.container.querySelector('#mlm-canvas');
            const rect = canvasEl.getBoundingClientRect();
            const vpScale = scale / this.transform.k;
            const vpW = rect.width * vpScale;
            const vpH = rect.height * vpScale;
            const vpX = (-this.transform.x / this.transform.k - bounds.centerX) * scale + minimapCanvas.width / 2;
            const vpY = (-this.transform.y / this.transform.k - bounds.minY) * scale + padding;

            viewport.style.left = `${Math.max(0, vpX)}px`;
            viewport.style.top = `${Math.max(0, vpY)}px`;
            viewport.style.width = `${Math.min(vpW, minimapCanvas.width)}px`;
            viewport.style.height = `${Math.min(vpH, minimapCanvas.height)}px`;
        }
    }

    applyTransform() {
        const g = this.container.querySelector('#main-group');
        if (g) {
            g.setAttribute('transform', `translate(${this.transform.x}, ${this.transform.y}) scale(${this.transform.k})`);
        }
    }

    zoomIn() {
        this.transform.k *= 1.2;
        this.transform.k = Math.min(3, this.transform.k);
        this.applyTransform();
        this.updateZoomLevel();
    }

    zoomOut() {
        this.transform.k *= 0.8;
        this.transform.k = Math.max(0.1, this.transform.k);
        this.applyTransform();
        this.updateZoomLevel();
    }

    resetView() {
        // ปรับให้อยู่ตรงกลาง
        const canvas = this.container.querySelector('#mlm-canvas');
        if (canvas) {
            const rect = canvas.getBoundingClientRect();
            this.transform = { x: rect.width / 2 - 200, y: 50, k: 1 };
        } else {
            this.transform = { x: 200, y: 50, k: 1 };
        }
        this.applyTransform();
        this.updateZoomLevel();
        this.updateMinimap();
    }

    updateZoomLevel() {
        const zoomLevel = this.container.querySelector('#zoom-level');
        if (zoomLevel) {
            zoomLevel.textContent = Math.round(this.transform.k * 100) + '%';
        }
    }

    updateStats() {
        const stats = this.calculateStats(this.data);
        this.container.querySelector('#stat-total-members').textContent = stats.totalMembers;
        this.container.querySelector('#stat-active-members').textContent = stats.activeMembers;
        this.container.querySelector('#stat-total-pv').textContent = stats.totalPv.toLocaleString();
        this.container.querySelector('#stat-depth').textContent = stats.maxDepth;
    }

    calculateStats(node, stats = { totalMembers: 0, activeMembers: 0, totalPv: 0, maxDepth: 0 }, depth = 0) {
        if (!node) return stats;

        stats.totalMembers++;
        if (node.status === 'active') stats.activeMembers++;
        stats.totalPv += node.total_pv || 0;
        stats.maxDepth = Math.max(stats.maxDepth, depth);

        if (this.options.type === 'binary') {
            if (node.left) this.calculateStats(node.left, stats, depth + 1);
            if (node.right) this.calculateStats(node.right, stats, depth + 1);
        } else {
            (node.children || []).forEach(child => this.calculateStats(child, stats, depth + 1));
        }

        return stats;
    }

    handleSearch(query) {
        // Implement search functionality
        const resultsContainer = this.container.querySelector('#search-results');

        if (!query) {
            resultsContainer.classList.remove('show');
            return;
        }

        // Demo search results
        resultsContainer.innerHTML = `
            <div class="mlm-search-item">
                <strong>MLM001</strong> - John Doe<br>
                <small style="color: #6b7280;">john@example.com</small>
            </div>
        `;
        resultsContainer.classList.add('show');
    }

    showNodeDetails(node) {
        const modal = this.container.querySelector('#node-modal');
        const modalBody = this.container.querySelector('#modal-body');

        modalBody.innerHTML = `
            <h2 style="margin: 0 0 20px 0; color: #1f2937;">📋 รายละเอียดสมาชิก</h2>
            <div style="background: #f9fafb; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <div style="font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">
                    ${node.name || 'Unknown'}
                </div>
                <div style="color: #6b7280; font-size: 14px;">
                    ${node.member_code} | ${node.email || 'No email'}
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;">
                <div style="background: #ecfdf5; padding: 16px; border-radius: 12px;">
                    <div style="color: #059669; font-size: 12px; font-weight: 600; margin-bottom: 8px;">TOTAL PV</div>
                    <div style="font-size: 24px; font-weight: 700; color: #047857;">${node.total_pv || 0}</div>
                </div>
                <div style="background: #ede9fe; padding: 16px; border-radius: 12px;">
                    <div style="color: #7c3aed; font-size: 12px; font-weight: 600; margin-bottom: 8px;">DIRECT REFS</div>
                    <div style="font-size: 24px; font-weight: 700; color: #6d28d9;">${node.direct_referrals || 0}</div>
                </div>
            </div>
            <div style="text-align: center;">
                <button onclick="this.closest('.mlm-modal').classList.remove('show')"
                        style="
                            padding: 12px 24px;
                            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
                            color: white;
                            border: none;
                            border-radius: 12px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: transform 0.2s;
                        "
                        onmouseover="this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.transform=''">
                    ✨ ปิด
                </button>
            </div>
        `;

        modal.classList.add('show');
    }

    closeModal() {
        const modal = this.container.querySelector('#node-modal');
        modal.classList.remove('show');
    }

    toggleFullscreen() {
        if (!document.fullscreenElement) {
            this.container.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    }

    switchType(type) {
        this.options.type = type;
        this.loadData().then(() => this.render());
    }
}

// Export for global use
window.MlmGenealogyPremium = MlmGenealogyPremium;
