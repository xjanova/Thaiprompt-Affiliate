/**
 * Workflow Diagram - n8n Style Node Editor
 * ระบบวาดผังสายงานแบบ Interactive เหมือน n8n
 *
 * ฟีเจอร์หลัก:
 * - ลาก nodes ได้อิสระ (Free dragging)
 * - ลากเส้นเชื่อมต่อ (Connection drawing)
 * - Bezier curves แบบ n8n
 * - Snap to grid
 * - Zoom & Pan
 * - Multi-select
 * - Delete nodes/connections
 * - Touch support
 *
 * @author TP-Affiliate Team
 * @version 1.0.0
 */

class WorkflowDiagram {
    /**
     * สร้าง WorkflowDiagram instance
     *
     * @param {string|HTMLElement} container - Container element หรือ ID
     * @param {Object} options - ตัวเลือกการตั้งค่า
     */
    constructor(container, options = {}) {
        this.container = typeof container === 'string'
            ? document.getElementById(container)
            : container;

        if (!this.container) {
            console.error('WorkflowDiagram: Container not found');
            return;
        }

        // ตัวเลือกเริ่มต้น
        this.options = {
            gridSize: options.gridSize || 20,
            snapToGrid: options.snapToGrid !== false,
            showGrid: options.showGrid !== false,
            nodeWidth: options.nodeWidth || 200,
            nodeHeight: options.nodeHeight || 80,
            portRadius: options.portRadius || 8,
            minScale: options.minScale || 0.1,
            maxScale: options.maxScale || 3,
            connectionStyle: options.connectionStyle || 'bezier', // 'bezier' | 'step' | 'straight'
            animateConnections: options.animateConnections !== false,
            editable: options.editable !== false,
            onNodeClick: options.onNodeClick || null,
            onNodeDragEnd: options.onNodeDragEnd || null,
            onConnectionCreate: options.onConnectionCreate || null,
            onConnectionDelete: options.onConnectionDelete || null,
            onNodeDelete: options.onNodeDelete || null,
            onSelectionChange: options.onSelectionChange || null,
            ...options
        };

        // State
        this.nodes = new Map();
        this.connections = [];
        this.selectedNodes = new Set();
        this.selectedConnection = null;

        // Transform state (pan & zoom)
        this.transform = { x: 0, y: 0, scale: 1 };

        // Interaction state
        this.isDragging = false;
        this.isPanning = false;
        this.isDrawingConnection = false;
        this.isSelecting = false;
        this.dragNode = null;
        this.dragOffset = { x: 0, y: 0 };
        this.panStart = { x: 0, y: 0 };
        this.connectionStart = null; // { nodeId, portType, portIndex }
        this.tempConnection = null;
        this.selectionBox = null;

        // Touch state
        this.lastTouchDistance = null;
        this.lastTouchCenter = null;

        // Initialize
        this.init();
    }

    /**
     * Initialize component
     */
    init() {
        this.createDOM();
        this.attachEventListeners();
        this.render();
    }

