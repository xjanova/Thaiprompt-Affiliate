/**
 * MlmGenealogyV3 - ผังสายงาน MLM ยกเครื่องใหม่
 *
 * คุณสมบัติ:
 * - Avatar วงกลม + ชื่อ + รหัส + rank badge
 * - สถานะ Active/Grace Period/Inactive พร้อม visual indicator
 * - Bézier curved connections สีตามสถานะ
 * - Pan/Drag + Zoom (scroll/pinch/buttons)
 * - Click node → detail panel
 * - Expand/Collapse nodes
 * - Search → highlight + scroll to node
 * - Fit-to-screen
 * - Mobile touch gestures
 * - Lazy load children via AJAX
 */
class MlmGenealogyV3 {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        if (!this.container) return;

        // ตั้งค่า
        this.options = {
            apiUrl: options.apiUrl || '/user/mlm/genealogy-data',
            treeType: options.treeType || 'unilevel',
            maxDepth: options.maxDepth || 5,
            nodeWidth: options.nodeWidth || 160,
            nodeHeight: options.nodeHeight || 100,
            horizontalGap: options.horizontalGap || 40,
            verticalGap: options.verticalGap || 80,
            onNodeClick: options.onNodeClick || null,
            ...options,
        };

        // State
        this.treeData = null;
        this.nodes = [];
        this.flatNodes = new Map();
        this.expandedNodes = new Set();
        this.selectedNode = null;
        this.searchResults = [];
        this.searchIndex = -1;

        // Transform (pan/zoom)
        this.transform = { x: 0, y: 0, scale: 1 };
        this.isDragging = false;
        this.dragStart = { x: 0, y: 0 };
        this.lastTransform = { x: 0, y: 0 };

        // Touch
        this.lastPinchDist = 0;
        this.lastTouchCenter = null;

