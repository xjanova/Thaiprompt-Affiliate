/**
 * Organization Chart Viewer - n8n Style
 * แสดงผังสายงาน MLM แบบ Node-based เหมือน n8n
 *
 * รองรับ:
 * - Grid background pattern
 * - Connection points (input/output)
 * - Smooth bezier curves
 * - Touch events (pinch-to-zoom, pan)
 * - Modern node design
 * - Animated connections
 *
 * @author TP-Affiliate Team
 * @version 3.1.0
 */

class OrgChartViewer {
    /**
     * สร้าง OrgChartViewer instance
     *
     * @param {string|HTMLElement} container - Container element หรือ ID
     * @param {Object} options - ตัวเลือกการตั้งค่า
     */
    constructor(container, options = {}) {
        this.container = typeof container === 'string'
            ? document.getElementById(container)
            : container;

        if (!this.container) {
            console.error('OrgChartViewer: Container not found');
            return;
        }

        // ตั้งค่า n8n style
        this.options = {
            treeType: options.treeType || 'unilevel',
            maxDepth: options.maxDepth || 5,
            nodeWidth: options.nodeWidth || 220,
            nodeHeight: options.nodeHeight || 80,
            horizontalSpacing: options.horizontalSpacing || 60,
            verticalSpacing: options.verticalSpacing || 120,
            minScale: options.minScale || 0.1,
            maxScale: options.maxScale || 3,
            gridSize: options.gridSize || 20,
            showGrid: options.showGrid !== false,
            animateConnections: options.animateConnections !== false,
            connectionStyle: options.connectionStyle || 'bezier', // 'bezier' | 'step' | 'straight'
            onNodeClick: options.onNodeClick || null,
            ...options
        };

        // State
        this.data = null;
        this.transform = { x: 0, y: 0, scale: 1 };
        this.isDragging = false;
        this.lastTouchDistance = null;
        this.lastTouchCenter = null;
        this.startDragPoint = { x: 0, y: 0 };
        this.nodeCount = 0;
        this.maxDepthReached = 0;
        this.nodePositions = new Map();

        // Initialize
        this.init();
    }

    /**
     * Initialize component
     */
    init() {
        this.createDOM();
        this.attachEventListeners();
        this.showLoading();
    }