    /**
     * สร้าง DOM structure
     */
    createDOM() {
        this.container.innerHTML = `
            <div class="wf-diagram-wrapper">
                <!-- Loading -->
                <div class="wf-loading">
                    <div class="wf-loading-spinner"></div>
                    <p>กำลังโหลด...</p>
                </div>

                <!-- Canvas -->
                <div class="wf-canvas">
                    <svg class="wf-svg">
                        <defs>
                            <!-- Grid Pattern -->
                            <pattern id="wf-grid-dots" width="${this.options.gridSize}" height="${this.options.gridSize}" patternUnits="userSpaceOnUse">
                                <circle cx="1" cy="1" r="1" fill="#d1d5db"/>
                            </pattern>
                            <pattern id="wf-grid-large" width="${this.options.gridSize * 5}" height="${this.options.gridSize * 5}" patternUnits="userSpaceOnUse">
                                <rect width="${this.options.gridSize * 5}" height="${this.options.gridSize * 5}" fill="url(#wf-grid-dots)"/>
                                <circle cx="1" cy="1" r="1.5" fill="#9ca3af"/>
                            </pattern>

                            <!-- Connection Gradients -->
                            <linearGradient id="wf-gradient-default" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#a855f7"/>
                                <stop offset="100%" style="stop-color:#6366f1"/>
                            </linearGradient>
                            <linearGradient id="wf-gradient-success" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#10b981"/>
                                <stop offset="100%" style="stop-color:#06b6d4"/>
                            </linearGradient>
                            <linearGradient id="wf-gradient-warning" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#f59e0b"/>
                                <stop offset="100%" style="stop-color:#f97316"/>
                            </linearGradient>
                            <linearGradient id="wf-gradient-danger" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#ef4444"/>
                                <stop offset="100%" style="stop-color:#dc2626"/>
                            </linearGradient>
                            <linearGradient id="wf-gradient-temp" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#94a3b8"/>
                                <stop offset="100%" style="stop-color:#64748b"/>
                            </linearGradient>

                            <!-- Glow Filter -->
                            <filter id="wf-glow" x="-50%" y="-50%" width="200%" height="200%">
                                <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
                                <feMerge>
                                    <feMergeNode in="coloredBlur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>

                            <!-- Shadow Filter -->
                            <filter id="wf-shadow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="#000" flood-opacity="0.15"/>
                            </filter>

                            <!-- Arrow Marker -->
                            <marker id="wf-arrow" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto">
                                <path d="M2,2 L10,6 L2,10 L4,6 Z" fill="#a855f7"/>
                            </marker>
                        </defs>

                        <!-- Grid Background -->
                        <rect class="wf-grid-bg" width="20000" height="20000" x="-10000" y="-10000" fill="url(#wf-grid-large)"/>

                        <!-- Main Transform Group -->
                        <g class="wf-transform-group">
                            <!-- Connections Layer (behind nodes) -->
                            <g class="wf-connections-layer"></g>
                            <!-- Temporary Connection (while drawing) -->
                            <g class="wf-temp-connection-layer"></g>
                            <!-- Nodes Layer -->
                            <g class="wf-nodes-layer"></g>
                            <!-- Selection Box -->
                            <rect class="wf-selection-box" fill="rgba(99, 102, 241, 0.1)" stroke="#6366f1" stroke-width="1" stroke-dasharray="4" style="display:none"/>
                        </g>
                    </svg>
                </div>

                <!-- Controls -->
                <div class="wf-controls">
                    <button class="wf-ctrl-btn wf-zoom-in" title="ซูมเข้า">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v6M8 11h6"/>
                        </svg>
                    </button>
                    <div class="wf-zoom-display">100%</div>
                    <button class="wf-ctrl-btn wf-zoom-out" title="ซูมออก">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M8 11h6"/>
                        </svg>
                    </button>
                    <div class="wf-ctrl-divider"></div>
                    <button class="wf-ctrl-btn wf-fit" title="พอดีหน้าจอ">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
                        </svg>
                    </button>
                    <button class="wf-ctrl-btn wf-reset" title="รีเซ็ต">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                        </svg>
                    </button>
                    <div class="wf-ctrl-divider"></div>
                    <button class="wf-ctrl-btn wf-fullscreen" title="เต็มจอ">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 3H5a2 2 0 0 0-2 2v3"/>
                            <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
                            <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
                            <path d="M16 21h3a2 2 0 0 0 2-2v-3"/>
                        </svg>
                    </button>
                </div>

                <!-- Toolbar (for editable mode) -->
                <div class="wf-toolbar ${!this.options.editable ? 'hidden' : ''}">
                    <button class="wf-tool-btn wf-delete-selected" title="ลบที่เลือก (Del)" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        ลบ
                    </button>
                    <button class="wf-tool-btn wf-select-all" title="เลือกทั้งหมด (Ctrl+A)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M9 12l2 2 4-4"/>
                        </svg>
                        เลือกทั้งหมด
                    </button>
                </div>

                <!-- Mini Map -->
                <div class="wf-minimap">
                    <div class="wf-minimap-title">Overview</div>
                    <canvas class="wf-minimap-canvas" width="160" height="100"></canvas>
                    <div class="wf-minimap-viewport"></div>
                </div>

                <!-- Stats -->
                <div class="wf-stats">
                    <div class="wf-stat">
                        <span class="wf-stat-icon">📦</span>
                        <span class="wf-stat-value wf-node-count">0</span>
                        <span class="wf-stat-label">Nodes</span>
                    </div>
                    <div class="wf-stat">
                        <span class="wf-stat-icon">🔗</span>
                        <span class="wf-stat-value wf-connection-count">0</span>
                        <span class="wf-stat-label">Connections</span>
                    </div>
                </div>

                <!-- Help -->
                <div class="wf-help">
                    <div class="wf-help-item"><kbd>Scroll</kbd> ซูม</div>
                    <div class="wf-help-item"><kbd>Drag</kbd> เลื่อน canvas</div>
                    <div class="wf-help-item"><kbd>Drag node</kbd> ย้าย node</div>
                    <div class="wf-help-item"><kbd>Drag port</kbd> เชื่อมต่อ</div>
                </div>

                <!-- Context Menu -->
                <div class="wf-context-menu" style="display:none">
                    <div class="wf-context-item wf-ctx-edit">แก้ไข</div>
                    <div class="wf-context-item wf-ctx-duplicate">คัดลอก</div>
                    <div class="wf-context-divider"></div>
                    <div class="wf-context-item wf-ctx-delete text-red-500">ลบ</div>
                </div>

                <!-- Node Detail Modal -->
                <div class="wf-modal">
                    <div class="wf-modal-backdrop"></div>
                    <div class="wf-modal-content"></div>
                </div>
            </div>

            <style>
                /* Workflow Diagram Styles */
                .wf-diagram-wrapper {
                    --wf-bg: #f8fafc;
                    --wf-bg-dark: #0f172a;
                    --wf-node-bg: #ffffff;
                    --wf-node-bg-dark: #1e293b;
                    --wf-text: #1e293b;
                    --wf-text-dark: #f1f5f9;
                    --wf-text-muted: #64748b;
                    --wf-border: #e2e8f0;
                    --wf-border-dark: #334155;
                    --wf-primary: #6366f1;
                    --wf-primary-light: #818cf8;
                    --wf-success: #10b981;
                    --wf-warning: #f59e0b;
                    --wf-danger: #ef4444;

                    position: relative;
                    width: 100%;
                    height: 100%;
                    min-height: 500px;
                    background: var(--wf-bg);
                    border-radius: 16px;
                    overflow: hidden;
                    font-family: 'Inter', system-ui, sans-serif;
                }

                .dark .wf-diagram-wrapper {
                    --wf-bg: var(--wf-bg-dark);
                    --wf-node-bg: var(--wf-node-bg-dark);
                    --wf-text: var(--wf-text-dark);
                    --wf-border: var(--wf-border-dark);
                }

                /* Loading */
                .wf-loading {
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

                .wf-loading.hidden {
                    opacity: 0;
                    pointer-events: none;
                }

                .wf-loading-spinner {
                    width: 40px;
                    height: 40px;
                    border: 3px solid var(--wf-border);
                    border-top-color: var(--wf-primary);
                    border-radius: 50%;
                    animation: wf-spin 0.8s linear infinite;
                }

                @keyframes wf-spin {
                    to { transform: rotate(360deg); }
                }

                /* Canvas */
                .wf-canvas {
                    position: absolute;
                    inset: 0;
                    cursor: grab;
                    touch-action: none;
                }

                .wf-canvas.dragging-node {
                    cursor: grabbing;
                }

                .wf-canvas.drawing-connection {
                    cursor: crosshair;
                }

                .wf-canvas:active:not(.dragging-node):not(.drawing-connection) {
                    cursor: grabbing;
                }

                .wf-svg {
                    width: 100%;
                    height: 100%;
                }

                .dark .wf-grid-bg {
                    opacity: 0.4;
                }

                /* Controls */
                .wf-controls {
                    position: absolute;
                    top: 16px;
                    right: 16px;
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                    background: var(--wf-node-bg);
                    border: 1px solid var(--wf-border);
                    border-radius: 12px;
                    padding: 6px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    z-index: 10;
                }

                .wf-ctrl-btn {
                    width: 36px;
                    height: 36px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: transparent;
                    border: none;
                    border-radius: 8px;
                    color: var(--wf-text-muted);
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .wf-ctrl-btn:hover {
                    background: rgba(99, 102, 241, 0.1);
                    color: var(--wf-primary);
                }

                .wf-ctrl-btn:active {
                    transform: scale(0.95);
                }

                .wf-ctrl-btn svg {
                    width: 18px;
                    height: 18px;
                }

                .wf-zoom-display {
                    font-size: 11px;
                    font-weight: 600;
                    text-align: center;
                    padding: 4px 0;
                    color: var(--wf-text-muted);
                }

                .wf-ctrl-divider {
                    height: 1px;
                    background: var(--wf-border);
                    margin: 4px 0;
                }

                /* Toolbar */
                .wf-toolbar {
                    position: absolute;
                    top: 16px;
                    left: 50%;
                    transform: translateX(-50%);
                    display: flex;
                    gap: 8px;
                    background: var(--wf-node-bg);
                    border: 1px solid var(--wf-border);
                    border-radius: 12px;
                    padding: 8px 12px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    z-index: 10;
                }

                .wf-toolbar.hidden {
                    display: none;
                }

                .wf-tool-btn {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    padding: 8px 12px;
                    background: transparent;
                    border: 1px solid var(--wf-border);
                    border-radius: 8px;
                    color: var(--wf-text);
                    font-size: 13px;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .wf-tool-btn:hover:not(:disabled) {
                    background: var(--wf-primary);
                    border-color: var(--wf-primary);
                    color: white;
                }

                .wf-tool-btn:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                .wf-tool-btn svg {
                    width: 16px;
                    height: 16px;
                }

                /* Mini Map */
                .wf-minimap {
                    position: absolute;
                    bottom: 16px;
                    right: 16px;
                    background: var(--wf-node-bg);
                    border: 1px solid var(--wf-border);
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    z-index: 10;
                }

                .wf-minimap-title {
                    padding: 8px 12px;
                    font-size: 11px;
                    font-weight: 600;
                    color: var(--wf-text-muted);
                    border-bottom: 1px solid var(--wf-border);
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .wf-minimap-canvas {
                    display: block;
                }

                .wf-minimap-viewport {
                    position: absolute;
                    border: 2px solid var(--wf-primary);
                    background: rgba(99, 102, 241, 0.1);
                    border-radius: 4px;
                    pointer-events: none;
                }

                /* Stats */
                .wf-stats {
                    position: absolute;
                    top: 16px;
                    left: 16px;
                    display: flex;
                    gap: 8px;
                    z-index: 10;
                }

                .wf-stat {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    background: var(--wf-node-bg);
                    border: 1px solid var(--wf-border);
                    border-radius: 10px;
                    padding: 8px 12px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                }

                .wf-stat-icon {
                    font-size: 16px;
                }

                .wf-stat-value {
                    font-size: 16px;
                    font-weight: 700;
                    color: var(--wf-text);
                }

                .wf-stat-label {
                    font-size: 11px;
                    color: var(--wf-text-muted);
                }

                /* Help */
                .wf-help {
                    position: absolute;
                    bottom: 16px;
                    left: 16px;
                    display: flex;
                    gap: 16px;
                    z-index: 10;
                }

                .wf-help-item {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 12px;
                    color: var(--wf-text-muted);
                }

                .wf-help-item kbd {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 20px;
                    height: 20px;
                    padding: 0 6px;
                    background: var(--wf-node-bg);
                    border: 1px solid var(--wf-border);
                    border-radius: 4px;
                    font-size: 10px;
                    font-weight: 600;
                    font-family: inherit;
                }

                /* Context Menu */
                .wf-context-menu {
                    position: fixed;
                    min-width: 150px;
                    background: var(--wf-node-bg);
                    border: 1px solid var(--wf-border);
                    border-radius: 10px;
                    padding: 6px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                    z-index: 100;
                }

                .wf-context-item {
                    padding: 10px 14px;
                    font-size: 13px;
                    color: var(--wf-text);
                    border-radius: 6px;
                    cursor: pointer;
                    transition: background 0.15s;
                }

                .wf-context-item:hover {
                    background: rgba(99, 102, 241, 0.1);
                }

                .wf-context-divider {
                    height: 1px;
                    background: var(--wf-border);
                    margin: 4px 0;
                }

                /* Modal */
                .wf-modal {
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

                .wf-modal.show {
                    opacity: 1;
                    pointer-events: auto;
                }

                .wf-modal-backdrop {
                    position: absolute;
                    inset: 0;
                    background: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(4px);
                }

                .wf-modal-content {
                    position: relative;
                    width: 90%;
                    max-width: 480px;
                    max-height: 80vh;
                    background: var(--wf-node-bg);
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
                    transform: scale(0.95);
                    transition: transform 0.3s;
                }

                .wf-modal.show .wf-modal-content {
                    transform: scale(1);
                }

                /* Connection Animations */
                @keyframes wf-flow {
                    from { stroke-dashoffset: 24; }
                    to { stroke-dashoffset: 0; }
                }

                .wf-connection {
                    fill: none;
                    stroke-width: 2.5;
                    stroke-linecap: round;
                    transition: stroke-width 0.2s;
                }

                .wf-connection:hover {
                    stroke-width: 4;
                    cursor: pointer;
                }

                .wf-connection.selected {
                    stroke-width: 4;
                    filter: url(#wf-glow);
                }

                .wf-connection-animated {
                    stroke-dasharray: 8 4;
                    animation: wf-flow 0.5s linear infinite;
                }

                .wf-temp-connection {
                    fill: none;
                    stroke: url(#wf-gradient-temp);
                    stroke-width: 2;
                    stroke-dasharray: 6 4;
                    stroke-linecap: round;
                }

                /* Node Styles */
                .wf-node {
                    cursor: grab;
                    transition: filter 0.2s;
                }

                .wf-node:hover {
                    filter: url(#wf-shadow);
                }

                .wf-node.selected .wf-node-body {
                    stroke: var(--wf-primary);
                    stroke-width: 3;
                }

                .wf-node.dragging {
                    cursor: grabbing;
                    opacity: 0.9;
                }

                .wf-node-body {
                    transition: stroke 0.2s, stroke-width 0.2s;
                }

                /* Port Styles */
                .wf-port {
                    cursor: crosshair;
                    transition: transform 0.15s, r 0.15s;
                }

                .wf-port:hover {
                    transform: scale(1.3);
                }

                .wf-port.active {
                    r: 10;
                    fill: var(--wf-primary);
                }

                /* Fullscreen Mode */
                .wf-diagram-wrapper.fullscreen {
                    position: fixed !important;
                    inset: 0 !important;
                    z-index: 999999 !important;
                    border-radius: 0 !important;
                    width: 100vw !important;
                    height: 100vh !important;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .wf-minimap,
                    .wf-help,
                    .wf-toolbar {
                        display: none;
                    }

                    .wf-stats {
                        flex-direction: column;
                    }
                }
            </style>
        `;

        // เก็บ references
        this.wrapper = this.container.querySelector('.wf-diagram-wrapper');
        this.loading = this.container.querySelector('.wf-loading');
        this.canvas = this.container.querySelector('.wf-canvas');
        this.svg = this.container.querySelector('.wf-svg');
        this.transformGroup = this.container.querySelector('.wf-transform-group');
        this.connectionsLayer = this.container.querySelector('.wf-connections-layer');
        this.tempConnectionLayer = this.container.querySelector('.wf-temp-connection-layer');
        this.nodesLayer = this.container.querySelector('.wf-nodes-layer');
        this.selectionBoxEl = this.container.querySelector('.wf-selection-box');
        this.zoomDisplay = this.container.querySelector('.wf-zoom-display');
        this.nodeCountDisplay = this.container.querySelector('.wf-node-count');
        this.connectionCountDisplay = this.container.querySelector('.wf-connection-count');
        this.modal = this.container.querySelector('.wf-modal');
        this.modalContent = this.container.querySelector('.wf-modal-content');
        this.contextMenu = this.container.querySelector('.wf-context-menu');
        this.minimapCanvas = this.container.querySelector('.wf-minimap-canvas');
        this.minimapViewport = this.container.querySelector('.wf-minimap-viewport');
        this.deleteBtn = this.container.querySelector('.wf-delete-selected');
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Control buttons
        this.container.querySelector('.wf-zoom-in').addEventListener('click', () => this.zoomIn());
        this.container.querySelector('.wf-zoom-out').addEventListener('click', () => this.zoomOut());
        this.container.querySelector('.wf-fit').addEventListener('click', () => this.fitToView());
        this.container.querySelector('.wf-reset').addEventListener('click', () => this.resetView());
        this.container.querySelector('.wf-fullscreen').addEventListener('click', () => this.toggleFullscreen());
        this.container.querySelector('.wf-select-all')?.addEventListener('click', () => this.selectAll());

        if (this.deleteBtn) {
            this.deleteBtn.addEventListener('click', () => this.deleteSelected());
        }

        // Mouse events on canvas
        this.canvas.addEventListener('mousedown', (e) => this.onMouseDown(e));
        this.canvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
        this.canvas.addEventListener('mouseup', (e) => this.onMouseUp(e));
        this.canvas.addEventListener('mouseleave', (e) => this.onMouseUp(e));
        this.canvas.addEventListener('wheel', (e) => this.onWheel(e), { passive: false });
        this.canvas.addEventListener('dblclick', (e) => this.onDoubleClick(e));
        this.canvas.addEventListener('contextmenu', (e) => this.onContextMenu(e));

        // Touch events
        this.canvas.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: false });
        this.canvas.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: false });
        this.canvas.addEventListener('touchend', (e) => this.onTouchEnd(e));

        // Keyboard events
        document.addEventListener('keydown', (e) => this.onKeyDown(e));

        // Modal
        this.container.querySelector('.wf-modal-backdrop').addEventListener('click', () => this.hideModal());

        // Context menu
        document.addEventListener('click', () => this.hideContextMenu());

        // Fullscreen change
        document.addEventListener('fullscreenchange', () => this.onFullscreenChange());
    }

    /**
     * Mouse Events
     */
    onMouseDown(e) {
        const point = this.getMousePoint(e);
        const target = e.target;

        // Hide context menu
        this.hideContextMenu();

        // บันทึกเวลาเริ่มคลิก สำหรับตรวจสอบว่าเป็นคลิกหรือลาก
        this.clickStartTime = Date.now();
        this.clickStartPoint = { x: point.x, y: point.y };
        this.hasMoved = false;

        // Check if clicked on a node (รวม port ด้วย - ลากได้ทั้งการ์ด)
        const nodeGroup = target.closest('.wf-node');
        if (nodeGroup) {
            e.stopPropagation();
            const nodeId = nodeGroup.dataset.nodeId;
            this.clickedNodeId = nodeId;

            // Handle selection
            if (e.ctrlKey || e.metaKey) {
                // Multi-select toggle
                if (this.selectedNodes.has(nodeId)) {
                    this.deselectNode(nodeId);
                } else {
                    this.selectNode(nodeId, true);
                }
            } else if (!this.selectedNodes.has(nodeId)) {
                // Single select
                this.clearSelection();
                this.selectNode(nodeId);
            }

            // Start dragging - ลากได้ทั้งการ์ด ไม่จำกัดแค่ตำแหน่งใดตำแหน่งหนึ่ง
            if (this.options.editable) {
                this.startNodeDrag(nodeId, point);
            }
            return;
        }

        // Check if clicked on a connection
        if (target.classList.contains('wf-connection')) {
            e.stopPropagation();
            const connectionIndex = parseInt(target.dataset.connectionIndex);
            this.selectConnection(connectionIndex);
            return;
        }

        // Start panning or selection box
        if (e.button === 0) {
            if (e.shiftKey && this.options.editable) {
                // Selection box
                this.startSelectionBox(point);
            } else {
                // Pan
                this.startPan(point);
            }
        }
    }

    onMouseMove(e) {
        const point = this.getMousePoint(e);

        // ตรวจสอบว่ามีการเลื่อนเกิน threshold หรือไม่ (สำหรับแยก click vs drag)
        if (this.clickStartPoint) {
            const dx = Math.abs(point.x - this.clickStartPoint.x);
            const dy = Math.abs(point.y - this.clickStartPoint.y);
            if (dx > 5 || dy > 5) {
                this.hasMoved = true;
            }
        }

        // Update temp connection if drawing
        if (this.isDrawingConnection) {
            this.updateTempConnection(point);
            return;
        }

        // Update node position if dragging
        if (this.isDragging && this.dragNode) {
            this.updateNodeDrag(point);
            return;
        }

        // Update pan if panning
        if (this.isPanning) {
            this.updatePan(point);
            return;
        }

        // Update selection box
        if (this.isSelecting) {
            this.updateSelectionBox(point);
            return;
        }
    }

    onMouseUp(e) {
        const point = this.getMousePoint(e);
        const clickDuration = Date.now() - (this.clickStartTime || 0);

        // ตรวจสอบว่าเป็นการคลิกเพื่อดูรายละเอียด (ไม่ได้ลาก และคลิกเร็ว)
        if (this.clickedNodeId && !this.hasMoved && clickDuration < 300) {
            const node = this.nodes.get(this.clickedNodeId);
            if (node) {
                this.showNodeDetail(node);
            }
        }

        // Reset click state
        this.clickedNodeId = null;
        this.clickStartTime = null;
        this.clickStartPoint = null;
        this.hasMoved = false;

        // Finish connection drawing
        if (this.isDrawingConnection) {
            this.finishConnectionDrawing(e.target, point);
        }

        // Finish node drag
        if (this.isDragging && this.dragNode) {
            this.finishNodeDrag();
        }

        // Finish pan
        if (this.isPanning) {
            this.finishPan();
        }

        // Finish selection box
        if (this.isSelecting) {
            this.finishSelectionBox();
        }
    }

    onWheel(e) {
        e.preventDefault();
        const rect = this.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        this.zoomAt(mouseX, mouseY, delta);
    }

    onDoubleClick(e) {
        const nodeGroup = e.target.closest('.wf-node');
        if (nodeGroup) {
            const nodeId = nodeGroup.dataset.nodeId;
            const node = this.nodes.get(nodeId);
            if (node) {
                this.showNodeDetail(node);
            }
        }
    }

    onContextMenu(e) {
        e.preventDefault();
        const nodeGroup = e.target.closest('.wf-node');

        if (nodeGroup && this.options.editable) {
            const nodeId = nodeGroup.dataset.nodeId;
            if (!this.selectedNodes.has(nodeId)) {
                this.clearSelection();
                this.selectNode(nodeId);
            }
            this.showContextMenu(e.clientX, e.clientY, 'node');
        }
    }

    onKeyDown(e) {
        // Only handle if canvas is focused
        if (!this.container.contains(document.activeElement) &&
            document.activeElement !== document.body) {
            return;
        }

        // Delete key
        if ((e.key === 'Delete' || e.key === 'Backspace') && this.options.editable) {
            this.deleteSelected();
            e.preventDefault();
        }

        // Ctrl+A - Select all
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && this.options.editable) {
            this.selectAll();
            e.preventDefault();
        }

        // Escape - Clear selection
        if (e.key === 'Escape') {
            this.clearSelection();
            this.hideModal();
        }
    }

    /**
     * Touch Events
     */
    onTouchStart(e) {
        if (e.touches.length === 1) {
            const touch = e.touches[0];
            const point = this.getTouchPoint(touch);

            // บันทึกเวลาเริ่มแตะ สำหรับตรวจสอบว่าเป็น tap หรือ drag
            this.touchStartTime = Date.now();
            this.touchStartPoint = { x: point.x, y: point.y };
            this.touchHasMoved = false;

            // Simulate mouse down
            const target = document.elementFromPoint(touch.clientX, touch.clientY);
            const nodeGroup = target?.closest('.wf-node');

            if (nodeGroup) {
                const nodeId = nodeGroup.dataset.nodeId;
                this.touchedNodeId = nodeId;
                this.clearSelection();
                this.selectNode(nodeId);
                if (this.options.editable) {
                    this.startNodeDrag(nodeId, point);
                }
            } else {
                this.startPan(point);
            }
        } else if (e.touches.length === 2) {
            // Pinch to zoom
            e.preventDefault();
            this.finishPan();
            this.lastTouchDistance = this.getTouchDistance(e.touches);
            this.lastTouchCenter = this.getTouchCenter(e.touches);
        }
    }

    onTouchMove(e) {
        if (e.touches.length === 1) {
            const touch = e.touches[0];
            const point = this.getTouchPoint(touch);

            // ตรวจสอบว่ามีการเลื่อนเกิน threshold หรือไม่
            if (this.touchStartPoint) {
                const dx = Math.abs(point.x - this.touchStartPoint.x);
                const dy = Math.abs(point.y - this.touchStartPoint.y);
                if (dx > 10 || dy > 10) {
                    this.touchHasMoved = true;
                }
            }

            if (this.isDragging && this.dragNode) {
                this.updateNodeDrag(point);
            } else if (this.isPanning) {
                this.updatePan(point);
            }
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
            const touchDuration = Date.now() - (this.touchStartTime || 0);

            // ตรวจสอบว่าเป็นการแตะเพื่อดูรายละเอียด (ไม่ได้ลาก และแตะเร็ว)
            if (this.touchedNodeId && !this.touchHasMoved && touchDuration < 300) {
                const node = this.nodes.get(this.touchedNodeId);
                if (node) {
                    this.showNodeDetail(node);
                }
            }

            // Reset touch state
            this.touchedNodeId = null;
            this.touchStartTime = null;
            this.touchStartPoint = null;
            this.touchHasMoved = false;

            this.finishNodeDrag();
            this.finishPan();
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
     * Get canvas-relative mouse point
     */
    getMousePoint(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            clientX: e.clientX,
            clientY: e.clientY,
            x: (e.clientX - rect.left - this.transform.x) / this.transform.scale,
            y: (e.clientY - rect.top - this.transform.y) / this.transform.scale
        };
    }

    getTouchPoint(touch) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            clientX: touch.clientX,
            clientY: touch.clientY,
            x: (touch.clientX - rect.left - this.transform.x) / this.transform.scale,
            y: (touch.clientY - rect.top - this.transform.y) / this.transform.scale
        };
    }

    /**
     * Node Dragging
     */
    startNodeDrag(nodeId, point) {
        this.isDragging = true;
        this.dragNode = nodeId;
        const node = this.nodes.get(nodeId);
        if (node) {
            this.dragOffset = {
                x: point.x - node.x,
                y: point.y - node.y
            };
        }
        this.canvas.classList.add('dragging-node');
    }

    updateNodeDrag(point) {
        if (!this.dragNode) return;

        let newX = point.x - this.dragOffset.x;
        let newY = point.y - this.dragOffset.y;

        // Snap to grid
        if (this.options.snapToGrid) {
            newX = Math.round(newX / this.options.gridSize) * this.options.gridSize;
            newY = Math.round(newY / this.options.gridSize) * this.options.gridSize;
        }

        // Update node position
        const node = this.nodes.get(this.dragNode);
        if (node) {
            const dx = newX - node.x;
            const dy = newY - node.y;
            node.x = newX;
            node.y = newY;

            // Move all selected nodes together
            if (this.selectedNodes.size > 1) {
                this.selectedNodes.forEach(id => {
                    if (id !== this.dragNode) {
                        const otherNode = this.nodes.get(id);
                        if (otherNode) {
                            otherNode.x += dx;
                            otherNode.y += dy;
                        }
                    }
                });
            }

            this.render();
        }
    }

    finishNodeDrag() {
        if (this.isDragging && this.dragNode) {
            const node = this.nodes.get(this.dragNode);
            if (node && this.options.onNodeDragEnd) {
                this.options.onNodeDragEnd(node);
            }
        }
        this.isDragging = false;
        this.dragNode = null;
        this.canvas.classList.remove('dragging-node');
    }

    /**
     * Panning
     */
    startPan(point) {
        this.isPanning = true;
        this.panStart = {
            x: point.clientX - this.transform.x,
            y: point.clientY - this.transform.y
        };
    }

    updatePan(point) {
        this.transform.x = point.clientX - this.panStart.x;
        this.transform.y = point.clientY - this.panStart.y;
        this.applyTransform();
        this.updateMinimap();
    }

    finishPan() {
        this.isPanning = false;
    }

    /**
     * Selection Box
     */
    startSelectionBox(point) {
        this.isSelecting = true;
        this.selectionBox = {
            startX: point.x,
            startY: point.y,
            endX: point.x,
            endY: point.y
        };
        this.selectionBoxEl.style.display = 'block';
    }

    updateSelectionBox(point) {
        this.selectionBox.endX = point.x;
        this.selectionBox.endY = point.y;

        const x = Math.min(this.selectionBox.startX, this.selectionBox.endX);
        const y = Math.min(this.selectionBox.startY, this.selectionBox.endY);
        const width = Math.abs(this.selectionBox.endX - this.selectionBox.startX);
        const height = Math.abs(this.selectionBox.endY - this.selectionBox.startY);

        this.selectionBoxEl.setAttribute('x', x);
        this.selectionBoxEl.setAttribute('y', y);
        this.selectionBoxEl.setAttribute('width', width);
        this.selectionBoxEl.setAttribute('height', height);
    }

    finishSelectionBox() {
        if (!this.isSelecting) return;

        const x = Math.min(this.selectionBox.startX, this.selectionBox.endX);
        const y = Math.min(this.selectionBox.startY, this.selectionBox.endY);
        const width = Math.abs(this.selectionBox.endX - this.selectionBox.startX);
        const height = Math.abs(this.selectionBox.endY - this.selectionBox.startY);

        // Select nodes within box
        this.clearSelection();
        this.nodes.forEach((node, id) => {
            const nodeX = node.x + this.options.nodeWidth / 2;
            const nodeY = node.y + this.options.nodeHeight / 2;
            if (nodeX >= x && nodeX <= x + width && nodeY >= y && nodeY <= y + height) {
                this.selectNode(id, true);
            }
        });

        this.selectionBoxEl.style.display = 'none';
        this.isSelecting = false;
        this.selectionBox = null;
    }

    /**
     * Connection Drawing
     */
    startConnectionDrawing(portElement, point) {
        const nodeGroup = portElement.closest('.wf-node');
        if (!nodeGroup) return;

        this.isDrawingConnection = true;
        this.connectionStart = {
            nodeId: nodeGroup.dataset.nodeId,
            portType: portElement.dataset.portType,
            portIndex: parseInt(portElement.dataset.portIndex) || 0,
            x: parseFloat(portElement.getAttribute('cx')) + parseFloat(nodeGroup.getAttribute('transform').match(/translate\(([^,]+)/)?.[1] || 0),
            y: parseFloat(portElement.getAttribute('cy')) + parseFloat(nodeGroup.getAttribute('transform').match(/,\s*([^)]+)/)?.[1] || 0)
        };

        this.canvas.classList.add('drawing-connection');
        portElement.classList.add('active');

        // Create temp connection path
        this.tempConnection = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        this.tempConnection.classList.add('wf-temp-connection');
        this.tempConnectionLayer.appendChild(this.tempConnection);
    }

    updateTempConnection(point) {
        if (!this.tempConnection || !this.connectionStart) return;

        const startX = this.connectionStart.x;
        const startY = this.connectionStart.y;
        const endX = point.x;
        const endY = point.y;

        const path = this.createBezierPath(startX, startY, endX, endY,
            this.connectionStart.portType === 'output' ? 'down' : 'up');
        this.tempConnection.setAttribute('d', path);
    }

    finishConnectionDrawing(target, point) {
        if (!this.isDrawingConnection) return;

        // Check if ended on a port
        if (target.classList.contains('wf-port')) {
            const endNodeGroup = target.closest('.wf-node');
            if (endNodeGroup) {
                const endNodeId = endNodeGroup.dataset.nodeId;
                const endPortType = target.dataset.portType;
                const endPortIndex = parseInt(target.dataset.portIndex) || 0;

                // Valid connection: output to input
                if (this.connectionStart.portType === 'output' && endPortType === 'input' &&
                    this.connectionStart.nodeId !== endNodeId) {
                    this.addConnection(
                        this.connectionStart.nodeId,
                        this.connectionStart.portIndex,
                        endNodeId,
                        endPortIndex
                    );
                }
            }
        }

        // Cleanup
        this.isDrawingConnection = false;
        this.connectionStart = null;
        this.canvas.classList.remove('drawing-connection');

        if (this.tempConnection) {
            this.tempConnection.remove();
            this.tempConnection = null;
        }

        // Remove active class from all ports
        this.container.querySelectorAll('.wf-port.active').forEach(p => p.classList.remove('active'));
    }

    /**
     * Create Bezier path for connection
     */
    createBezierPath(x1, y1, x2, y2, direction = 'down') {
        const controlOffset = Math.min(Math.abs(y2 - y1) * 0.5, 100);

        let cp1x, cp1y, cp2x, cp2y;

        if (direction === 'down') {
            cp1x = x1;
            cp1y = y1 + controlOffset;
            cp2x = x2;
            cp2y = y2 - controlOffset;
        } else {
            cp1x = x1;
            cp1y = y1 - controlOffset;
            cp2x = x2;
            cp2y = y2 + controlOffset;
        }

        return `M ${x1} ${y1} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${x2} ${y2}`;
    }

    /**
     * Node Selection
     */
    selectNode(nodeId, addToSelection = false) {
        if (!addToSelection) {
            this.clearSelection();
        }
        this.selectedNodes.add(nodeId);
        this.updateNodeSelectionUI();
        this.updateToolbar();

        if (this.options.onSelectionChange) {
            this.options.onSelectionChange(Array.from(this.selectedNodes));
        }
    }

    deselectNode(nodeId) {
        this.selectedNodes.delete(nodeId);
        this.updateNodeSelectionUI();
        this.updateToolbar();

        if (this.options.onSelectionChange) {
            this.options.onSelectionChange(Array.from(this.selectedNodes));
        }
    }

    clearSelection() {
        this.selectedNodes.clear();
        this.selectedConnection = null;
        this.updateNodeSelectionUI();
        this.updateToolbar();

        if (this.options.onSelectionChange) {
            this.options.onSelectionChange([]);
        }
    }

    selectAll() {
        this.nodes.forEach((_, id) => this.selectedNodes.add(id));
        this.updateNodeSelectionUI();
        this.updateToolbar();

        if (this.options.onSelectionChange) {
            this.options.onSelectionChange(Array.from(this.selectedNodes));
        }
    }

    selectConnection(index) {
        this.clearSelection();
        this.selectedConnection = index;
        this.render();
        this.updateToolbar();
    }

    updateNodeSelectionUI() {
        this.nodesLayer.querySelectorAll('.wf-node').forEach(el => {
            const nodeId = el.dataset.nodeId;
            if (this.selectedNodes.has(nodeId)) {
                el.classList.add('selected');
            } else {
                el.classList.remove('selected');
            }
        });
    }

    updateToolbar() {
        if (this.deleteBtn) {
            this.deleteBtn.disabled = this.selectedNodes.size === 0 && this.selectedConnection === null;
        }
    }

    /**
     * Delete Operations
     */
    deleteSelected() {
        if (!this.options.editable) return;

        // Delete selected connection
        if (this.selectedConnection !== null) {
            this.removeConnection(this.selectedConnection);
            this.selectedConnection = null;
            this.render();
            return;
        }

        // Delete selected nodes
        if (this.selectedNodes.size > 0) {
            this.selectedNodes.forEach(id => {
                this.removeNode(id);
            });
            this.selectedNodes.clear();
            this.render();
        }

        this.updateToolbar();
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

    fitToView() {
        if (this.nodes.size === 0) return;

        const rect = this.canvas.getBoundingClientRect();
        const bounds = this.calculateBounds();
        const padding = 100;

        const scaleX = (rect.width - padding * 2) / bounds.width;
        const scaleY = (rect.height - padding * 2) / bounds.height;
        const scale = Math.min(scaleX, scaleY, 1.5);

        this.transform = {
            x: rect.width / 2 - bounds.centerX * scale,
            y: rect.height / 2 - bounds.centerY * scale,
            scale: Math.max(this.options.minScale, scale)
        };

        this.applyTransform();
        this.updateZoomDisplay();
        this.updateMinimap();
    }

    resetView() {
        const rect = this.canvas.getBoundingClientRect();
        this.transform = {
            x: rect.width / 2,
            y: 100,
            scale: 1
        };
        this.applyTransform();
        this.updateZoomDisplay();
        this.updateMinimap();
    }

    applyTransform() {
        const { x, y, scale } = this.transform;
        this.transformGroup.setAttribute('transform', `translate(${x}, ${y}) scale(${scale})`);
    }

    updateZoomDisplay() {
        this.zoomDisplay.textContent = `${Math.round(this.transform.scale * 100)}%`;
    }

    /**
     * Calculate node bounds
     */
    calculateBounds() {
        let minX = Infinity, maxX = -Infinity;
        let minY = Infinity, maxY = -Infinity;

        this.nodes.forEach(node => {
            minX = Math.min(minX, node.x);
            maxX = Math.max(maxX, node.x + this.options.nodeWidth);
            minY = Math.min(minY, node.y);
            maxY = Math.max(maxY, node.y + this.options.nodeHeight);
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
     * Fullscreen
     */
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            this.wrapper.requestFullscreen?.() || this.wrapper.webkitRequestFullscreen?.();
        } else {
            document.exitFullscreen?.() || document.webkitExitFullscreen?.();
        }
    }

    onFullscreenChange() {
        if (document.fullscreenElement === this.wrapper) {
            this.wrapper.classList.add('fullscreen');
        } else {
            this.wrapper.classList.remove('fullscreen');
        }
        setTimeout(() => this.fitToView(), 100);
    }

    /**
     * Context Menu
     */
    showContextMenu(x, y, type) {
        this.contextMenu.style.display = 'block';
        this.contextMenu.style.left = `${x}px`;
        this.contextMenu.style.top = `${y}px`;
    }

    hideContextMenu() {
        this.contextMenu.style.display = 'none';
    }

    /**
     * Modal
     */
    showNodeDetail(node) {
        const accentColor = node.color || '#6366f1';
        const data = node.data || {};

        // กำหนด label ภาษาไทยสำหรับ fields
        const fieldLabels = {
            'pv': 'คะแนน PV',
            'total_pv': 'PV รวม',
            'monthly_pv': 'PV เดือนนี้',
            'referrals': 'แนะนำตรง',
            'direct_referrals': 'แนะนำตรง',
            'total_direct_referrals': 'แนะนำตรง',
            'team': 'ทีมงาน',
            'total_team_members': 'ทีมงานทั้งหมด',
            'status': 'สถานะ',
            'retention_status': 'สถานะ',
            'rank': 'ระดับ',
            'rank_name': 'ระดับ',
            'email': 'อีเมล',
            'phone': 'โทรศัพท์',
            'sponsor': 'สปอนเซอร์',
            'sponsor_name': 'สปอนเซอร์',
            'joined_at': 'วันที่สมัคร',
            'created_at': 'วันที่สมัคร',
            'left_pv': 'PV ซ้าย',
            'right_pv': 'PV ขวา',
            'member_code': 'รหัสสมาชิก'
        };

        // กำหนด icon สำหรับแต่ละ field
        const fieldIcons = {
            'pv': '⭐',
            'total_pv': '⭐',
            'monthly_pv': '📊',
            'referrals': '👥',
            'direct_referrals': '👥',
            'team': '🏢',
            'total_team_members': '🏢',
            'status': '🔖',
            'retention_status': '🔖',
            'rank': '🏆',
            'rank_name': '🏆',
            'email': '📧',
            'phone': '📱',
            'sponsor': '👤',
            'sponsor_name': '👤',
            'joined_at': '📅',
            'created_at': '📅',
            'left_pv': '◀️',
            'right_pv': '▶️',
            'member_code': '🔢'
        };

        // กำหนดสถานะภาษาไทย
        const statusLabels = {
            'active': '✅ แอคทีฟ',
            'grace_period': '⏳ ช่วงผ่อนผัน',
            'inactive': '❌ ไม่แอคทีฟ',
            'suspended': '🚫 ถูกระงับ'
        };

        // เลือก fields ที่สำคัญมาแสดง
        const importantFields = ['member_code', 'pv', 'total_pv', 'monthly_pv', 'referrals', 'direct_referrals', 'total_direct_referrals', 'team', 'total_team_members', 'rank', 'rank_name', 'left_pv', 'right_pv', 'status', 'retention_status', 'sponsor_name', 'email', 'phone', 'joined_at', 'created_at'];

        const displayData = {};
        importantFields.forEach(key => {
            if (data[key] !== undefined && data[key] !== null && data[key] !== '') {
                displayData[key] = data[key];
            }
        });

        this.modalContent.innerHTML = `
            <div>
                <div style="background: linear-gradient(135deg, ${accentColor}, ${accentColor}cc); padding: 24px; color: white; position: relative;">
                    <button onclick="this.closest('.wf-modal').classList.remove('show')" style="
                        position: absolute; top: 16px; right: 16px; width: 36px; height: 36px;
                        border: none; background: rgba(255,255,255,0.2); border-radius: 10px;
                        color: white; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center;
                        transition: background 0.2s;
                    " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">×</button>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px;">
                            👤
                        </div>
                        <div>
                            <div style="font-size: 22px; font-weight: 700;">${this.escapeHtml(node.label || node.name || 'สมาชิก')}</div>
                            <div style="opacity: 0.9; font-size: 14px; margin-top: 4px;">${this.escapeHtml(node.subtitle || data.member_code || '')}</div>
                            ${data.rank_name ? `<div style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-top: 8px; display: inline-block;">${this.escapeHtml(data.rank_name)}</div>` : ''}
                        </div>
                    </div>
                </div>
                <div style="padding: 24px; max-height: 400px; overflow-y: auto;">
                    ${node.description ? `<p style="color: #64748b; margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; border-left: 4px solid ${accentColor};">${this.escapeHtml(node.description)}</p>` : ''}

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        ${Object.entries(displayData).map(([key, value]) => {
                            const label = fieldLabels[key] || key;
                            const icon = fieldIcons[key] || '📌';
                            let displayValue = value;

                            // จัดรูปแบบค่าตามประเภท
                            if (key === 'status' || key === 'retention_status') {
                                displayValue = statusLabels[value] || value;
                            } else if (typeof value === 'number') {
                                displayValue = this.formatNumber(value);
                            } else if (key.includes('_at') && value) {
                                try {
                                    const date = new Date(value);
                                    if (!isNaN(date)) {
                                        displayValue = date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
                                    }
                                } catch(e) {}
                            }

                            return `
                                <div style="background: #f8fafc; padding: 14px; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; margin-bottom: 4px;">
                                        <span>${icon}</span>
                                        <span>${this.escapeHtml(label)}</span>
                                    </div>
                                    <div style="font-size: 16px; font-weight: 600; color: #1e293b;">${this.escapeHtml(String(displayValue))}</div>
                                </div>
                            `;
                        }).join('')}
                    </div>

                    ${data.id ? `
                        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                            <a href="/admin/mlm/members/${data.id}" style="
                                display: flex; align-items: center; justify-content: center; gap: 8px;
                                padding: 12px 20px; background: linear-gradient(135deg, ${accentColor}, ${accentColor}dd);
                                color: white; border-radius: 12px; text-decoration: none; font-weight: 600;
                                transition: transform 0.2s, box-shadow 0.2s;
                            " onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(99,102,241,0.3)'" onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                ดูรายละเอียดเพิ่มเติม
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
     * Loading
     */
    showLoading() {
        this.loading.classList.remove('hidden');
    }

    hideLoading() {
        this.loading.classList.add('hidden');
    }

    /**
     * Update Minimap
     */
    updateMinimap() {
        if (!this.minimapCanvas || this.nodes.size === 0) return;

        const ctx = this.minimapCanvas.getContext('2d');
        const rect = this.canvas.getBoundingClientRect();

        // Clear
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, 160, 100);

        // Calculate bounds
        const bounds = this.calculateBounds();
        const padding = 10;
        const scale = Math.min(
            (160 - padding * 2) / bounds.width,
            (100 - padding * 2) / bounds.height
        ) * 0.8;

        // Draw nodes
        ctx.fillStyle = '#6366f1';
        this.nodes.forEach(node => {
            const x = (node.x - bounds.minX) * scale + padding;
            const y = (node.y - bounds.minY) * scale + padding;
            ctx.fillRect(x, y, 8, 4);
        });

        // Draw connections
        ctx.strokeStyle = '#94a3b8';
        ctx.lineWidth = 1;
        this.connections.forEach(conn => {
            const fromNode = this.nodes.get(conn.from);
            const toNode = this.nodes.get(conn.to);
            if (fromNode && toNode) {
                const x1 = (fromNode.x + this.options.nodeWidth / 2 - bounds.minX) * scale + padding;
                const y1 = (fromNode.y + this.options.nodeHeight - bounds.minY) * scale + padding;
                const x2 = (toNode.x + this.options.nodeWidth / 2 - bounds.minX) * scale + padding;
                const y2 = (toNode.y - bounds.minY) * scale + padding;
                ctx.beginPath();
                ctx.moveTo(x1, y1);
                ctx.lineTo(x2, y2);
                ctx.stroke();
            }
        });

        // Update viewport indicator
        const vpScale = scale / this.transform.scale;
        const vpX = (-this.transform.x / this.transform.scale - bounds.minX) * scale + padding;
        const vpY = (-this.transform.y / this.transform.scale - bounds.minY) * scale + padding + 28;
        const vpWidth = rect.width * vpScale;
        const vpHeight = rect.height * vpScale;

        this.minimapViewport.style.left = `${Math.max(0, vpX)}px`;
        this.minimapViewport.style.top = `${Math.max(28, vpY)}px`;
        this.minimapViewport.style.width = `${Math.min(vpWidth, 160)}px`;
        this.minimapViewport.style.height = `${Math.min(vpHeight, 100)}px`;
    }

    /**
     * Utility Functions
     */
    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    formatNumber(num) {
        return Number(num || 0).toLocaleString('th-TH');
    }

    /**
     * Public API - Data Management
     */

    /**
     * เพิ่ม node ใหม่
     * @param {Object} nodeData - ข้อมูล node
     */
    addNode(nodeData) {
        const id = nodeData.id || `node_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const node = {
            id,
            x: nodeData.x ?? 0,
            y: nodeData.y ?? 0,
            label: nodeData.label || nodeData.name || 'Node',
            subtitle: nodeData.subtitle || '',
            description: nodeData.description || '',
            color: nodeData.color || '#6366f1',
            icon: nodeData.icon || null,
            inputs: nodeData.inputs ?? 1,
            outputs: nodeData.outputs ?? 1,
            data: nodeData.data || {},
            ...nodeData
        };

        // Snap to grid
        if (this.options.snapToGrid) {
            node.x = Math.round(node.x / this.options.gridSize) * this.options.gridSize;
            node.y = Math.round(node.y / this.options.gridSize) * this.options.gridSize;
        }

        this.nodes.set(id, node);
        this.render();
        this.updateStats();
        return node;
    }

    /**
     * อัพเดท node
     */
    updateNode(id, updates) {
        const node = this.nodes.get(id);
        if (node) {
            Object.assign(node, updates);
            this.render();
        }
        return node;
    }

    /**
     * ลบ node
     */
    removeNode(id) {
        if (this.nodes.has(id)) {
            // Remove connections involving this node
            this.connections = this.connections.filter(
                conn => conn.from !== id && conn.to !== id
            );
            this.nodes.delete(id);
            this.render();
            this.updateStats();

            if (this.options.onNodeDelete) {
                this.options.onNodeDelete(id);
            }
        }
    }

    /**
     * เพิ่ม connection
     */
    addConnection(fromNodeId, fromPort, toNodeId, toPort) {
        // Check if connection already exists
        const exists = this.connections.some(
            c => c.from === fromNodeId && c.to === toNodeId &&
                 c.fromPort === fromPort && c.toPort === toPort
        );

        if (!exists) {
            const connection = {
                from: fromNodeId,
                fromPort: fromPort || 0,
                to: toNodeId,
                toPort: toPort || 0
            };
            this.connections.push(connection);
            this.render();
            this.updateStats();

            if (this.options.onConnectionCreate) {
                this.options.onConnectionCreate(connection);
            }
        }
    }

    /**
     * ลบ connection
     */
    removeConnection(index) {
        if (index >= 0 && index < this.connections.length) {
            const connection = this.connections.splice(index, 1)[0];
            this.updateStats();

            if (this.options.onConnectionDelete) {
                this.options.onConnectionDelete(connection);
            }
        }
    }

    /**
     * โหลดข้อมูลทั้งหมด
     */
    setData(data) {
        this.showLoading();
        this.nodes.clear();
        this.connections = [];

        // Add nodes
        (data.nodes || []).forEach(node => {
            this.addNode(node);
        });

        // Add connections
        (data.connections || data.edges || []).forEach(conn => {
            this.connections.push({
                from: conn.from || conn.source,
                fromPort: conn.fromPort || conn.sourcePort || 0,
                to: conn.to || conn.target,
                toPort: conn.toPort || conn.targetPort || 0
            });
        });

        this.render();
        this.hideLoading();
        this.updateStats();
        this.fitToView();
    }

    /**
     * โหลดจากข้อมูล tree (ผังสายงาน)
     */
    loadTreeData(rootNode, options = {}) {
        this.showLoading();
        this.nodes.clear();
        this.connections = [];

        const spacing = options.horizontalSpacing || 280;
        const levelHeight = options.verticalSpacing || 150;

        // Recursive function to process tree
        const processNode = (node, x, y, level, parentId = null) => {
            const id = node.id || `node_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;

            this.nodes.set(id, {
                id,
                x,
                y,
                label: node.name || node.label || 'Unknown',
                subtitle: node.member_code || node.subtitle || '',
                color: this.getStatusColor(node.status || node.retention_status),
                inputs: parentId ? 1 : 0,
                outputs: (node.children?.length || 0) > 0 ? 1 : 0,
                data: {
                    pv: node.total_pv || node.monthly_pv || 0,
                    referrals: node.direct_referrals || node.total_direct_referrals || 0,
                    team: node.total_team_members || 0,
                    status: node.retention_status || node.status || 'active',
                    ...node
                }
            });

            // Add connection to parent
            if (parentId) {
                this.connections.push({
                    from: parentId,
                    fromPort: 0,
                    to: id,
                    toPort: 0
                });
            }

            // Process children
            const children = node.children || [];
            if (children.length > 0) {
                const totalWidth = (children.length - 1) * spacing;
                const startX = x - totalWidth / 2;

                children.forEach((child, index) => {
                    const childX = startX + index * spacing;
                    processNode(child, childX, y + levelHeight, level + 1, id);
                });
            }
        };

        processNode(rootNode, 0, 0, 0);

        this.render();
        this.hideLoading();
        this.updateStats();
        this.fitToView();
    }

    getStatusColor(status) {
        switch (status) {
            case 'active': return '#10b981';
            case 'grace_period': return '#f59e0b';
            case 'inactive': return '#6b7280';
            case 'suspended': return '#ef4444';
            default: return '#6366f1';
        }
    }

    /**
     * ส่งออกข้อมูล
     */
    getData() {
        return {
            nodes: Array.from(this.nodes.values()),
            connections: this.connections
        };
    }

    /**
     * Update stats display
     */
    updateStats() {
        this.nodeCountDisplay.textContent = this.nodes.size;
        this.connectionCountDisplay.textContent = this.connections.length;
    }

    /**
     * Render ทั้งหมด
     */
    render() {
        this.renderConnections();
        this.renderNodes();
        this.updateMinimap();
    }

    /**
     * Render connections
     */
    renderConnections() {
        this.connectionsLayer.innerHTML = '';

        this.connections.forEach((conn, index) => {
            const fromNode = this.nodes.get(conn.from);
            const toNode = this.nodes.get(conn.to);
            if (!fromNode || !toNode) return;

            // Calculate port positions
            const x1 = fromNode.x + this.options.nodeWidth / 2;
            const y1 = fromNode.y + this.options.nodeHeight;
            const x2 = toNode.x + this.options.nodeWidth / 2;
            const y2 = toNode.y;

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', this.createBezierPath(x1, y1, x2, y2, 'down'));
            path.setAttribute('class', `wf-connection ${this.options.animateConnections ? 'wf-connection-animated' : ''} ${this.selectedConnection === index ? 'selected' : ''}`);
            path.setAttribute('stroke', 'url(#wf-gradient-default)');
            path.dataset.connectionIndex = index;

            this.connectionsLayer.appendChild(path);
        });
    }

    /**
     * Render nodes
     */
    renderNodes() {
        this.nodesLayer.innerHTML = '';

        this.nodes.forEach((node, id) => {
            const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            g.setAttribute('class', `wf-node ${this.selectedNodes.has(id) ? 'selected' : ''}`);
            g.setAttribute('transform', `translate(${node.x}, ${node.y})`);
            g.dataset.nodeId = id;

            const nodeWidth = this.options.nodeWidth;
            const nodeHeight = this.options.nodeHeight;
            const portRadius = this.options.portRadius;
            const accentColor = node.color || '#6366f1';

            g.innerHTML = `
                <!-- Node Body -->
                <rect class="wf-node-body" x="0" y="0" width="${nodeWidth}" height="${nodeHeight}"
                    rx="12" fill="white" stroke="${accentColor}" stroke-width="2"/>

                <!-- Color Accent -->
                <rect x="0" y="0" width="6" height="${nodeHeight}" rx="3" fill="${accentColor}"/>

                <!-- Content -->
                <foreignObject x="12" y="8" width="${nodeWidth - 24}" height="${nodeHeight - 16}">
                    <div xmlns="http://www.w3.org/1999/xhtml" style="
                        font-family: 'Inter', system-ui, sans-serif;
                        height: 100%;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                    ">
                        <div style="font-size: 14px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${this.escapeHtml(node.label)}
                        </div>
                        ${node.subtitle ? `
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                ${this.escapeHtml(node.subtitle)}
                            </div>
                        ` : ''}
                        ${node.data?.pv !== undefined ? `
                            <div style="font-size: 10px; color: #a855f7; margin-top: 4px; font-weight: 600;">
                                ${this.formatNumber(node.data.pv)} PV
                            </div>
                        ` : ''}
                    </div>
                </foreignObject>

                <!-- Input Port (top) -->
                ${node.inputs > 0 ? `
                    <circle class="wf-port" cx="${nodeWidth / 2}" cy="0" r="${portRadius}"
                        fill="white" stroke="${accentColor}" stroke-width="2"
                        data-port-type="input" data-port-index="0"/>
                ` : ''}

                <!-- Output Port (bottom) -->
                ${node.outputs > 0 ? `
                    <circle class="wf-port" cx="${nodeWidth / 2}" cy="${nodeHeight}" r="${portRadius}"
                        fill="${accentColor}" stroke="white" stroke-width="2"
                        data-port-type="output" data-port-index="0"/>
                ` : ''}
            `;

            this.nodesLayer.appendChild(g);
        });
    }

    /**
     * Destroy instance
     */
    destroy() {
        this.container.innerHTML = '';
        this.nodes.clear();
        this.connections = [];
    }
}

// Export to window
window.WorkflowDiagram = WorkflowDiagram;