        // สร้าง SVG
        this.init();
    }

    init() {
        // สร้าง wrapper
        this.container.innerHTML = '';
        this.container.style.position = 'relative';
        this.container.style.overflow = 'hidden';
        this.container.style.userSelect = 'none';
        this.container.style.touchAction = 'none';

        // SVG element
        this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svg.setAttribute('width', '100%');
        this.svg.setAttribute('height', '100%');
        this.svg.style.cursor = 'grab';
        this.container.appendChild(this.svg);

        // Defs (สำหรับ clip paths, gradients, filters)
        this.defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        this.svg.appendChild(this.defs);

        // Shadow filter
        const filter = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
        filter.setAttribute('id', 'shadow');
        filter.setAttribute('x', '-10%');
        filter.setAttribute('y', '-10%');
        filter.setAttribute('width', '120%');
        filter.setAttribute('height', '130%');
        const feDropShadow = document.createElementNS('http://www.w3.org/2000/svg', 'feDropShadow');
        feDropShadow.setAttribute('dx', '0');
        feDropShadow.setAttribute('dy', '2');
        feDropShadow.setAttribute('stdDeviation', '3');
        feDropShadow.setAttribute('flood-color', '#00000020');
        filter.appendChild(feDropShadow);
        this.defs.appendChild(filter);

        // Main group (transform applies here)
        this.mainGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        this.mainGroup.setAttribute('class', 'main-group');
        this.svg.appendChild(this.mainGroup);

        // Connections group (render behind nodes)
        this.connectionsGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        this.connectionsGroup.setAttribute('class', 'connections');
        this.mainGroup.appendChild(this.connectionsGroup);

        // Nodes group
        this.nodesGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        this.nodesGroup.setAttribute('class', 'nodes');
        this.mainGroup.appendChild(this.nodesGroup);

        // Event listeners
        this.bindEvents();

        // สร้าง zoom controls
        this.createZoomControls();
    }

    // ===== Event Binding =====

    bindEvents() {
        // Mouse drag
        this.svg.addEventListener('mousedown', (e) => this.onDragStart(e));
        window.addEventListener('mousemove', (e) => this.onDragMove(e));
        window.addEventListener('mouseup', () => this.onDragEnd());

        // Touch drag
        this.svg.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: false });
        this.svg.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: false });
        this.svg.addEventListener('touchend', () => this.onTouchEnd());

        // Zoom (scroll)
        this.svg.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            this.zoom(delta, e.clientX, e.clientY);
        }, { passive: false });
    }

    onDragStart(e) {
        if (e.target.closest('.node-group')) return;
        this.isDragging = true;
        this.dragStart = { x: e.clientX, y: e.clientY };
        this.lastTransform = { x: this.transform.x, y: this.transform.y };
        this.svg.style.cursor = 'grabbing';
    }

    onDragMove(e) {
        if (!this.isDragging) return;
        const dx = e.clientX - this.dragStart.x;
        const dy = e.clientY - this.dragStart.y;
        this.transform.x = this.lastTransform.x + dx;
        this.transform.y = this.lastTransform.y + dy;
        this.applyTransform();
    }

    onDragEnd() {
        this.isDragging = false;
        this.svg.style.cursor = 'grab';
    }

    onTouchStart(e) {
        if (e.touches.length === 1) {
            this.isDragging = true;
            this.dragStart = { x: e.touches[0].clientX, y: e.touches[0].clientY };
            this.lastTransform = { x: this.transform.x, y: this.transform.y };
        } else if (e.touches.length === 2) {
            this.isDragging = false;
            this.lastPinchDist = this.getTouchDistance(e.touches);
            this.lastTouchCenter = this.getTouchCenter(e.touches);
        }
        e.preventDefault();
    }

    onTouchMove(e) {
        if (e.touches.length === 1 && this.isDragging) {
            const dx = e.touches[0].clientX - this.dragStart.x;
            const dy = e.touches[0].clientY - this.dragStart.y;
            this.transform.x = this.lastTransform.x + dx;
            this.transform.y = this.lastTransform.y + dy;
            this.applyTransform();
        } else if (e.touches.length === 2) {
            const dist = this.getTouchDistance(e.touches);
            const center = this.getTouchCenter(e.touches);
            if (this.lastPinchDist) {
                const delta = dist / this.lastPinchDist;
                this.zoom(delta, center.x, center.y);
            }
            this.lastPinchDist = dist;
            this.lastTouchCenter = center;
        }
        e.preventDefault();
    }

    onTouchEnd() {
        this.isDragging = false;
        this.lastPinchDist = 0;
    }

    getTouchDistance(touches) {
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.sqrt(dx * dx + dy * dy);
    }

    getTouchCenter(touches) {
        return {
            x: (touches[0].clientX + touches[1].clientX) / 2,
            y: (touches[0].clientY + touches[1].clientY) / 2,
        };
    }

    // ===== Zoom =====

    zoom(delta, cx, cy) {
        const rect = this.container.getBoundingClientRect();
        const x = cx - rect.left;
        const y = cy - rect.top;

        const oldScale = this.transform.scale;
        this.transform.scale = Math.min(3, Math.max(0.1, oldScale * delta));
        const scaleChange = this.transform.scale / oldScale;

        this.transform.x = x - scaleChange * (x - this.transform.x);
        this.transform.y = y - scaleChange * (y - this.transform.y);
        this.applyTransform();
    }

    zoomIn() { this.zoom(1.2, this.container.clientWidth / 2, this.container.clientHeight / 2); }
    zoomOut() { this.zoom(0.8, this.container.clientWidth / 2, this.container.clientHeight / 2); }

    applyTransform() {
        this.mainGroup.setAttribute('transform',
            `translate(${this.transform.x}, ${this.transform.y}) scale(${this.transform.scale})`
        );
    }

    // ===== Zoom Controls UI =====

    createZoomControls() {
        const controls = document.createElement('div');
        controls.className = 'absolute bottom-4 right-4 flex flex-col gap-2 z-20';
        controls.innerHTML = `
            <button onclick="this.closest('[data-genealogy]').__genealogy.zoomIn()"
                    class="w-10 h-10 bg-white dark:bg-gray-800 rounded-lg shadow-lg flex items-center justify-center text-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-600">+</button>
            <button onclick="this.closest('[data-genealogy]').__genealogy.zoomOut()"
                    class="w-10 h-10 bg-white dark:bg-gray-800 rounded-lg shadow-lg flex items-center justify-center text-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-600">&minus;</button>
            <button onclick="this.closest('[data-genealogy]').__genealogy.fitToScreen()"
                    class="w-10 h-10 bg-white dark:bg-gray-800 rounded-lg shadow-lg flex items-center justify-center text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition border border-gray-200 dark:border-gray-600" title="พอดีหน้าจอ">&#8862;</button>
        `;
        this.container.setAttribute('data-genealogy', '');
        this.container.__genealogy = this;
        this.container.appendChild(controls);
    }

    // ===== Data Loading =====

    async loadTree() {
        try {
            this.showLoading();
            const url = `${this.options.apiUrl}?type=${this.options.treeType}&depth=${this.options.maxDepth}`;
            const resp = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            this.treeData = data.data || data.tree || data;
            this.renderTree();
        } catch (err) {
            console.error('Genealogy load error:', err);
            this.showError('ไม่สามารถโหลดข้อมูลผังสายงาน');
        }
    }

    showLoading() {
        this.nodesGroup.innerHTML = '';
        this.connectionsGroup.innerHTML = '';
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', this.container.clientWidth / 2);
        text.setAttribute('y', this.container.clientHeight / 2);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('fill', '#9CA3AF');
        text.setAttribute('font-size', '16');
        text.textContent = 'กำลังโหลดผังสายงาน...';
        this.nodesGroup.appendChild(text);
    }

    showError(msg) {
        this.nodesGroup.innerHTML = '';
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', this.container.clientWidth / 2);
        text.setAttribute('y', this.container.clientHeight / 2);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('fill', '#EF4444');
        text.setAttribute('font-size', '14');
        text.textContent = msg;
        this.nodesGroup.appendChild(text);
    }

    // ===== Tree Layout =====

    renderTree() {
        if (!this.treeData) return;
        this.nodesGroup.innerHTML = '';
        this.connectionsGroup.innerHTML = '';
        this.flatNodes.clear();

        // คำนวณตำแหน่ง
        this.layoutTree(this.treeData, 0);

        // วาดเส้นเชื่อม
        this.drawConnections(this.treeData);

        // วาด nodes
        this.drawNodes(this.treeData);

        // Fit to screen
        this.fitToScreen();
    }

    layoutTree(node, depth) {
        if (!node) return 0;

        const nw = this.options.nodeWidth;
        const nh = this.options.nodeHeight;
        const hGap = this.options.horizontalGap;
        const vGap = this.options.verticalGap;

        node._depth = depth;
        node._expanded = this.expandedNodes.has(node.id) || depth < 2;

        const children = this.getChildren(node);
        const visibleChildren = node._expanded ? children : [];

        if (visibleChildren.length === 0) {
            node._width = nw;
            node._x = 0;
            node._y = depth * (nh + vGap);
            this.flatNodes.set(node.id, node);
            return nw;
        }

        // Layout children
        let totalWidth = 0;
        for (const child of visibleChildren) {
            if (totalWidth > 0) totalWidth += hGap;
            const childWidth = this.layoutTree(child, depth + 1);
            totalWidth += childWidth;
        }

        node._width = Math.max(nw, totalWidth);
        node._y = depth * (nh + vGap);

        // จัดตำแหน่ง children
        let startX = -(totalWidth / 2);
        for (const child of visibleChildren) {
            child._x = startX + child._width / 2;
            this.offsetSubtree(child, startX + child._width / 2 - child._x);
            startX += child._width + hGap;
        }

        node._x = 0;
        if (visibleChildren.length > 0) {
            const first = visibleChildren[0];
            const last = visibleChildren[visibleChildren.length - 1];
            node._x = (first._x + last._x) / 2;
        }

        this.flatNodes.set(node.id, node);
        return node._width;
    }

    offsetSubtree(node, dx) {
        node._x += dx;
        const children = node._expanded ? this.getChildren(node) : [];
        for (const child of children) {
            this.offsetSubtree(child, dx);
        }
    }

    getChildren(node) {
        if (this.options.treeType === 'binary') {
            const children = [];
            if (node.left) children.push(node.left);
            if (node.right) children.push(node.right);
            return children;
        }
        return node.children || [];
    }

    // ===== Draw Connections =====

    drawConnections(node) {
        if (!node || !node._expanded) return;

        const children = this.getChildren(node);
        const nh = this.options.nodeHeight;

        for (const child of children) {
            const x1 = node._x;
            const y1 = node._y + nh;
            const x2 = child._x;
            const y2 = child._y;

            // Bézier curve
            const midY = (y1 + y2) / 2;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const d = `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`;
            path.setAttribute('d', d);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('stroke-linecap', 'round');

            // สีตามสถานะ child
            const status = child.retention_status || 'active';
            const colors = {
                active: '#22C55E',
                grace_period: '#EAB308',
                inactive: '#EF4444',
            };
            path.setAttribute('stroke', colors[status] || '#9CA3AF');
            path.setAttribute('opacity', status === 'inactive' ? '0.4' : '0.7');

            this.connectionsGroup.appendChild(path);

            // Recursive
            this.drawConnections(child);
        }
    }

    // ===== Draw Nodes =====

    drawNodes(node) {
        if (!node) return;

        const nw = this.options.nodeWidth;
        const nh = this.options.nodeHeight;
        const x = node._x - nw / 2;
        const y = node._y;

        // Node group
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'node-group');
        g.setAttribute('transform', `translate(${x}, ${y})`);
        g.setAttribute('data-node-id', node.id);
        g.style.cursor = 'pointer';

        // Status colors
        const status = node.retention_status || 'active';
        const borderColors = {
            active: '#22C55E',
            grace_period: '#EAB308',
            inactive: '#EF4444',
        };
        const borderColor = borderColors[status] || '#9CA3AF';
        const bgOpacity = status === 'inactive' ? 0.5 : 1;

        // Card background
        const isDark = document.documentElement.classList.contains('dark');
        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('width', nw);
        rect.setAttribute('height', nh);
        rect.setAttribute('rx', '12');
        rect.setAttribute('fill', isDark ? '#1F2937' : '#FFFFFF');
        rect.setAttribute('stroke', borderColor);
        rect.setAttribute('stroke-width', '2');
        rect.setAttribute('opacity', bgOpacity);
        rect.setAttribute('filter', 'url(#shadow)');
        g.appendChild(rect);

        // Avatar circle
        const avatarSize = 36;
        const avatarX = nw / 2;
        const avatarY = 24;

        // Clip path for avatar
        const clipId = `clip-${node.id}`;
        const clipPath = document.createElementNS('http://www.w3.org/2000/svg', 'clipPath');
        clipPath.setAttribute('id', clipId);
        const clipCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        clipCircle.setAttribute('cx', avatarX);
        clipCircle.setAttribute('cy', avatarY);
        clipCircle.setAttribute('r', avatarSize / 2);
        clipPath.appendChild(clipCircle);
        this.defs.appendChild(clipPath);

        // Avatar border
        const avatarBorder = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        avatarBorder.setAttribute('cx', avatarX);
        avatarBorder.setAttribute('cy', avatarY);
        avatarBorder.setAttribute('r', avatarSize / 2 + 2);
        avatarBorder.setAttribute('fill', borderColor);
        g.appendChild(avatarBorder);

        // Avatar image
        const avatarUrl = node.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent((node.name || 'U').charAt(0))}&background=6366f1&color=fff&size=36`;
        const img = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        img.setAttribute('href', avatarUrl);
        img.setAttribute('x', avatarX - avatarSize / 2);
        img.setAttribute('y', avatarY - avatarSize / 2);
        img.setAttribute('width', avatarSize);
        img.setAttribute('height', avatarSize);
        img.setAttribute('clip-path', `url(#${clipId})`);
        img.setAttribute('preserveAspectRatio', 'xMidYMid slice');
        g.appendChild(img);

        // Name
        const nameText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        nameText.setAttribute('x', nw / 2);
        nameText.setAttribute('y', 54);
        nameText.setAttribute('text-anchor', 'middle');
        nameText.setAttribute('fill', isDark ? '#F3F4F6' : '#111827');
        nameText.setAttribute('font-size', '11');
        nameText.setAttribute('font-weight', '600');
        const displayName = (node.name || 'Unknown').length > 14 ? (node.name || '').substring(0, 12) + '..' : node.name;
        nameText.textContent = displayName;
        g.appendChild(nameText);

        // Member code
        const codeText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        codeText.setAttribute('x', nw / 2);
        codeText.setAttribute('y', 67);
        codeText.setAttribute('text-anchor', 'middle');
        codeText.setAttribute('fill', isDark ? '#9CA3AF' : '#6B7280');
        codeText.setAttribute('font-size', '9');
        codeText.textContent = node.member_code || '';
        g.appendChild(codeText);

        // Rank badge
        if (node.rank_name) {
            const rankBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            const rankColor = node.rank_color || '#6366F1';
            rankBg.setAttribute('x', nw / 2 - 30);
            rankBg.setAttribute('y', 72);
            rankBg.setAttribute('width', 60);
            rankBg.setAttribute('height', 14);
            rankBg.setAttribute('rx', 7);
            rankBg.setAttribute('fill', rankColor);
            g.appendChild(rankBg);

            const rankText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            rankText.setAttribute('x', nw / 2);
            rankText.setAttribute('y', 83);
            rankText.setAttribute('text-anchor', 'middle');
            rankText.setAttribute('fill', '#FFFFFF');
            rankText.setAttribute('font-size', '8');
            rankText.setAttribute('font-weight', '600');
            rankText.textContent = node.rank_name.length > 10 ? node.rank_name.substring(0, 8) + '..' : node.rank_name;
            g.appendChild(rankText);
        }

        // Status indicator
        if (status === 'grace_period' && node.retention_days_left != null) {
            const warnBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            warnBg.setAttribute('x', 0);
            warnBg.setAttribute('y', nh - 16);
            warnBg.setAttribute('width', nw);
            warnBg.setAttribute('height', 16);
            warnBg.setAttribute('rx', '0 0 12 12');
            warnBg.setAttribute('fill', '#FEF3C7');
            g.appendChild(warnBg);

            const warnText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            warnText.setAttribute('x', nw / 2);
            warnText.setAttribute('y', nh - 4);
            warnText.setAttribute('text-anchor', 'middle');
            warnText.setAttribute('fill', '#92400E');
            warnText.setAttribute('font-size', '8');
            warnText.textContent = `เหลือ ${node.retention_days_left} วัน`;
            g.appendChild(warnText);
        } else if (status === 'inactive') {
            const inactiveBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            inactiveBg.setAttribute('x', 0);
            inactiveBg.setAttribute('y', nh - 16);
            inactiveBg.setAttribute('width', nw);
            inactiveBg.setAttribute('height', 16);
            inactiveBg.setAttribute('fill', '#FEE2E2');
            g.appendChild(inactiveBg);

            const inactiveText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            inactiveText.setAttribute('x', nw / 2);
            inactiveText.setAttribute('y', nh - 4);
            inactiveText.setAttribute('text-anchor', 'middle');
            inactiveText.setAttribute('fill', '#991B1B');
            inactiveText.setAttribute('font-size', '8');
            inactiveText.textContent = 'หมดอายุ';
            g.appendChild(inactiveText);
        }

        // Expand/Collapse button
        const children = this.getChildren(node);
        if (children.length > 0) {
            const btnG = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            btnG.style.cursor = 'pointer';
            const btnCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            btnCircle.setAttribute('cx', nw / 2);
            btnCircle.setAttribute('cy', nh + 8);
            btnCircle.setAttribute('r', 8);
            btnCircle.setAttribute('fill', isDark ? '#374151' : '#F3F4F6');
            btnCircle.setAttribute('stroke', '#9CA3AF');
            btnCircle.setAttribute('stroke-width', '1');
            btnG.appendChild(btnCircle);

            const btnText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            btnText.setAttribute('x', nw / 2);
            btnText.setAttribute('y', nh + 12);
            btnText.setAttribute('text-anchor', 'middle');
            btnText.setAttribute('fill', '#6B7280');
            btnText.setAttribute('font-size', '10');
            btnText.textContent = node._expanded ? '−' : '+';
            btnG.appendChild(btnText);

            // Count badge
            const countBg = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            countBg.setAttribute('cx', nw / 2 + 14);
            countBg.setAttribute('cy', nh + 4);
            countBg.setAttribute('r', 7);
            countBg.setAttribute('fill', '#6366F1');
            btnG.appendChild(countBg);
            const countText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            countText.setAttribute('x', nw / 2 + 14);
            countText.setAttribute('y', nh + 7.5);
            countText.setAttribute('text-anchor', 'middle');
            countText.setAttribute('fill', '#FFFFFF');
            countText.setAttribute('font-size', '8');
            countText.textContent = children.length;
            btnG.appendChild(countText);

            btnG.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleExpand(node.id);
            });

            g.appendChild(btnG);
        }

        // Click handler
        g.addEventListener('click', () => {
            this.selectedNode = node;
            if (this.options.onNodeClick) {
                this.options.onNodeClick(node);
            }
            this.highlightNode(node.id);
        });

        this.nodesGroup.appendChild(g);

        // Draw children
        if (node._expanded) {
            for (const child of children) {
                this.drawNodes(child);
            }
        }
    }

    // ===== Expand/Collapse =====

    toggleExpand(nodeId) {
        if (this.expandedNodes.has(nodeId)) {
            this.expandedNodes.delete(nodeId);
        } else {
            this.expandedNodes.add(nodeId);
        }
        this.renderTree();
    }

    // ===== Highlight =====

    highlightNode(nodeId) {
        // Remove previous highlight
        this.nodesGroup.querySelectorAll('.node-highlight').forEach(el => el.remove());

        const nodeEl = this.nodesGroup.querySelector(`[data-node-id="${nodeId}"]`);
        if (!nodeEl) return;

        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('class', 'node-highlight');
        rect.setAttribute('width', this.options.nodeWidth + 8);
        rect.setAttribute('height', this.options.nodeHeight + 8);
        rect.setAttribute('x', -4);
        rect.setAttribute('y', -4);
        rect.setAttribute('rx', 14);
        rect.setAttribute('fill', 'none');
        rect.setAttribute('stroke', '#6366F1');
        rect.setAttribute('stroke-width', '3');
        rect.setAttribute('stroke-dasharray', '5,3');
        nodeEl.insertBefore(rect, nodeEl.firstChild);
    }

    // ===== Search =====

    search(query) {
        if (!query || query.trim() === '') {
            this.searchResults = [];
            this.searchIndex = -1;
            this.renderTree();
            return [];
        }

        const q = query.toLowerCase();
        this.searchResults = [];

        this.flatNodes.forEach((node) => {
            const name = (node.name || '').toLowerCase();
            const code = (node.member_code || '').toLowerCase();
            if (name.includes(q) || code.includes(q)) {
                this.searchResults.push(node);
            }
        });

        if (this.searchResults.length > 0) {
            this.searchIndex = 0;
            this.scrollToNode(this.searchResults[0]);
        }

        return this.searchResults;
    }

    searchNext() {
        if (this.searchResults.length === 0) return;
        this.searchIndex = (this.searchIndex + 1) % this.searchResults.length;
        this.scrollToNode(this.searchResults[this.searchIndex]);
    }

    scrollToNode(node) {
        if (!node) return;

        // Expand parents ให้เห็น node
        // ... (simplified: just scroll to position)

        const cx = this.container.clientWidth / 2;
        const cy = this.container.clientHeight / 2;
        this.transform.x = cx - node._x * this.transform.scale;
        this.transform.y = cy - node._y * this.transform.scale;
        this.applyTransform();

        this.highlightNode(node.id);
    }

    // ===== Fit to Screen =====

    fitToScreen() {
        if (this.flatNodes.size === 0) return;

        let minX = Infinity, maxX = -Infinity;
        let minY = Infinity, maxY = -Infinity;
        const nw = this.options.nodeWidth;
        const nh = this.options.nodeHeight;

        this.flatNodes.forEach(node => {
            minX = Math.min(minX, node._x - nw / 2);
            maxX = Math.max(maxX, node._x + nw / 2);
            minY = Math.min(minY, node._y);
            maxY = Math.max(maxY, node._y + nh);
        });

        const treeWidth = maxX - minX;
        const treeHeight = maxY - minY;
        const containerWidth = this.container.clientWidth;
        const containerHeight = this.container.clientHeight;

        const padding = 40;
        const scaleX = (containerWidth - padding * 2) / treeWidth;
        const scaleY = (containerHeight - padding * 2) / treeHeight;
        this.transform.scale = Math.min(1.5, Math.max(0.1, Math.min(scaleX, scaleY)));

        this.transform.x = (containerWidth - treeWidth * this.transform.scale) / 2 - minX * this.transform.scale;
        this.transform.y = padding - minY * this.transform.scale;

        this.applyTransform();
    }

    // ===== Public Methods =====

    setTreeType(type) {
        this.options.treeType = type;
        this.expandedNodes.clear();
        this.loadTree();
    }

    destroy() {
        this.container.innerHTML = '';
    }
}

// Shadow filter (global)
document.addEventListener('DOMContentLoaded', () => {
    // Create shadow filter when any genealogy is initialized
});

// Export
window.MlmGenealogyV3 = MlmGenealogyV3;
