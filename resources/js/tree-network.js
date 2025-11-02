/**
 * Tree Network Visualization (Google Maps Style)
 * Using vis-network for smooth pan & zoom experience
 */

import { Network } from 'vis-network/standalone';

export class TreeNetwork {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);

        if (!this.container) {
            console.error(`Container with id "${containerId}" not found`);
            return;
        }

        this.options = {
            onNodeClick: options.onNodeClick || null,
            onNodeHover: options.onNodeHover || null,
            readOnly: options.readOnly !== false,
            physics: options.physics !== false,
            hierarchical: options.hierarchical !== false
        };

        this.network = null;
        this.nodes = null;
        this.edges = null;
        this.rawData = null;

        this.init();
    }

    init() {
        // Clear container
        this.container.innerHTML = '';

        // Configure network options (Google Maps style)
        const networkOptions = {
            nodes: {
                shape: 'circularImage',
                size: 40,
                borderWidth: 3,
                borderWidthSelected: 5,
                color: {
                    border: '#6366f1',
                    background: '#ffffff',
                    highlight: {
                        border: '#818cf8',
                        background: '#e0e7ff'
                    },
                    hover: {
                        border: '#818cf8',
                        background: '#e0e7ff'
                    }
                },
                font: {
                    size: 14,
                    face: 'Arial',
                    color: '#1e293b',
                    bold: {
                        color: '#1e293b',
                        size: 16,
                        face: 'Arial'
                    }
                },
                scaling: {
                    min: 30,
                    max: 60,
                    label: {
                        enabled: true,
                        min: 12,
                        max: 18
                    }
                },
                shadow: {
                    enabled: true,
                    color: 'rgba(0,0,0,0.2)',
                    size: 10,
                    x: 2,
                    y: 2
                }
            },
            edges: {
                width: 2,
                color: {
                    color: '#cbd5e1',
                    highlight: '#94a3b8',
                    hover: '#94a3b8'
                },
                smooth: {
                    enabled: true,
                    type: 'cubicBezier',
                    roundness: 0.5
                },
                arrows: {
                    to: {
                        enabled: true,
                        scaleFactor: 0.5
                    }
                },
                shadow: {
                    enabled: true,
                    color: 'rgba(0,0,0,0.1)',
                    size: 5,
                    x: 1,
                    y: 1
                }
            },
            layout: {
                hierarchical: this.options.hierarchical ? {
                    enabled: true,
                    direction: 'UD',        // Up-Down
                    sortMethod: 'directed',
                    nodeSpacing: 180,
                    levelSeparation: 150,
                    treeSpacing: 200,
                    blockShifting: true,
                    edgeMinimization: true,
                    parentCentralization: true
                } : {
                    randomSeed: 2
                }
            },
            physics: {
                enabled: this.options.physics,
                hierarchicalRepulsion: {
                    centralGravity: 0,
                    springLength: 150,
                    springConstant: 0.01,
                    nodeDistance: 180,
                    damping: 0.09
                },
                solver: 'hierarchicalRepulsion',
                stabilization: {
                    enabled: true,
                    iterations: 1000,
                    updateInterval: 25
                }
            },
            interaction: {
                dragNodes: !this.options.readOnly,
                dragView: true,              // Google Maps style - drag to pan
                zoomView: true,              // Google Maps style - scroll to zoom
                zoomSpeed: 0.5,
                navigationButtons: false,
                keyboard: {
                    enabled: true,
                    speed: { x: 10, y: 10, zoom: 0.02 }
                },
                hover: true,
                tooltipDelay: 100,
                hideEdgesOnDrag: false,
                hideEdgesOnZoom: false
            },
            manipulation: {
                enabled: false
            }
        };

        this.network = new Network(this.container, {}, networkOptions);

        // Setup event listeners
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Click event
        this.network.on('click', (params) => {
            if (params.nodes.length > 0) {
                const nodeId = params.nodes[0];
                const nodeData = this.findNodeData(nodeId);
                if (nodeData && this.options.onNodeClick) {
                    this.options.onNodeClick(nodeData);
                }
            }
        });

        // Hover event
        this.network.on('hoverNode', (params) => {
            const nodeId = params.node;
            const nodeData = this.findNodeData(nodeId);
            if (nodeData && this.options.onNodeHover) {
                this.options.onNodeHover(nodeData, params.event);
            }
            // Change cursor
            this.container.style.cursor = 'pointer';
        });

        this.network.on('blurNode', () => {
            this.container.style.cursor = 'default';
        });

        // Double click to focus
        this.network.on('doubleClick', (params) => {
            if (params.nodes.length > 0) {
                this.focusNode(params.nodes[0]);
            }
        });

        // Stabilization events
        this.network.on('stabilizationProgress', (params) => {
            const progress = Math.round((params.iterations / params.total) * 100);
            this.updateLoadingProgress(progress);
        });

        this.network.on('stabilizationIterationsDone', () => {
            this.hideLoading();
            // Fit network after stabilization
            setTimeout(() => {
                this.fit();
            }, 100);
        });
    }

    loadData(data) {
        this.rawData = data;
        this.showLoading();

        // Convert tree data to nodes and edges
        const { nodes, edges } = this.convertToNetwork(data);

        this.nodes = nodes;
        this.edges = edges;

        // Update network
        this.network.setData({
            nodes: nodes,
            edges: edges
        });
    }

    convertToNetwork(data, parentId = null, nodes = [], edges = [], level = 0) {
        if (!data) return { nodes, edges };

        const nodeId = data.id || `node_${nodes.length}`;

        // Create avatar image URL or use initial letter
        let imageUrl = null;
        if (data.avatar?.type === 'image') {
            imageUrl = data.avatar.url;
        } else {
            // Create data URL for avatar with initial letter
            imageUrl = this.createAvatarDataUrl(
                data.avatar?.text || data.name?.charAt(0)?.toUpperCase() || '?',
                this.getNodeColor(data)
            );
        }

        // Add node
        nodes.push({
            id: nodeId,
            label: this.truncateText(data.name, 20),
            image: imageUrl,
            title: this.createTooltipHtml(data),
            level: level,
            color: {
                border: this.getNodeColor(data),
                background: '#ffffff'
            },
            data: data  // Store original data
        });

        // Add edge from parent
        if (parentId !== null) {
            edges.push({
                from: parentId,
                to: nodeId,
                color: {
                    color: '#cbd5e1',
                    highlight: '#94a3b8'
                }
            });
        }

        // Process children
        if (data.children && Array.isArray(data.children)) {
            data.children.forEach(child => {
                this.convertToNetwork(child, nodeId, nodes, edges, level + 1);
            });
        }

        return { nodes, edges };
    }

    createAvatarDataUrl(letter, color) {
        const canvas = document.createElement('canvas');
        canvas.width = 100;
        canvas.height = 100;
        const ctx = canvas.getContext('2d');

        // Draw circle
        ctx.beginPath();
        ctx.arc(50, 50, 48, 0, 2 * Math.PI);
        ctx.fillStyle = color;
        ctx.fill();
        ctx.lineWidth = 4;
        ctx.strokeStyle = '#ffffff';
        ctx.stroke();

        // Draw letter
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 48px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(letter, 50, 50);

        return canvas.toDataURL();
    }

    createTooltipHtml(data) {
        return `
            <div style="padding: 8px; min-width: 200px;">
                <div style="font-weight: bold; font-size: 14px; margin-bottom: 4px;">
                    ${data.name}
                </div>
                <div style="color: #64748b; font-size: 12px; margin-bottom: 8px;">
                    ${data.email}
                </div>
                <div style="border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 11px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px;">
                        <div>รหัส: <strong>${data.referral_code}</strong></div>
                        <div>ระดับ: <strong>L${data.level || 0}</strong></div>
                        <div>ลูกทีม: <strong>${data.direct_children || 0}</strong></div>
                        <div>เครือข่าย: <strong>${data.total_referrals || 0}</strong></div>
                    </div>
                    <div style="margin-top: 4px; color: #10b981;">
                        รายได้: <strong>฿${this.formatNumber(data.total_earnings || 0)}</strong>
                    </div>
                </div>
            </div>
        `;
    }

    getNodeColor(data) {
        // Color based on status
        if (data.status === 'inactive') {
            return '#94a3b8';
        }

        // Color based on rank
        if (data.rank?.color) {
            return data.rank.color;
        }

        // Color based on level
        const levelColors = [
            '#6366f1', // Level 0 - Indigo
            '#3b82f6', // Level 1 - Blue
            '#10b981', // Level 2 - Green
            '#f59e0b', // Level 3 - Amber
            '#ef4444', // Level 4+ - Red
        ];

        const level = data.level || 0;
        return levelColors[Math.min(level, levelColors.length - 1)];
    }

    findNodeData(nodeId) {
        const node = this.nodes.find(n => n.id === nodeId);
        return node ? node.data : null;
    }

    // Google Maps style controls
    zoomIn() {
        const scale = this.network.getScale();
        this.network.moveTo({
            scale: scale * 1.3,
            animation: {
                duration: 300,
                easingFunction: 'easeInOutQuad'
            }
        });
    }

    zoomOut() {
        const scale = this.network.getScale();
        this.network.moveTo({
            scale: scale * 0.7,
            animation: {
                duration: 300,
                easingFunction: 'easeInOutQuad'
            }
        });
    }

    fit(options = {}) {
        this.network.fit({
            animation: {
                duration: 500,
                easingFunction: 'easeInOutQuad'
            },
            ...options
        });
    }

    focusNode(nodeId, options = {}) {
        this.network.focus(nodeId, {
            scale: 1.5,
            animation: {
                duration: 500,
                easingFunction: 'easeInOutQuad'
            },
            ...options
        });
    }

    resetView() {
        this.fit();
    }

    // Utility methods
    truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    formatNumber(num) {
        return new Intl.NumberFormat('th-TH').format(num);
    }

    showLoading() {
        const loading = document.getElementById(`${this.containerId}-loading`);
        if (loading) loading.classList.remove('hidden');
    }

    hideLoading() {
        const loading = document.getElementById(`${this.containerId}-loading`);
        if (loading) loading.classList.add('hidden');
    }

    updateLoadingProgress(progress) {
        const progressText = document.getElementById(`${this.containerId}-progress`);
        if (progressText) {
            progressText.textContent = `กำลังโหลด... ${progress}%`;
        }
    }

    resize() {
        if (this.network) {
            this.network.redraw();
            this.network.fit();
        }
    }

    destroy() {
        if (this.network) {
            this.network.destroy();
            this.network = null;
        }
    }

    // Export methods
    exportAsImage() {
        const canvas = this.container.querySelector('canvas');
        if (canvas) {
            return canvas.toDataURL('image/png');
        }
        return null;
    }

    getStats() {
        return {
            nodes: this.nodes?.length || 0,
            edges: this.edges?.length || 0,
            levels: Math.max(...(this.nodes?.map(n => n.level) || [0])) + 1
        };
    }
}

export default TreeNetwork;
