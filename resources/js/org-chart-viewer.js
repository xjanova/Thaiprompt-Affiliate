/**
 * Organization Chart Viewer
 * แสดงผังสายงาน MLM แบบ Interactive รองรับทั้ง Desktop และ Mobile
 *
 * รองรับ:
 * - Touch events (pinch-to-zoom, pan)
 * - Mouse events (drag, wheel zoom)
 * - Responsive design
 * - Dark mode
 *
 * @author TP-Affiliate Team
 * @version 3.0.0
 */

class OrgChartViewer {
    /**
     * สร้าง OrgChartViewer instance
     *
     * @param {string|HTMLElement} container - Container element หรือ ID
     * @param {Object} options - ตัวเลือกการตั้งค่า
     */
    constructor(container, options = {}) {
        // หา container element
        this.container = typeof container === 'string'
            ? document.getElementById(container)
            : container;

        if (!this.container) {
            console.error('OrgChartViewer: Container not found');
            return;
        }

        // ตั้งค่าเริ่มต้น
        this.options = {
            treeType: options.treeType || 'unilevel',
            maxDepth: options.maxDepth || 5,
            nodeWidth: options.nodeWidth || 200,
            nodeHeight: options.nodeHeight || 120,
            horizontalSpacing: options.horizontalSpacing || 40,
            verticalSpacing: options.verticalSpacing || 100,
            minScale: options.minScale || 0.2,
            maxScale: options.maxScale || 3,
            animationDuration: options.animationDuration || 300,
            showTooltip: options.showTooltip !== false,
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
     * สร้าง DOM structure
     */
    createDOM() {
        this.container.innerHTML = `
            <div class="org-chart-wrapper relative w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 overflow-hidden rounded-xl" style="min-height: 500px;">
                <!-- Loading Overlay -->
                <div class="org-chart-loading absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm z-50 transition-opacity duration-300">
                    <div class="text-center">
                        <div class="relative w-16 h-16 mx-auto mb-4">
                            <div class="absolute inset-0 border-4 border-purple-200 dark:border-purple-900 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-transparent border-t-purple-600 dark:border-t-purple-400 rounded-full animate-spin"></div>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 font-medium">กำลังโหลดผังสายงาน...</p>
                    </div>
                </div>

                <!-- Canvas Container -->
                <div class="org-chart-canvas absolute inset-0 cursor-grab active:cursor-grabbing touch-none">
                    <svg class="org-chart-svg w-full h-full" style="touch-action: none;">
                        <defs>
                            <!-- กำหนด Gradients และ Filters -->
                            <linearGradient id="nodeGradientActive" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="nodeGradientInactive" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#9ca3af;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#6b7280;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="nodeGradientSuspended" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#ef4444;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#dc2626;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="nodeGradientGrace" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#d97706;stop-opacity:1" />
                            </linearGradient>
                            <filter id="nodeShadow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="4" stdDeviation="6" flood-opacity="0.15"/>
                            </filter>
                            <filter id="nodeShadowHover" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="8" stdDeviation="12" flood-opacity="0.25"/>
                            </filter>
                        </defs>
                        <g class="org-chart-main-group"></g>
                    </svg>
                </div>

                <!-- Zoom Controls -->
                <div class="org-chart-controls absolute top-4 right-4 flex flex-col gap-2 z-10">
                    <button class="org-chart-zoom-in p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 transition-all duration-200 active:scale-95" title="ซูมเข้า">
                        <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </button>
                    <div class="org-chart-zoom-level text-center py-2 px-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        100%
                    </div>
                    <button class="org-chart-zoom-out p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 transition-all duration-200 active:scale-95" title="ซูมออก">
                        <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"></path>
                        </svg>
                    </button>
                    <button class="org-chart-reset p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 transition-all duration-200 active:scale-95" title="รีเซ็ต">
                        <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                    <button class="org-chart-fit p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 transition-all duration-200 active:scale-95" title="พอดีหน้าจอ">
                        <svg class="w-5 h-5 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                        </svg>
                    </button>
                </div>

                <!-- Instructions (Mobile-friendly) -->
                <div class="org-chart-instructions absolute bottom-4 left-4 right-4 md:right-auto md:max-w-xs bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                    <p class="font-bold text-sm text-purple-600 dark:text-purple-400 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        การใช้งาน
                    </p>
                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                        <li class="flex items-center gap-2 md:hidden">
                            <span class="text-blue-500">👆</span> ลากนิ้วเพื่อเลื่อนดู
                        </li>
                        <li class="flex items-center gap-2 md:hidden">
                            <span class="text-purple-500">🤏</span> บีบนิ้วเพื่อซูม
                        </li>
                        <li class="flex items-center gap-2 hidden md:flex">
                            <span class="text-blue-500">🖱️</span> ลากเพื่อเลื่อนดู
                        </li>
                        <li class="flex items-center gap-2 hidden md:flex">
                            <span class="text-purple-500">⚙️</span> Scroll เพื่อซูม
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-pink-500">👆</span> แตะ/คลิกโหนดเพื่อดูรายละเอียด
                        </li>
                    </ul>
                </div>

                <!-- Stats Panel -->
                <div class="org-chart-stats absolute top-4 left-4 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700 z-10 hidden md:block">
                    <p class="font-bold text-sm text-gray-900 dark:text-white mb-2">📊 สถิติ</p>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400">จำนวนโหนด:</span>
                            <span class="font-bold text-gray-900 dark:text-white org-chart-node-count">0</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500 dark:text-gray-400">ความลึก:</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400 org-chart-max-depth">0</span>
                        </div>
                    </div>
                </div>

                <!-- Node Detail Modal -->
                <div class="org-chart-modal fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 opacity-0 pointer-events-none transition-opacity duration-300">
                    <div class="org-chart-modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full max-h-[80vh] overflow-y-auto transform scale-95 transition-transform duration-300">
                        <!-- Modal content จะถูกเติมโดย showNodeDetail() -->
                    </div>
                </div>
            </div>
        `;

        // เก็บ references
        this.wrapper = this.container.querySelector('.org-chart-wrapper');
        this.loading = this.container.querySelector('.org-chart-loading');
        this.canvas = this.container.querySelector('.org-chart-canvas');
        this.svg = this.container.querySelector('.org-chart-svg');
        this.mainGroup = this.container.querySelector('.org-chart-main-group');
        this.zoomLevelDisplay = this.container.querySelector('.org-chart-zoom-level');
        this.nodeCountDisplay = this.container.querySelector('.org-chart-node-count');
        this.maxDepthDisplay = this.container.querySelector('.org-chart-max-depth');
        this.modal = this.container.querySelector('.org-chart-modal');
        this.modalContent = this.container.querySelector('.org-chart-modal-content');
    }

    /**
     * Attach event listeners สำหรับทั้ง mouse และ touch
     */
    attachEventListeners() {
        // Zoom buttons
        this.container.querySelector('.org-chart-zoom-in').addEventListener('click', () => this.zoomIn());
        this.container.querySelector('.org-chart-zoom-out').addEventListener('click', () => this.zoomOut());
        this.container.querySelector('.org-chart-reset').addEventListener('click', () => this.resetView());
        this.container.querySelector('.org-chart-fit').addEventListener('click', () => this.fitToScreen());

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

        // Modal close
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.hideModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideModal();
            }
        });
    }

    /**
     * Mouse drag start
     */
    onDragStart(e) {
        if (e.target.closest('.org-chart-node')) return;

        this.isDragging = true;
        this.startDragPoint = {
            x: e.clientX - this.transform.x,
            y: e.clientY - this.transform.y
        };
    }

    /**
     * Mouse drag move
     */
    onDragMove(e) {
        if (!this.isDragging) return;

        this.transform.x = e.clientX - this.startDragPoint.x;
        this.transform.y = e.clientY - this.startDragPoint.y;
        this.applyTransform();
    }

    /**
     * Mouse drag end
     */
    onDragEnd() {
        this.isDragging = false;
    }

    /**
     * Mouse wheel zoom
     */
    onWheel(e) {
        e.preventDefault();

        const rect = this.canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        this.zoomAt(mouseX, mouseY, delta);
    }

    /**
     * Touch start - จัดการ single touch และ multi-touch
     */
    onTouchStart(e) {
        if (e.touches.length === 1) {
            // Single touch - drag
            const touch = e.touches[0];
            this.isDragging = true;
            this.startDragPoint = {
                x: touch.clientX - this.transform.x,
                y: touch.clientY - this.transform.y
            };
        } else if (e.touches.length === 2) {
            // Two finger - pinch zoom
            e.preventDefault();
            this.isDragging = false;
            this.lastTouchDistance = this.getTouchDistance(e.touches);
            this.lastTouchCenter = this.getTouchCenter(e.touches);
        }
    }

    /**
     * Touch move - จัดการ pan และ pinch-to-zoom
     */
    onTouchMove(e) {
        if (e.touches.length === 1 && this.isDragging) {
            // Single touch - pan
            const touch = e.touches[0];
            this.transform.x = touch.clientX - this.startDragPoint.x;
            this.transform.y = touch.clientY - this.startDragPoint.y;
            this.applyTransform();
        } else if (e.touches.length === 2 && this.lastTouchDistance !== null) {
            // Two finger - pinch zoom
            e.preventDefault();

            const distance = this.getTouchDistance(e.touches);
            const center = this.getTouchCenter(e.touches);
            const rect = this.canvas.getBoundingClientRect();

            // คำนวณ scale factor
            const scaleFactor = distance / this.lastTouchDistance;

            // Zoom at center point
            const centerX = center.x - rect.left;
            const centerY = center.y - rect.top;
            this.zoomAt(centerX, centerY, scaleFactor);

            // Pan based on center movement
            if (this.lastTouchCenter) {
                this.transform.x += center.x - this.lastTouchCenter.x;
                this.transform.y += center.y - this.lastTouchCenter.y;
                this.applyTransform();
            }

            this.lastTouchDistance = distance;
            this.lastTouchCenter = center;
        }
    }

    /**
     * Touch end
     */
    onTouchEnd(e) {
        if (e.touches.length < 2) {
            this.lastTouchDistance = null;
            this.lastTouchCenter = null;
        }
        if (e.touches.length === 0) {
            this.isDragging = false;
        }
    }

    /**
     * คำนวณระยะห่างระหว่าง 2 นิ้ว
     */
    getTouchDistance(touches) {
        const dx = touches[1].clientX - touches[0].clientX;
        const dy = touches[1].clientY - touches[0].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }

    /**
     * คำนวณจุดกึ่งกลางระหว่าง 2 นิ้ว
     */
    getTouchCenter(touches) {
        return {
            x: (touches[0].clientX + touches[1].clientX) / 2,
            y: (touches[0].clientY + touches[1].clientY) / 2
        };
    }

    /**
     * Zoom at specific point
     */
    zoomAt(x, y, factor) {
        const oldScale = this.transform.scale;
        const newScale = Math.max(
            this.options.minScale,
            Math.min(this.options.maxScale, oldScale * factor)
        );

        if (newScale === oldScale) return;

        // Adjust position to zoom at point
        const scaleRatio = newScale / oldScale;
        this.transform.x = x - (x - this.transform.x) * scaleRatio;
        this.transform.y = y - (y - this.transform.y) * scaleRatio;
        this.transform.scale = newScale;

        this.applyTransform();
        this.updateZoomDisplay();
    }

    /**
     * Zoom in
     */
    zoomIn() {
        const rect = this.canvas.getBoundingClientRect();
        this.zoomAt(rect.width / 2, rect.height / 2, 1.3);
    }

    /**
     * Zoom out
     */
    zoomOut() {
        const rect = this.canvas.getBoundingClientRect();
        this.zoomAt(rect.width / 2, rect.height / 2, 0.7);
    }

    /**
     * Reset view to initial state
     */
    resetView() {
        const rect = this.canvas.getBoundingClientRect();
        this.transform = {
            x: rect.width / 2 - this.options.nodeWidth / 2,
            y: 50,
            scale: 1
        };
        this.applyTransform();
        this.updateZoomDisplay();
    }

    /**
     * Fit tree to screen
     */
    fitToScreen() {
        if (!this.data) return;

        const rect = this.canvas.getBoundingClientRect();
        const treeWidth = this.calculateTreeWidth(this.data);
        const treeHeight = this.maxDepthReached * this.options.verticalSpacing + this.options.nodeHeight;

        const scaleX = (rect.width - 100) / Math.max(treeWidth, 1);
        const scaleY = (rect.height - 100) / Math.max(treeHeight, 1);
        const scale = Math.min(scaleX, scaleY, 1);

        this.transform = {
            x: (rect.width - treeWidth * scale) / 2,
            y: 50,
            scale: Math.max(this.options.minScale, scale)
        };
        this.applyTransform();
        this.updateZoomDisplay();
    }

    /**
     * Apply transform to main group
     */
    applyTransform() {
        const { x, y, scale } = this.transform;
        this.mainGroup.setAttribute('transform', `translate(${x}, ${y}) scale(${scale})`);
    }

    /**
     * Update zoom level display
     */
    updateZoomDisplay() {
        this.zoomLevelDisplay.textContent = `${Math.round(this.transform.scale * 100)}%`;
    }

    /**
     * Show loading overlay
     */
    showLoading() {
        this.loading.style.opacity = '1';
        this.loading.style.pointerEvents = 'auto';
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        this.loading.style.opacity = '0';
        this.loading.style.pointerEvents = 'none';
    }

    /**
     * โหลดข้อมูลและ render tree
     *
     * @param {Object} data - ข้อมูล tree
     */
    setData(data) {
        this.data = data;
        this.nodeCount = 0;
        this.maxDepthReached = 0;

        this.render();
        this.hideLoading();
        this.resetView();
    }

    /**
     * Render tree
     */
    render() {
        if (!this.data) return;

        // Clear previous render
        this.mainGroup.innerHTML = '';

        // Render based on tree type
        if (this.options.treeType === 'binary') {
            this.renderBinaryTree(this.data, 0, 0, 0);
        } else {
            this.renderUnilevelTree(this.data, 0, 0, 0);
        }

        // Update stats
        if (this.nodeCountDisplay) {
            this.nodeCountDisplay.textContent = this.nodeCount;
        }
        if (this.maxDepthDisplay) {
            this.maxDepthDisplay.textContent = this.maxDepthReached;
        }
    }

    /**
     * Render Binary Tree recursively
     */
    renderBinaryTree(node, x, y, depth) {
        if (!node || depth > this.options.maxDepth) return;

        this.nodeCount++;
        this.maxDepthReached = Math.max(this.maxDepthReached, depth);

        // คำนวณ horizontal offset ตาม depth
        const baseOffset = 250;
        const offset = baseOffset / Math.pow(1.5, depth);

        const nodeY = y + (depth * this.options.verticalSpacing);
        const leftX = x - offset;
        const rightX = x + offset;
        const childY = y + ((depth + 1) * this.options.verticalSpacing);

        // Draw connections first (behind nodes)
        if (node.left) {
            this.drawConnection(x, nodeY + this.options.nodeHeight, leftX, childY, 'left');
            this.renderBinaryTree(node.left, leftX, y, depth + 1);
        }

        if (node.right) {
            this.drawConnection(x, nodeY + this.options.nodeHeight, rightX, childY, 'right');
            this.renderBinaryTree(node.right, rightX, y, depth + 1);
        }

        // Draw node
        this.drawNode(node, x, nodeY);
    }

    /**
     * Render Unilevel Tree recursively
     */
    renderUnilevelTree(node, x, y, depth) {
        if (!node || depth > this.options.maxDepth) return;

        this.nodeCount++;
        this.maxDepthReached = Math.max(this.maxDepthReached, depth);

        const nodeY = y + (depth * this.options.verticalSpacing);

        // Draw node
        this.drawNode(node, x, nodeY);

        // Draw children
        const children = node.children || [];
        if (children.length === 0) return;

        const totalWidth = (children.length - 1) * (this.options.nodeWidth + this.options.horizontalSpacing);
        const startX = x - totalWidth / 2;
        const childY = nodeY + this.options.nodeHeight;

        children.forEach((child, index) => {
            const childX = startX + index * (this.options.nodeWidth + this.options.horizontalSpacing);

            // Draw connection
            this.drawConnection(x, childY, childX, childY + this.options.verticalSpacing - this.options.nodeHeight);

            // Render child recursively
            this.renderUnilevelTree(child, childX, y, depth + 1);
        });
    }

    /**
     * Draw a node
     */
    drawNode(node, x, y) {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'org-chart-node');
        g.setAttribute('data-node-id', node.id);
        g.setAttribute('transform', `translate(${x - this.options.nodeWidth / 2}, ${y})`);
        g.style.cursor = 'pointer';

        // กำหนดสีตามสถานะ
        const status = node.retention_status || node.status || 'active';
        let gradientId, borderColor, statusText, statusBadgeBg, statusBadgeColor;

        switch (status) {
            case 'active':
                gradientId = 'url(#nodeGradientActive)';
                borderColor = '#10b981';
                statusText = '✓ Active';
                statusBadgeBg = '#dcfce7';
                statusBadgeColor = '#059669';
                break;
            case 'grace_period':
                gradientId = 'url(#nodeGradientGrace)';
                borderColor = '#f59e0b';
                statusText = '⚠️ Grace';
                statusBadgeBg = '#fef3c7';
                statusBadgeColor = '#d97706';
                break;
            case 'inactive':
                gradientId = 'url(#nodeGradientInactive)';
                borderColor = '#9ca3af';
                statusText = '❌ Inactive';
                statusBadgeBg = '#f3f4f6';
                statusBadgeColor = '#6b7280';
                break;
            case 'suspended':
                gradientId = 'url(#nodeGradientSuspended)';
                borderColor = '#ef4444';
                statusText = '🚫 Suspended';
                statusBadgeBg = '#fee2e2';
                statusBadgeColor = '#dc2626';
                break;
            default:
                gradientId = 'url(#nodeGradientActive)';
                borderColor = '#10b981';
                statusText = '✓ Active';
                statusBadgeBg = '#dcfce7';
                statusBadgeColor = '#059669';
        }

        // สร้าง node HTML ด้วย foreignObject
        g.innerHTML = `
            <foreignObject width="${this.options.nodeWidth}" height="${this.options.nodeHeight}">
                <div xmlns="http://www.w3.org/1999/xhtml" style="
                    width: 100%;
                    height: 100%;
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
                    overflow: hidden;
                    font-family: system-ui, -apple-system, sans-serif;
                    border: 3px solid ${borderColor};
                    transition: all 0.2s ease;
                " class="node-card">
                    <!-- Header -->
                    <div style="
                        background: ${gradientId.replace('url(#', 'linear-gradient(135deg, ').replace(')', ', #1f2937)')};
                        background: ${status === 'active' ? 'linear-gradient(135deg, #10b981, #059669)' : status === 'grace_period' ? 'linear-gradient(135deg, #f59e0b, #d97706)' : status === 'suspended' ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #9ca3af, #6b7280)'};
                        padding: 12px;
                        color: white;
                    ">
                        <div style="font-weight: 700; font-size: 13px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${this.escapeHtml(node.name || 'Unknown')}
                        </div>
                        <div style="font-size: 11px; opacity: 0.9;">
                            ${this.escapeHtml(node.member_code || '')}
                        </div>
                    </div>
                    <!-- Body -->
                    <div style="padding: 10px; font-size: 11px; background: white;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: #6b7280;">PV:</span>
                            <span style="font-weight: 700; color: #7c3aed;">${this.formatNumber(node.monthly_pv || node.total_pv || 0)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #6b7280;">Refs:</span>
                            <span style="font-weight: 700; color: #1f2937;">${node.direct_referrals || node.total_direct_referrals || 0}</span>
                        </div>
                        <div style="
                            background: ${statusBadgeBg};
                            color: ${statusBadgeColor};
                            text-align: center;
                            padding: 4px 8px;
                            border-radius: 8px;
                            font-size: 10px;
                            font-weight: 600;
                        ">
                            ${statusText}
                        </div>
                    </div>
                </div>
            </foreignObject>
        `;

        // Add hover effect
        g.addEventListener('mouseenter', () => {
            const card = g.querySelector('.node-card');
            if (card) {
                card.style.transform = 'scale(1.05)';
                card.style.boxShadow = '0 8px 30px rgba(0,0,0,0.2)';
            }
        });

        g.addEventListener('mouseleave', () => {
            const card = g.querySelector('.node-card');
            if (card) {
                card.style.transform = 'scale(1)';
                card.style.boxShadow = '0 4px 20px rgba(0,0,0,0.12)';
            }
        });

        // Add click handler
        g.addEventListener('click', (e) => {
            e.stopPropagation();
            this.showNodeDetail(node);
        });

        // Touch support
        g.addEventListener('touchend', (e) => {
            if (!this.isDragging && e.changedTouches.length === 1) {
                e.preventDefault();
                e.stopPropagation();
                this.showNodeDetail(node);
            }
        });

        this.mainGroup.appendChild(g);
    }

    /**
     * Draw connection line between nodes
     */
    drawConnection(x1, y1, x2, y2, leg = null) {
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

        // สร้าง bezier curve
        const midY = (y1 + y2) / 2;
        const d = `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`;

        path.setAttribute('d', d);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke-width', '2.5');
        path.setAttribute('stroke-linecap', 'round');

        // สีตาม leg
        if (leg === 'left') {
            path.setAttribute('stroke', '#10b981');
        } else if (leg === 'right') {
            path.setAttribute('stroke', '#f43f5e');
        } else {
            path.setAttribute('stroke', '#9ca3af');
        }

        // Insert at beginning so lines appear behind nodes
        this.mainGroup.insertBefore(path, this.mainGroup.firstChild);
    }

    /**
     * Show node detail modal
     */
    showNodeDetail(node) {
        const status = node.retention_status || node.status || 'active';
        let statusColor, statusBg, statusText;

        switch (status) {
            case 'active':
                statusColor = '#059669';
                statusBg = '#dcfce7';
                statusText = 'Active';
                break;
            case 'grace_period':
                statusColor = '#d97706';
                statusBg = '#fef3c7';
                statusText = 'Grace Period';
                break;
            case 'inactive':
                statusColor = '#6b7280';
                statusBg = '#f3f4f6';
                statusText = 'Inactive';
                break;
            case 'suspended':
                statusColor = '#dc2626';
                statusBg = '#fee2e2';
                statusText = 'Suspended';
                break;
            default:
                statusColor = '#059669';
                statusBg = '#dcfce7';
                statusText = 'Active';
        }

        this.modalContent.innerHTML = `
            <div class="p-6">
                <!-- Close Button -->
                <button class="modal-close absolute top-4 right-4 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- Header -->
                <div class="mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center text-white text-2xl font-bold mb-4">
                        ${this.escapeHtml((node.name || 'U').substring(0, 2).toUpperCase())}
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${this.escapeHtml(node.name || 'Unknown')}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400">
                        ${this.escapeHtml(node.member_code || '')}
                        ${node.email ? ` • ${this.escapeHtml(node.email)}` : ''}
                    </p>
                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold" style="background: ${statusBg}; color: ${statusColor};">
                        ${statusText}
                    </span>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-xl p-4">
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mb-1">TOTAL PV</p>
                        <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                            ${this.formatNumber(node.total_pv || 0)}
                        </p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-4">
                        <p class="text-xs text-purple-600 dark:text-purple-400 font-semibold mb-1">MONTHLY PV</p>
                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                            ${this.formatNumber(node.monthly_pv || 0)}
                        </p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4">
                        <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold mb-1">DIRECT REFS</p>
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                            ${node.direct_referrals || node.total_direct_referrals || 0}
                        </p>
                    </div>
                    <div class="bg-gradient-to-br from-pink-50 to-pink-100 dark:from-pink-900/20 dark:to-pink-800/20 rounded-xl p-4">
                        <p class="text-xs text-pink-600 dark:text-pink-400 font-semibold mb-1">TEAM SIZE</p>
                        <p class="text-2xl font-bold text-pink-700 dark:text-pink-300">
                            ${node.total_team_members || 0}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                    ${node.id ? `
                    <a href="/admin/mlm/members/${node.id}"
                       class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-center py-3 px-4 rounded-xl font-semibold transition-all shadow-lg hover:shadow-xl">
                        ดูรายละเอียดเพิ่มเติม
                    </a>
                    <a href="/admin/mlm/members/${node.id}/genealogy"
                       class="flex-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-center py-3 px-4 rounded-xl font-semibold transition-all">
                        ดูสายงานของสมาชิกนี้
                    </a>
                    ` : ''}
                </div>
            </div>
        `;

        // Attach close button handler
        this.modalContent.querySelector('.modal-close').addEventListener('click', () => this.hideModal());

        // Show modal with animation
        this.modal.style.opacity = '1';
        this.modal.style.pointerEvents = 'auto';
        this.modalContent.style.transform = 'scale(1)';

        // Call custom callback if provided
        if (this.options.onNodeClick) {
            this.options.onNodeClick(node);
        }
    }

    /**
     * Hide modal
     */
    hideModal() {
        this.modal.style.opacity = '0';
        this.modal.style.pointerEvents = 'none';
        this.modalContent.style.transform = 'scale(0.95)';
    }

    /**
     * Calculate tree width for fitting
     */
    calculateTreeWidth(node, depth = 0) {
        if (!node) return 0;

        if (this.options.treeType === 'binary') {
            const baseOffset = 250;
            const offset = baseOffset / Math.pow(1.5, depth);

            const leftWidth = node.left ? this.calculateTreeWidth(node.left, depth + 1) : 0;
            const rightWidth = node.right ? this.calculateTreeWidth(node.right, depth + 1) : 0;

            return Math.max(
                this.options.nodeWidth,
                offset * 2 + Math.max(leftWidth, rightWidth)
            );
        } else {
            const children = node.children || [];
            if (children.length === 0) return this.options.nodeWidth;

            const childrenWidth = children.reduce((sum, child) => {
                return sum + this.calculateTreeWidth(child, depth + 1);
            }, 0) + (children.length - 1) * this.options.horizontalSpacing;

            return Math.max(this.options.nodeWidth, childrenWidth);
        }
    }

    /**
     * Format number with commas
     */
    formatNumber(num) {
        return Number(num || 0).toLocaleString('th-TH');
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Set tree type and re-render
     */
    setTreeType(type) {
        this.options.treeType = type;
        if (this.data) {
            this.nodeCount = 0;
            this.maxDepthReached = 0;
            this.render();
            this.resetView();
        }
    }

    /**
     * Set max depth and re-render
     */
    setMaxDepth(depth) {
        this.options.maxDepth = depth;
        if (this.data) {
            this.nodeCount = 0;
            this.maxDepthReached = 0;
            this.render();
        }
    }

    /**
     * Destroy instance and cleanup
     */
    destroy() {
        this.container.innerHTML = '';
    }
}

// Export to global scope
window.OrgChartViewer = OrgChartViewer;