    /**
     * สร้าง DOM structure แบบ n8n
     */
    createDOM() {
        this.container.innerHTML = `
            <div class="n8n-chart-wrapper">
                <!-- Loading Overlay -->
                <div class="n8n-loading">
                    <div class="n8n-loading-spinner"></div>
                    <p>กำลังโหลดผังสายงาน...</p>
                </div>

                <!-- Canvas Container -->
                <div class="n8n-canvas">
                    <svg class="n8n-svg">
                        <defs>
                            <!-- Grid Pattern -->
                            <pattern id="n8n-grid-small" width="${this.options.gridSize}" height="${this.options.gridSize}" patternUnits="userSpaceOnUse">
                                <circle cx="1" cy="1" r="1" fill="#e5e7eb" class="dark-fill-gray-700"/>
                            </pattern>
                            <pattern id="n8n-grid-large" width="${this.options.gridSize * 5}" height="${this.options.gridSize * 5}" patternUnits="userSpaceOnUse">
                                <rect width="${this.options.gridSize * 5}" height="${this.options.gridSize * 5}" fill="url(#n8n-grid-small)"/>
                                <circle cx="1" cy="1" r="1.5" fill="#d1d5db" class="dark-fill-gray-600"/>
                            </pattern>

                            <!-- Connection Gradient -->
                            <linearGradient id="conn-gradient-default" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#a855f7"/>
                                <stop offset="100%" style="stop-color:#6366f1"/>
                            </linearGradient>
                            <linearGradient id="conn-gradient-left" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#10b981"/>
                                <stop offset="100%" style="stop-color:#06b6d4"/>
                            </linearGradient>
                            <linearGradient id="conn-gradient-right" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#f43f5e"/>
                                <stop offset="100%" style="stop-color:#ec4899"/>
                            </linearGradient>

                            <!-- Glow Filter -->
                            <filter id="n8n-glow" x="-50%" y="-50%" width="200%" height="200%">
                                <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                <feMerge>
                                    <feMergeNode in="coloredBlur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>

                            <!-- Node Shadow -->
                            <filter id="n8n-shadow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="#000" flood-opacity="0.1"/>
                            </filter>

                            <!-- Arrow Marker -->
                            <marker id="n8n-arrow" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto">
                                <path d="M2,2 L10,6 L2,10 L4,6 Z" fill="#a855f7"/>
                            </marker>
                        </defs>

                        <!-- Grid Background -->
                        <rect class="n8n-grid-bg" width="10000" height="10000" x="-5000" y="-5000" fill="url(#n8n-grid-large)"/>

                        <!-- Main Group for Transform -->
                        <g class="n8n-main-group">
                            <!-- Connections Layer -->
                            <g class="n8n-connections"></g>
                            <!-- Nodes Layer -->
                            <g class="n8n-nodes"></g>
                        </g>
                    </svg>
                </div>

                <!-- Zoom Controls -->
                <div class="n8n-controls">
                    <button class="n8n-ctrl-btn n8n-zoom-in" title="ซูมเข้า">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v6M8 11h6"/>
                        </svg>
                    </button>
                    <div class="n8n-zoom-display">100%</div>
                    <button class="n8n-ctrl-btn n8n-zoom-out" title="ซูมออก">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M8 11h6"/>
                        </svg>
                    </button>
                    <div class="n8n-ctrl-divider"></div>
                    <button class="n8n-ctrl-btn n8n-fit" title="พอดีหน้าจอ">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
                        </svg>
                    </button>
                    <button class="n8n-ctrl-btn n8n-reset" title="รีเซ็ต">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                        </svg>
                    </button>
                </div>

                <!-- Mini Map -->
                <div class="n8n-minimap">
                    <div class="n8n-minimap-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M3 9h18M9 21V9"/>
                        </svg>
                        Overview
                    </div>
                    <canvas class="n8n-minimap-canvas"></canvas>
                    <div class="n8n-minimap-viewport"></div>
                </div>

                <!-- Stats Panel -->
                <div class="n8n-stats">
                    <div class="n8n-stat">
                        <span class="n8n-stat-icon">👥</span>
                        <div>
                            <div class="n8n-stat-value n8n-node-count">0</div>
                            <div class="n8n-stat-label">Nodes</div>
                        </div>
                    </div>
                    <div class="n8n-stat">
                        <span class="n8n-stat-icon">📊</span>
                        <div>
                            <div class="n8n-stat-value n8n-max-depth">0</div>
                            <div class="n8n-stat-label">Levels</div>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="n8n-help">
                    <div class="n8n-help-item">
                        <kbd>Scroll</kbd> ซูม
                    </div>
                    <div class="n8n-help-item">
                        <kbd>Drag</kbd> เลื่อน
                    </div>
                    <div class="n8n-help-item">
                        <kbd>Click</kbd> รายละเอียด
                    </div>
                </div>

                <!-- Node Detail Modal -->
                <div class="n8n-modal">
                    <div class="n8n-modal-backdrop"></div>
                    <div class="n8n-modal-content"></div>
                </div>
            </div>

            <style>
                /* n8n Style Variables */
                .n8n-chart-wrapper {
                    --n8n-bg: #f8fafc;
                    --n8n-bg-dark: #1e293b;
                    --n8n-node-bg: #ffffff;
                    --n8n-node-bg-dark: #334155;
                    --n8n-text: #1e293b;
                    --n8n-text-dark: #f1f5f9;
                    --n8n-text-muted: #64748b;
                    --n8n-border: #e2e8f0;
                    --n8n-border-dark: #475569;
                    --n8n-primary: #a855f7;
                    --n8n-success: #10b981;
                    --n8n-warning: #f59e0b;
                    --n8n-danger: #ef4444;

                    position: relative;
                    width: 100%;
                    height: 100%;
                    min-height: 500px;
                    background: var(--n8n-bg);
                    border-radius: 16px;
                    overflow: hidden;
                    font-family: 'Inter', system-ui, -apple-system, sans-serif;
                }

                .dark .n8n-chart-wrapper {
                    --n8n-bg: #0f172a;
                    --n8n-node-bg: #1e293b;
                    --n8n-text: #f1f5f9;
                    --n8n-text-muted: #94a3b8;
                    --n8n-border: #334155;
                }

                /* Canvas */
                .n8n-canvas {
                    position: absolute;
                    inset: 0;
                    cursor: grab;
                    touch-action: none;
                }

                .n8n-canvas:active {
                    cursor: grabbing;
                }

                .n8n-svg {
                    width: 100%;
                    height: 100%;
                }

                .dark .n8n-grid-bg {
                    opacity: 0.5;
                }

                /* Loading */
                .n8n-loading {
                    position: absolute;
                    inset: 0;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    background: rgba(248, 250, 252, 0.95);
                    backdrop-filter: blur(8px);
                    z-index: 50;
                    transition: opacity 0.3s;
                }

                .dark .n8n-loading {
                    background: rgba(15, 23, 42, 0.95);
                }

                .n8n-loading-spinner {
                    width: 48px;
                    height: 48px;
                    border: 3px solid var(--n8n-border);
                    border-top-color: var(--n8n-primary);
                    border-radius: 50%;
                    animation: n8n-spin 0.8s linear infinite;
                }

                .n8n-loading p {
                    margin-top: 16px;
                    color: var(--n8n-text-muted);
                    font-size: 14px;
                }

                @keyframes n8n-spin {
                    to { transform: rotate(360deg); }
                }

                /* Controls */
                .n8n-controls {
                    position: absolute;
                    top: 16px;
                    right: 16px;
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                    background: var(--n8n-node-bg);
                    border: 1px solid var(--n8n-border);
                    border-radius: 12px;
                    padding: 6px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    z-index: 10;
                }

                .dark .n8n-controls {
                    background: var(--n8n-node-bg);
                    border-color: var(--n8n-border);
                }

                .n8n-ctrl-btn {
                    width: 36px;
                    height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: transparent;
                    border: none;
                    border-radius: 8px;
                    color: var(--n8n-text-muted);
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .n8n-ctrl-btn:hover {
                    background: rgba(168, 85, 247, 0.1);
                    color: var(--n8n-primary);
                }

                .n8n-ctrl-btn:active {
                    transform: scale(0.95);
                }

                .n8n-ctrl-btn svg {
                    width: 18px;
                    height: 18px;
                }

                .n8n-zoom-display {
                    font-size: 11px;
                    font-weight: 600;
                    text-align: center;
                    padding: 4px 0;
                    color: var(--n8n-text-muted);
                }

                .n8n-ctrl-divider {
                    height: 1px;
                    background: var(--n8n-border);
                    margin: 4px 0;
                }

                /* Mini Map */
                .n8n-minimap {
                    position: absolute;
                    bottom: 16px;
                    right: 16px;
                    width: 180px;
                    background: var(--n8n-node-bg);
                    border: 1px solid var(--n8n-border);
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    z-index: 10;
                }

                .n8n-minimap-title {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    padding: 8px 12px;
                    font-size: 11px;
                    font-weight: 600;
                    color: var(--n8n-text-muted);
                    border-bottom: 1px solid var(--n8n-border);
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .n8n-minimap-canvas {
                    width: 100%;
                    height: 100px;
                    display: block;
                }

                .n8n-minimap-viewport {
                    position: absolute;
                    border: 2px solid var(--n8n-primary);
                    background: rgba(168, 85, 247, 0.1);
                    border-radius: 4px;
                    pointer-events: none;
                }

                /* Stats */
                .n8n-stats {
                    position: absolute;
                    top: 16px;
                    left: 16px;
                    display: flex;
                    gap: 12px;
                    z-index: 10;
                }

                .n8n-stat {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    background: var(--n8n-node-bg);
                    border: 1px solid var(--n8n-border);
                    border-radius: 12px;
                    padding: 10px 14px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                }

                .n8n-stat-icon {
                    font-size: 20px;
                }

                .n8n-stat-value {
                    font-size: 18px;
                    font-weight: 700;
                    color: var(--n8n-text);
                }

                .n8n-stat-label {
                    font-size: 11px;
                    color: var(--n8n-text-muted);
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                /* Help */
                .n8n-help {
                    position: absolute;
                    bottom: 16px;
                    left: 16px;
                    display: flex;
                    gap: 16px;
                    z-index: 10;
                }

                .n8n-help-item {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 12px;
                    color: var(--n8n-text-muted);
                }

                .n8n-help-item kbd {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 20px;
                    height: 20px;
                    padding: 0 6px;
                    background: var(--n8n-node-bg);
                    border: 1px solid var(--n8n-border);
                    border-radius: 4px;
                    font-size: 10px;
                    font-weight: 600;
                    font-family: inherit;
                }

                /* Modal */
                .n8n-modal {
                    position: fixed;
                    inset: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 100;
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.3s;
                }

                .n8n-modal.show {
                    opacity: 1;
                    pointer-events: auto;
                }

                .n8n-modal-backdrop {
                    position: absolute;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(4px);
                }

                .n8n-modal-content {
                    position: relative;
                    width: 90%;
                    max-width: 480px;
                    max-height: 80vh;
                    background: var(--n8n-node-bg);
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
                    transform: scale(0.95);
                    transition: transform 0.3s;
                }

                .n8n-modal.show .n8n-modal-content {
                    transform: scale(1);
                }

                /* Connection Animations */
                @keyframes n8n-flow {
                    from { stroke-dashoffset: 24; }
                    to { stroke-dashoffset: 0; }
                }

                .n8n-connection {
                    fill: none;
                    stroke-width: 2;
                    stroke-linecap: round;
                }

                .n8n-connection-animated {
                    stroke-dasharray: 8 4;
                    animation: n8n-flow 0.5s linear infinite;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .n8n-stats {
                        flex-direction: column;
                        gap: 8px;
                    }

                    .n8n-minimap {
                        display: none;
                    }

                    .n8n-help {
                        display: none;
                    }

                    .n8n-stat {
                        padding: 8px 12px;
                    }

                    .n8n-stat-value {
                        font-size: 16px;
                    }
                }
            </style>
        `;

        // เก็บ references
        this.wrapper = this.container.querySelector('.n8n-chart-wrapper');
        this.loading = this.container.querySelector('.n8n-loading');
        this.canvas = this.container.querySelector('.n8n-canvas');
        this.svg = this.container.querySelector('.n8n-svg');
        this.mainGroup = this.container.querySelector('.n8n-main-group');
        this.connectionsGroup = this.container.querySelector('.n8n-connections');
        this.nodesGroup = this.container.querySelector('.n8n-nodes');
        this.zoomDisplay = this.container.querySelector('.n8n-zoom-display');
        this.nodeCountDisplay = this.container.querySelector('.n8n-node-count');
        this.maxDepthDisplay = this.container.querySelector('.n8n-max-depth');
        this.modal = this.container.querySelector('.n8n-modal');
        this.modalContent = this.container.querySelector('.n8n-modal-content');
        this.minimapCanvas = this.container.querySelector('.n8n-minimap-canvas');
        this.minimapViewport = this.container.querySelector('.n8n-minimap-viewport');
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Zoom buttons
        this.container.querySelector('.n8n-zoom-in').addEventListener('click', () => this.zoomIn());
        this.container.querySelector('.n8n-zoom-out').addEventListener('click', () => this.zoomOut());
        this.container.querySelector('.n8n-reset').addEventListener('click', () => this.resetView());
        this.container.querySelector('.n8n-fit').addEventListener('click', () => this.fitToScreen());

        // Mouse events
        this.canvas.addEventListener('mousedown', (e) => this.onDragStart(e));
        this.canvas.addEventListener('mousemove', (e) => this.onDragMove(e));
        this.canvas.addEventListener('mouseup', () => this.onDragEnd());
        this.canvas.addEventListener('mouseleave', () => this.onDragEnd());
        this.canvas.addEventListener('wheel', (e) => this.onWheel(e), { passive: false });

        // Touch events
        this.canvas.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: false });
        this.canvas.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: false });
        this.canvas.addEventListener('touchend', (e) => this.onTouchEnd(e));

        // Modal
        this.container.querySelector('.n8n-modal-backdrop').addEventListener('click', () => this.hideModal());
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hideModal();
        });
    }

    /**
     * Mouse/Touch Events
     */
    onDragStart(e) {
        if (e.target.closest('.n8n-node')) return;
        this.isDragging = true;
        this.startDragPoint = {
            x: e.clientX - this.transform.x,
            y: e.clientY - this.transform.y
        };
    }

    onDragMove(e) {
        if (!this.isDragging) return;
        this.transform.x = e.clientX - this.startDragPoint.x;
        this.transform.y = e.clientY - this.startDragPoint.y;
        this.applyTransform();
        this.updateMinimap();
    }

    onDragEnd() {
        this.isDragging = false;
    }

    onWheel(e) {
        e.preventDefault();
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        this.zoomAt(mouseX, mouseY, delta);
    }

    onTouchStart(e) {
        if (e.touches.length === 1) {
            const touch = e.touches[0];
            this.isDragging = true;
            this.startDragPoint = {
                x: touch.clientX - this.transform.x,
                y: touch.clientY - this.transform.y
            };
        } else if (e.touches.length === 2) {
            e.preventDefault();
            this.isDragging = false;
            this.lastTouchDistance = this.getTouchDistance(e.touches);
            this.lastTouchCenter = this.getTouchCenter(e.touches);
        }
    }

    onTouchMove(e) {
        if (e.touches.length === 1 && this.isDragging) {
            const touch = e.touches[0];
            this.transform.x = touch.clientX - this.startDragPoint.x;
            this.transform.y = touch.clientY - this.startDragPoint.y;
            this.applyTransform();
            this.updateMinimap();
        } else if (e.touches.length === 2 && this.lastTouchDistance !== null) {
            e.preventDefault();
            const distance = this.getTouchDistance(e.touches);
            const center = this.getTouchCenter(e.touches);
            const rect = this.canvas.getBoundingClientRect();
            const scaleFactor = distance / this.lastTouchDistance;
            const centerX = center.x - rect.left;
            const centerY = center.y - rect.top;
            this.zoomAt(centerX, centerY, scaleFactor);

            if (this.lastTouchCenter) {
                this.transform.x += center.x - this.lastTouchCenter.x;
                this.transform.y += center.y - this.lastTouchCenter.y;
                this.applyTransform();
            }

            this.lastTouchDistance = distance;
            this.lastTouchCenter = center;
        }
    }

    onTouchEnd(e) {
        if (e.touches.length < 2) {
            this.lastTouchDistance = null;
            this.lastTouchCenter = null;
        }
        if (e.touches.length === 0) {
            this.isDragging = false;
        }
    }

    getTouchDistance(touches) {
        const dx = touches[1].clientX - touches[0].clientX;
        const dy = touches[1].clientY - touches[0].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }

    getTouchCenter(touches) {
        return {
            x: (touches[0].clientX + touches[1].clientX) / 2,
            y: (touches[0].clientY + touches[1].clientY) / 2
        };
    }

    /**
     * Zoom Functions
     */
    zoomAt(x, y, factor) {
        const oldScale = this.transform.scale;
        const newScale = Math.max(this.options.minScale, Math.min(this.options.maxScale, oldScale * factor));
        if (newScale === oldScale) return;

        const scaleRatio = newScale / oldScale;
        this.transform.x = x - (x - this.transform.x) * scaleRatio;
        this.transform.y = y - (y - this.transform.y) * scaleRatio;
        this.transform.scale = newScale;

        this.applyTransform();
        this.updateZoomDisplay();
        this.updateMinimap();
    }

    zoomIn() {
        const rect = this.canvas.getBoundingClientRect();
        this.zoomAt(rect.width / 2, rect.height / 2, 1.25);
    }

    zoomOut() {
        const rect = this.canvas.getBoundingClientRect();
        this.zoomAt(rect.width / 2, rect.height / 2, 0.8);
    }

    resetView() {
        const rect = this.canvas.getBoundingClientRect();
        this.transform = {
            x: rect.width / 2,
            y: 80,
            scale: 1
        };
        this.applyTransform();
        this.updateZoomDisplay();
        this.updateMinimap();
    }

    fitToScreen() {
        if (!this.data) return;
        const rect = this.canvas.getBoundingClientRect();
        const bounds = this.calculateBounds();

        const padding = 100;
        const scaleX = (rect.width - padding * 2) / bounds.width;
        const scaleY = (rect.height - padding * 2) / bounds.height;
        const scale = Math.min(scaleX, scaleY, 1.5);

        this.transform = {
            x: rect.width / 2 - (bounds.centerX * scale),
            y: padding - (bounds.minY * scale),
            scale: Math.max(this.options.minScale, scale)
        };

        this.applyTransform();
        this.updateZoomDisplay();
        this.updateMinimap();
    }

    applyTransform() {
        const { x, y, scale } = this.transform;
        this.mainGroup.setAttribute('transform', `translate(${x}, ${y}) scale(${scale})`);
    }

    updateZoomDisplay() {
        this.zoomDisplay.textContent = `${Math.round(this.transform.scale * 100)}%`;
    }

    /**
     * Loading State
     */
    showLoading() {
        this.loading.style.opacity = '1';
        this.loading.style.pointerEvents = 'auto';
    }

    hideLoading() {
        this.loading.style.opacity = '0';
        this.loading.style.pointerEvents = 'none';
    }

    /**
     * Set Data และ Render
     */
    setData(data) {
        this.data = data;
        this.nodeCount = 0;
        this.maxDepthReached = 0;
        this.nodePositions.clear();

        this.render();
        this.hideLoading();
        this.updateStats();
        this.resetView();
        this.updateMinimap();
    }

    /**
     * Render Tree
     */
    render() {
        if (!this.data) return;

        // Clear
        this.connectionsGroup.innerHTML = '';
        this.nodesGroup.innerHTML = '';

        // คำนวณตำแหน่งทั้งหมดก่อน
        if (this.options.treeType === 'binary') {
            this.calculateBinaryPositions(this.data, 0, 0, 0);
        } else {
            this.calculateUnilevelPositions(this.data, 0, 0, 0);
        }

        // Render connections ก่อน (อยู่ด้านหลัง)
        this.renderConnections();

        // Render nodes
        this.nodePositions.forEach((pos, nodeId) => {
            this.drawNode(pos.node, pos.x, pos.y);
        });

        // Update stats
        this.nodeCountDisplay.textContent = this.nodeCount;
        this.maxDepthDisplay.textContent = this.maxDepthReached;
    }

    /**
     * คำนวณตำแหน่ง Binary Tree
     */
    calculateBinaryPositions(node, x, y, depth) {
        if (!node || depth > this.options.maxDepth) return;

        this.nodeCount++;
        this.maxDepthReached = Math.max(this.maxDepthReached, depth);

        const nodeY = depth * this.options.verticalSpacing;
        const baseOffset = 300;
        const offset = baseOffset / Math.pow(1.6, depth);

        // เก็บตำแหน่ง
        this.nodePositions.set(node.id || `node-${this.nodeCount}`, {
            node,
            x,
            y: nodeY,
            depth,
            leftChild: node.left ? (node.left.id || `node-${this.nodeCount + 1}`) : null,
            rightChild: node.right ? (node.right.id || `node-${this.nodeCount + 2}`) : null
        });

        if (node.left) {
            this.calculateBinaryPositions(node.left, x - offset, y, depth + 1);
        }
        if (node.right) {
            this.calculateBinaryPositions(node.right, x + offset, y, depth + 1);
        }
    }

    /**
     * คำนวณตำแหน่ง Unilevel Tree
     */
    calculateUnilevelPositions(node, x, y, depth) {
        if (!node || depth > this.options.maxDepth) return;

        this.nodeCount++;
        this.maxDepthReached = Math.max(this.maxDepthReached, depth);

        const nodeY = depth * this.options.verticalSpacing;
        const children = node.children || [];

        // เก็บตำแหน่ง
        this.nodePositions.set(node.id || `node-${this.nodeCount}`, {
            node,
            x,
            y: nodeY,
            depth,
            children: children.map((c, i) => c.id || `child-${this.nodeCount}-${i}`)
        });

        if (children.length > 0) {
            const spacing = this.options.nodeWidth + this.options.horizontalSpacing;
            const totalWidth = (children.length - 1) * spacing;
            const startX = x - totalWidth / 2;

            children.forEach((child, index) => {
                const childX = startX + index * spacing;
                this.calculateUnilevelPositions(child, childX, y, depth + 1);
            });
        }
    }

    /**
     * Render Connections
     */
    renderConnections() {
        this.nodePositions.forEach((pos, nodeId) => {
            if (this.options.treeType === 'binary') {
                // Binary connections
                if (pos.leftChild && this.nodePositions.has(pos.leftChild)) {
                    const childPos = this.nodePositions.get(pos.leftChild);
                    this.drawConnection(pos.x, pos.y, childPos.x, childPos.y, 'left');
                }
                if (pos.rightChild && this.nodePositions.has(pos.rightChild)) {
                    const childPos = this.nodePositions.get(pos.rightChild);
                    this.drawConnection(pos.x, pos.y, childPos.x, childPos.y, 'right');
                }
            } else {
                // Unilevel connections
                if (pos.children) {
                    pos.children.forEach(childId => {
                        if (this.nodePositions.has(childId)) {
                            const childPos = this.nodePositions.get(childId);
                            this.drawConnection(pos.x, pos.y, childPos.x, childPos.y, 'default');
                        }
                    });
                }
            }
        });
    }

    /**
     * Draw Connection (n8n style bezier curve)
     */
    drawConnection(x1, y1, x2, y2, type = 'default') {
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

        // Connection points
        const startY = y1 + this.options.nodeHeight;
        const endY = y2;

        // Bezier curve control points
        const midY = (startY + endY) / 2;
        const d = `M ${x1} ${startY} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${endY}`;

        path.setAttribute('d', d);
        path.setAttribute('class', `n8n-connection ${this.options.animateConnections ? 'n8n-connection-animated' : ''}`);

        // สีตาม type
        if (type === 'left') {
            path.setAttribute('stroke', 'url(#conn-gradient-left)');
        } else if (type === 'right') {
            path.setAttribute('stroke', 'url(#conn-gradient-right)');
        } else {
            path.setAttribute('stroke', 'url(#conn-gradient-default)');
        }

        this.connectionsGroup.appendChild(path);
    }

    /**
     * Draw Node (n8n style)
     */
    drawNode(node, x, y) {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'n8n-node');
        g.setAttribute('transform', `translate(${x - this.options.nodeWidth / 2}, ${y})`);
        g.style.cursor = 'pointer';

        // สีตามสถานะ
        const status = node.retention_status || node.status || 'active';
        let accentColor, statusText, statusEmoji;

        switch (status) {
            case 'active':
                accentColor = '#10b981';
                statusText = 'Active';
                statusEmoji = '✓';
                break;
            case 'grace_period':
                accentColor = '#f59e0b';
                statusText = 'Grace';
                statusEmoji = '⏳';
                break;
            case 'inactive':
                accentColor = '#6b7280';
                statusText = 'Inactive';
                statusEmoji = '○';
                break;
            case 'suspended':
                accentColor = '#ef4444';
                statusText = 'Suspended';
                statusEmoji = '✕';
                break;
            default:
                accentColor = '#10b981';
                statusText = 'Active';
                statusEmoji = '✓';
        }

        const nodeWidth = this.options.nodeWidth;
        const nodeHeight = this.options.nodeHeight;

        g.innerHTML = `
            <!-- Node Card -->
            <foreignObject width="${nodeWidth}" height="${nodeHeight}" style="overflow: visible;">
                <div xmlns="http://www.w3.org/1999/xhtml" style="
                    width: ${nodeWidth}px;
                    height: ${nodeHeight}px;
                    background: white;
                    border: 2px solid #e2e8f0;
                    border-radius: 12px;
                    display: flex;
                    overflow: hidden;
                    transition: all 0.2s ease;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                " class="n8n-node-card" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)';this.style.borderColor='${accentColor}'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)';this.style.borderColor='#e2e8f0'">
                    <!-- Color Bar -->
                    <div style="width: 6px; background: ${accentColor}; flex-shrink: 0;"></div>

                    <!-- Content -->
                    <div style="flex: 1; padding: 10px 12px; display: flex; flex-direction: column; justify-content: center; min-width: 0;">
                        <!-- Header -->
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <div style="
                                width: 28px;
                                height: 28px;
                                background: linear-gradient(135deg, ${accentColor}, ${accentColor}dd);
                                border-radius: 6px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 12px;
                                font-weight: 700;
                                color: white;
                                flex-shrink: 0;
                            ">${this.escapeHtml((node.name || 'U').substring(0, 2).toUpperCase())}</div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-size: 13px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    ${this.escapeHtml(node.name || 'Unknown')}
                                </div>
                                <div style="font-size: 10px; color: #64748b;">
                                    ${this.escapeHtml(node.member_code || '')}
                                </div>
                            </div>
                        </div>

                        <!-- Stats Row -->
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 10px;">
                            <div style="display: flex; align-items: center; gap: 3px; color: #64748b;">
                                <span style="color: #a855f7; font-weight: 700;">${this.formatNumber(node.monthly_pv || node.total_pv || 0)}</span>
                                PV
                            </div>
                            <div style="display: flex; align-items: center; gap: 3px; color: #64748b;">
                                <span style="font-weight: 600;">${node.direct_referrals || node.total_direct_referrals || 0}</span>
                                refs
                            </div>
                            <div style="
                                margin-left: auto;
                                display: flex;
                                align-items: center;
                                gap: 3px;
                                padding: 2px 6px;
                                background: ${accentColor}15;
                                color: ${accentColor};
                                border-radius: 4px;
                                font-weight: 600;
                            ">
                                ${statusEmoji} ${statusText}
                            </div>
                        </div>
                    </div>
                </div>
            </foreignObject>

            <!-- Input Connector (Top) -->
            <circle cx="${nodeWidth / 2}" cy="0" r="5" fill="white" stroke="${accentColor}" stroke-width="2"/>

            <!-- Output Connector (Bottom) -->
            <circle cx="${nodeWidth / 2}" cy="${nodeHeight}" r="5" fill="${accentColor}" stroke="white" stroke-width="2"/>
        `;

        // Click handler
        g.addEventListener('click', (e) => {
            e.stopPropagation();
            this.showNodeDetail(node);
        });

        g.addEventListener('touchend', (e) => {
            if (!this.isDragging && e.changedTouches.length === 1) {
                e.preventDefault();
                e.stopPropagation();
                this.showNodeDetail(node);
            }
        });

        this.nodesGroup.appendChild(g);
    }

    /**
     * Show Node Detail Modal
     */
    showNodeDetail(node) {
        const status = node.retention_status || node.status || 'active';
        let accentColor, statusText;

        switch (status) {
            case 'active':
                accentColor = '#10b981';
                statusText = 'Active';
                break;
            case 'grace_period':
                accentColor = '#f59e0b';
                statusText = 'Grace Period';
                break;
            case 'inactive':
                accentColor = '#6b7280';
                statusText = 'Inactive';
                break;
            case 'suspended':
                accentColor = '#ef4444';
                statusText = 'Suspended';
                break;
            default:
                accentColor = '#10b981';
                statusText = 'Active';
        }

        this.modalContent.innerHTML = `
            <div style="padding: 0;">
                <!-- Header -->
                <div style="background: linear-gradient(135deg, ${accentColor}, ${accentColor}cc); padding: 24px; color: white;">
                    <button onclick="this.closest('.n8n-modal').classList.remove('show')" style="
                        position: absolute;
                        top: 16px;
                        right: 16px;
                        width: 32px;
                        height: 32px;
                        border: none;
                        background: rgba(255,255,255,0.2);
                        border-radius: 8px;
                        color: white;
                        font-size: 18px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">×</button>

                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="
                            width: 56px;
                            height: 56px;
                            background: rgba(255,255,255,0.2);
                            border-radius: 12px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 20px;
                            font-weight: 700;
                        ">${this.escapeHtml((node.name || 'U').substring(0, 2).toUpperCase())}</div>
                        <div>
                            <div style="font-size: 20px; font-weight: 700;">${this.escapeHtml(node.name || 'Unknown')}</div>
                            <div style="opacity: 0.9; font-size: 14px;">${this.escapeHtml(node.member_code || '')}</div>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div style="padding: 24px;">
                    <!-- Status Badge -->
                    <div style="
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                        padding: 6px 12px;
                        background: ${accentColor}15;
                        color: ${accentColor};
                        border-radius: 8px;
                        font-size: 13px;
                        font-weight: 600;
                        margin-bottom: 20px;
                    ">
                        <span style="width: 8px; height: 8px; background: ${accentColor}; border-radius: 50%;"></span>
                        ${statusText}
                    </div>

                    <!-- Stats Grid -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px;">
                        <div style="background: #f8fafc; padding: 16px; border-radius: 12px;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Total PV</div>
                            <div style="font-size: 24px; font-weight: 700; color: #a855f7;">${this.formatNumber(node.total_pv || 0)}</div>
                        </div>
                        <div style="background: #f8fafc; padding: 16px; border-radius: 12px;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Monthly PV</div>
                            <div style="font-size: 24px; font-weight: 700; color: #6366f1;">${this.formatNumber(node.monthly_pv || 0)}</div>
                        </div>
                        <div style="background: #f8fafc; padding: 16px; border-radius: 12px;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Direct Referrals</div>
                            <div style="font-size: 24px; font-weight: 700; color: #10b981;">${node.direct_referrals || node.total_direct_referrals || 0}</div>
                        </div>
                        <div style="background: #f8fafc; padding: 16px; border-radius: 12px;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Team Size</div>
                            <div style="font-size: 24px; font-weight: 700; color: #f59e0b;">${node.total_team_members || 0}</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    ${node.id ? `
                    <div style="display: flex; gap: 12px;">
                        <a href="/admin/mlm/members/${node.id}" style="
                            flex: 1;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 8px;
                            padding: 12px;
                            background: linear-gradient(135deg, #a855f7, #6366f1);
                            color: white;
                            border-radius: 10px;
                            font-weight: 600;
                            font-size: 14px;
                            text-decoration: none;
                            transition: transform 0.2s;
                        " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                            View Details
                        </a>
                        <a href="/admin/mlm/members/${node.id}/genealogy" style="
                            flex: 1;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 8px;
                            padding: 12px;
                            background: #f1f5f9;
                            color: #475569;
                            border-radius: 10px;
                            font-weight: 600;
                            font-size: 14px;
                            text-decoration: none;
                            transition: all 0.2s;
                        " onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            View Tree
                        </a>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;

        this.modal.classList.add('show');

        if (this.options.onNodeClick) {
            this.options.onNodeClick(node);
        }
    }

    hideModal() {
        this.modal.classList.remove('show');
    }

    /**
     * Calculate Bounds
     */
    calculateBounds() {
        let minX = Infinity, maxX = -Infinity;
        let minY = Infinity, maxY = -Infinity;

        this.nodePositions.forEach(pos => {
            minX = Math.min(minX, pos.x - this.options.nodeWidth / 2);
            maxX = Math.max(maxX, pos.x + this.options.nodeWidth / 2);
            minY = Math.min(minY, pos.y);
            maxY = Math.max(maxY, pos.y + this.options.nodeHeight);
        });

        return {
            minX,
            maxX,
            minY,
            maxY,
            width: maxX - minX,
            height: maxY - minY,
            centerX: (minX + maxX) / 2,
            centerY: (minY + maxY) / 2
        };
    }

    /**
     * Update Minimap
     */
    updateMinimap() {
        if (!this.minimapCanvas || !this.data) return;

        const canvas = this.minimapCanvas;
        const ctx = canvas.getContext('2d');
        const rect = this.canvas.getBoundingClientRect();

        // Set canvas size
        canvas.width = 180;
        canvas.height = 100;

        // Clear
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Calculate bounds
        const bounds = this.calculateBounds();
        const padding = 20;
        const scale = Math.min(
            (canvas.width - padding * 2) / bounds.width,
            (canvas.height - padding * 2) / bounds.height
        ) * 0.8;

        // Draw nodes
        ctx.fillStyle = '#a855f7';
        this.nodePositions.forEach(pos => {
            const x = (pos.x - bounds.centerX) * scale + canvas.width / 2;
            const y = (pos.y - bounds.minY) * scale + padding;
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, Math.PI * 2);
            ctx.fill();
        });

        // Update viewport rectangle
        const viewportScale = scale / this.transform.scale;
        const vpWidth = rect.width * viewportScale;
        const vpHeight = rect.height * viewportScale;
        const vpX = (-this.transform.x / this.transform.scale - bounds.centerX) * scale + canvas.width / 2;
        const vpY = (-this.transform.y / this.transform.scale - bounds.minY) * scale + padding;

        this.minimapViewport.style.left = `${Math.max(0, vpX)}px`;
        this.minimapViewport.style.top = `${Math.max(28, vpY + 28)}px`; // 28 = title height
        this.minimapViewport.style.width = `${Math.min(vpWidth, canvas.width)}px`;
        this.minimapViewport.style.height = `${Math.min(vpHeight, canvas.height)}px`;
    }

    /**
     * Update Stats
     */
    updateStats() {
        this.nodeCountDisplay.textContent = this.nodeCount;
        this.maxDepthDisplay.textContent = this.maxDepthReached;
    }

    /**
     * Utilities
     */
    formatNumber(num) {
        return Number(num || 0).toLocaleString('th-TH');
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    setTreeType(type) {
        this.options.treeType = type;
        if (this.data) {
            this.nodeCount = 0;
            this.maxDepthReached = 0;
            this.nodePositions.clear();
            this.render();
            this.resetView();
        }
    }

    setMaxDepth(depth) {
        this.options.maxDepth = depth;
        if (this.data) {
            this.nodeCount = 0;
            this.maxDepthReached = 0;
            this.nodePositions.clear();
            this.render();
        }
    }

    destroy() {
        this.container.innerHTML = '';
    }
}

// Export
window.OrgChartViewer = OrgChartViewer;
