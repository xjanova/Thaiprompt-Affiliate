/**
 * MLM Genealogy Tree Visualization
 * Google Maps-style interactive tree viewer
 * Supports both Unilevel and Binary structures
 */

class MlmGenealogyViewer {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            type: options.type || 'unilevel', // 'unilevel' or 'binary'
            memberCode: options.memberCode,
            apiUrl: options.apiUrl,
            maxDepth: options.maxDepth || 5,
            nodeWidth: 180,
            nodeHeight: 100,
            horizontalSpacing: 50,
            verticalSpacing: 80,
            ...options
        };

        this.data = null;
        this.svg = null;
        this.zoom = null;
        this.transform = { x: 0, y: 0, k: 1 };

        this.init();
    }

    async init() {
        await this.loadData();
        this.createSvg();
        this.render();
    }

    async loadData() {
        try {
            const response = await fetch(`${this.options.apiUrl}?type=${this.options.type}&depth=${this.options.maxDepth}`);
            const result = await response.json();
            this.data = result.data;
        } catch (error) {
            console.error('Error loading genealogy data:', error);
        }
    }

    createSvg() {
        // Clear container
        this.container.innerHTML = '';

        // Create SVG element
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', '600px');
        svg.style.border = '1px solid #e5e7eb';
        svg.style.borderRadius = '8px';
        svg.style.cursor = 'grab';

        this.container.appendChild(svg);
        this.svg = svg;

        // Create main group for pan & zoom
        this.mainGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        svg.appendChild(this.mainGroup);

        // Add zoom & pan functionality
        this.initZoomPan();

        // Add controls
        this.addControls();
    }

    initZoomPan() {
        const svg = this.svg;
        const mainGroup = this.mainGroup;
        let isPanning = false;
        let startPoint = { x: 0, y: 0 };

        // Mouse wheel zoom
        svg.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? 0.9 : 1.1;
            this.transform.k *= delta;
            this.transform.k = Math.max(0.1, Math.min(5, this.transform.k));
            this.applyTransform();
        });

        // Pan on drag
        svg.addEventListener('mousedown', (e) => {
            isPanning = true;
            svg.style.cursor = 'grabbing';
            startPoint = {
                x: e.clientX - this.transform.x,
                y: e.clientY - this.transform.y
            };
        });

        svg.addEventListener('mousemove', (e) => {
            if (!isPanning) return;
            this.transform.x = e.clientX - startPoint.x;
            this.transform.y = e.clientY - startPoint.y;
            this.applyTransform();
        });

        svg.addEventListener('mouseup', () => {
            isPanning = false;
            svg.style.cursor = 'grab';
        });

        svg.addEventListener('mouseleave', () => {
            isPanning = false;
            svg.style.cursor = 'grab';
        });
    }

    applyTransform() {
        const { x, y, k } = this.transform;
        this.mainGroup.setAttribute('transform', `translate(${x}, ${y}) scale(${k})`);
    }

    addControls() {
        const controls = document.createElement('div');
        controls.className = 'absolute top-4 right-4 flex flex-col gap-2 bg-white p-2 rounded-lg shadow-md';
        controls.innerHTML = `
            <button class="zoom-in px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </button>
            <button class="zoom-out px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/>
                </svg>
            </button>
            <button class="reset-view px-3 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        `;

        // Position relative container
        this.container.style.position = 'relative';
        this.container.appendChild(controls);

        // Add event listeners
        controls.querySelector('.zoom-in').addEventListener('click', () => this.zoomIn());
        controls.querySelector('.zoom-out').addEventListener('click', () => this.zoomOut());
        controls.querySelector('.reset-view').addEventListener('click', () => this.resetView());
    }

    zoomIn() {
        this.transform.k *= 1.2;
        this.transform.k = Math.min(5, this.transform.k);
        this.applyTransform();
    }

    zoomOut() {
        this.transform.k *= 0.8;
        this.transform.k = Math.max(0.1, this.transform.k);
        this.applyTransform();
    }

    resetView() {
        this.transform = { x: 300, y: 50, k: 1 };
        this.applyTransform();
    }

    render() {
        if (!this.data) return;

        // Clear previous render
        this.mainGroup.innerHTML = '';

        if (this.options.type === 'binary') {
            this.renderBinaryTree(this.data);
        } else {
            this.renderUnilevelTree(this.data);
        }

        // Center initial view
        this.resetView();
    }

    renderBinaryTree(node, x = 400, y = 50, depth = 0) {
        if (!node || depth > this.options.maxDepth) return;

        const spacing = this.options.horizontalSpacing / (depth + 1);

        // Draw node
        this.drawNode(node, x, y);

        // Calculate positions for children
        const leftX = x - spacing * 100;
        const rightX = x + spacing * 100;
        const childY = y + this.options.verticalSpacing;

        // Draw connections
        if (node.left) {
            this.drawConnection(x, y + this.options.nodeHeight, leftX, childY);
            this.renderBinaryTree(node.left, leftX, childY, depth + 1);
        }

        if (node.right) {
            this.drawConnection(x, y + this.options.nodeHeight, rightX, childY);
            this.renderBinaryTree(node.right, rightX, childY, depth + 1);
        }
    }

    renderUnilevelTree(node, x = 400, y = 50, depth = 0) {
        if (!node || depth > this.options.maxDepth) return;

        // Draw node
        this.drawNode(node, x, y);

        // Calculate positions for children
        const children = node.children || [];
        if (children.length === 0) return;

        const totalWidth = children.length * (this.options.nodeWidth + this.options.horizontalSpacing);
        const startX = x - totalWidth / 2;
        const childY = y + this.options.verticalSpacing;

        children.forEach((child, index) => {
            const childX = startX + index * (this.options.nodeWidth + this.options.horizontalSpacing) + this.options.nodeWidth / 2;

            // Draw connection
            this.drawConnection(x, y + this.options.nodeHeight, childX, childY);

            // Render child
            this.renderUnilevelTree(child, childX, childY, depth + 1);
        });
    }

    drawNode(node, x, y) {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'member-node');
        g.style.cursor = 'pointer';

        // Background rect
        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('x', x - this.options.nodeWidth / 2);
        rect.setAttribute('y', y);
        rect.setAttribute('width', this.options.nodeWidth);
        rect.setAttribute('height', this.options.nodeHeight);
        rect.setAttribute('rx', '8');
        rect.setAttribute('fill', node.status === 'active' ? '#10b981' : '#9ca3af');
        rect.setAttribute('stroke', '#374151');
        rect.setAttribute('stroke-width', '2');
        g.appendChild(rect);

        // Name text
        const name = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        name.setAttribute('x', x);
        name.setAttribute('y', y + 25);
        name.setAttribute('text-anchor', 'middle');
        name.setAttribute('fill', 'white');
        name.setAttribute('font-weight', 'bold');
        name.textContent = node.name || 'Unknown';
        g.appendChild(name);

        // Member code
        const code = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        code.setAttribute('x', x);
        code.setAttribute('y', y + 45);
        code.setAttribute('text-anchor', 'middle');
        code.setAttribute('fill', 'white');
        code.setAttribute('font-size', '12');
        code.textContent = node.member_code;
        g.appendChild(code);

        // PV info
        const pv = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        pv.setAttribute('x', x);
        pv.setAttribute('y', y + 65);
        pv.setAttribute('text-anchor', 'middle');
        pv.setAttribute('fill', 'white');
        pv.setAttribute('font-size', '11');
        pv.textContent = `PV: ${node.total_pv || 0}`;
        g.appendChild(pv);

        // Stats
        const stats = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        stats.setAttribute('x', x);
        stats.setAttribute('y', y + 82);
        stats.setAttribute('text-anchor', 'middle');
        stats.setAttribute('fill', 'white');
        stats.setAttribute('font-size', '10');
        stats.textContent = `Refs: ${node.direct_referrals || 0}`;
        g.appendChild(stats);

        // Add click event
        g.addEventListener('click', () => this.onNodeClick(node));

        this.mainGroup.appendChild(g);
    }

    drawConnection(x1, y1, x2, y2) {
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('stroke', '#9ca3af');
        line.setAttribute('stroke-width', '2');
        this.mainGroup.appendChild(line);
    }

    onNodeClick(node) {
        // Show node details
        alert(`Member: ${node.name}\nCode: ${node.member_code}\nPV: ${node.total_pv}\nStatus: ${node.status}`);
    }

    async refresh() {
        await this.loadData();
        this.render();
    }

    switchType(type) {
        this.options.type = type;
        this.refresh();
    }
}

// Export for use
window.MlmGenealogyViewer = MlmGenealogyViewer;
