/**
 * MLM Tree Visualization Module
 * Creates interactive organizational tree using D3.js
 */

import * as d3 from 'd3';
import { hierarchy, tree } from 'd3-hierarchy';

class MLMTree {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = d3.select(`#${containerId}`);

        this.options = {
            width: options.width || 1200,
            height: options.height || 800,
            nodeWidth: options.nodeWidth || 120,
            nodeHeight: options.nodeHeight || 80,
            verticalSpacing: options.verticalSpacing || 150,
            horizontalSpacing: options.horizontalSpacing || 50,
            animationDuration: options.animationDuration || 750,
            ...options
        };

        this.svg = null;
        this.g = null;
        this.root = null;
        this.treeLayout = null;
        this.zoom = null;

        this.initialize();
    }

    /**
     * Initialize SVG and tree layout
     */
    initialize() {
        // Clear existing content
        this.container.html('');

        // Create SVG
        this.svg = this.container.append('svg')
            .attr('width', '100%')
            .attr('height', this.options.height)
            .attr('class', 'mlm-tree-svg');

        // Create zoom behavior
        this.zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on('zoom', (event) => {
                this.g.attr('transform', event.transform);
            });

        this.svg.call(this.zoom);

        // Create main group for tree
        this.g = this.svg.append('g')
            .attr('class', 'tree-container')
            .attr('transform', `translate(${this.options.width / 2}, 50)`);

        // Create tree layout
        this.treeLayout = tree()
            .nodeSize([
                this.options.nodeWidth + this.options.horizontalSpacing,
                this.options.nodeHeight + this.options.verticalSpacing
            ])
            .separation((a, b) => {
                return a.parent === b.parent ? 1 : 1.5;
            });
    }

    /**
     * Load and render tree data
     */
    async loadTree(userId = null) {
        try {
            const url = userId
                ? `/mlm/tree-data/${userId}`
                : '/mlm/tree-data';

            const response = await fetch(url);
            const data = await response.json();

            this.renderTree(data);
        } catch (error) {
            console.error('Error loading tree data:', error);
            throw error;
        }
    }

    /**
     * Render tree visualization
     */
    renderTree(data) {
        // Create hierarchy
        this.root = hierarchy(data);

        // Apply tree layout
        this.treeLayout(this.root);

        // Render links
        this.renderLinks();

        // Render nodes
        this.renderNodes();

        // Center the tree
        this.centerTree();
    }

    /**
     * Render tree links (connections between nodes)
     */
    renderLinks() {
        const links = this.root.links();

        const link = this.g.selectAll('.link')
            .data(links, d => d.target.data.id);

        // Enter new links
        const linkEnter = link.enter()
            .append('path')
            .attr('class', 'link')
            .attr('fill', 'none')
            .attr('stroke', '#cbd5e0')
            .attr('stroke-width', 2)
            .attr('d', d => {
                return `M${d.source.x},${d.source.y}
                        C${d.source.x},${(d.source.y + d.target.y) / 2}
                         ${d.target.x},${(d.source.y + d.target.y) / 2}
                         ${d.target.x},${d.target.y}`;
            });

        // Update existing links
        link.transition()
            .duration(this.options.animationDuration)
            .attr('d', d => {
                return `M${d.source.x},${d.source.y}
                        C${d.source.x},${(d.source.y + d.target.y) / 2}
                         ${d.target.x},${(d.source.y + d.target.y) / 2}
                         ${d.target.x},${d.target.y}`;
            });

        // Remove old links
        link.exit().remove();
    }

    /**
     * Render tree nodes
     */
    renderNodes() {
        const nodes = this.root.descendants();

        const node = this.g.selectAll('.node')
            .data(nodes, d => d.data.id);

        // Enter new nodes
        const nodeEnter = node.enter()
            .append('g')
            .attr('class', 'node')
            .attr('transform', d => `translate(${d.x},${d.y})`)
            .style('cursor', 'pointer')
            .on('click', (event, d) => this.onNodeClick(event, d))
            .on('mouseenter', (event, d) => this.showTooltip(event, d))
            .on('mouseleave', () => this.hideTooltip());

        // Add node background
        nodeEnter.append('rect')
            .attr('class', 'node-bg')
            .attr('x', -this.options.nodeWidth / 2)
            .attr('y', -this.options.nodeHeight / 2)
            .attr('width', this.options.nodeWidth)
            .attr('height', this.options.nodeHeight)
            .attr('rx', 8)
            .attr('fill', '#ffffff')
            .attr('stroke', d => this.getNodeBorderColor(d.data))
            .attr('stroke-width', d => d.data.maintaining_sales ? 2 : 3);

        // Add avatar
        nodeEnter.append('image')
            .attr('class', 'node-avatar')
            .attr('x', -25)
            .attr('y', -35)
            .attr('width', 50)
            .attr('height', 50)
            .attr('clip-path', 'circle(25px at 25px 25px)')
            .attr('xlink:href', d => d.data.avatar || '/images/default-avatar.png');

        // Add name
        nodeEnter.append('text')
            .attr('class', 'node-name')
            .attr('text-anchor', 'middle')
            .attr('y', 25)
            .style('font-size', '12px')
            .style('font-weight', '600')
            .text(d => this.truncateText(d.data.name, 15));

        // Add rank badge
        nodeEnter.append('text')
            .attr('class', 'node-rank')
            .attr('text-anchor', 'middle')
            .attr('y', 38)
            .style('font-size', '10px')
            .style('fill', d => d.data.rank_color || '#6B7280')
            .text(d => d.data.rank || 'Member');

        // Update existing nodes
        node.transition()
            .duration(this.options.animationDuration)
            .attr('transform', d => `translate(${d.x},${d.y})`);

        // Remove old nodes
        node.exit().remove();
    }

    /**
     * Get node border color based on status
     */
    getNodeBorderColor(data) {
        if (!data.maintaining_sales) {
            return '#ef4444'; // Red for not maintaining sales
        }
        if (data.is_kyc_verified) {
            return '#10b981'; // Green for verified
        }
        return '#cbd5e0'; // Gray for normal
    }

    /**
     * Handle node click
     */
    async onNodeClick(event, d) {
        try {
            const response = await fetch(`/mlm/tree/node/${d.data.id}`);
            const details = await response.json();

            this.showNodeDetails(details);
        } catch (error) {
            console.error('Error fetching node details:', error);
        }
    }

    /**
     * Show tooltip on hover
     */
    showTooltip(event, d) {
        const tooltip = d3.select('body')
            .append('div')
            .attr('class', 'mlm-tree-tooltip')
            .style('position', 'absolute')
            .style('background', 'white')
            .style('border', '1px solid #cbd5e0')
            .style('border-radius', '8px')
            .style('padding', '12px')
            .style('box-shadow', '0 4px 6px rgba(0,0,0,0.1)')
            .style('z-index', '1000')
            .style('pointer-events', 'none');

        tooltip.html(`
            <div class="font-semibold mb-2">${d.data.name}</div>
            <div class="text-sm text-gray-600">Level: ${d.data.level}</div>
            <div class="text-sm text-gray-600">Rank: ${d.data.rank}</div>
            <div class="text-sm text-gray-600">Direct Referrals: ${d.data.direct_referrals}</div>
            <div class="text-sm ${d.data.maintaining_sales ? 'text-green-600' : 'text-red-600'}">
                ${d.data.maintaining_sales ? '✓ Maintaining Sales' : '✗ Not Maintaining Sales'}
            </div>
        `);

        tooltip.style('left', (event.pageX + 10) + 'px')
            .style('top', (event.pageY - 10) + 'px');
    }

    /**
     * Hide tooltip
     */
    hideTooltip() {
        d3.selectAll('.mlm-tree-tooltip').remove();
    }

    /**
     * Show detailed node information in modal
     */
    showNodeDetails(details) {
        // This should be implemented with your modal system
        console.log('Node details:', details);

        // Example: Using SweetAlert2
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: details.name,
                html: `
                    <div class="text-left">
                        <p><strong>Email:</strong> ${details.email}</p>
                        <p><strong>Phone:</strong> ${details.phone || 'N/A'}</p>
                        <p><strong>Rank:</strong> ${details.rank}</p>
                        <p><strong>Level:</strong> ${details.mlm_level}</p>
                        <p><strong>Direct Referrals:</strong> ${details.direct_referrals}</p>
                        <p><strong>Total Downline:</strong> ${details.total_downline}</p>
                        <p><strong>Wallet Balance:</strong> ฿${details.wallet_balance}</p>
                        <p><strong>Total Sales:</strong> ฿${details.total_sales}</p>
                        <p><strong>Month Sales:</strong> ฿${details.month_sales}</p>
                        <p><strong>Total Commissions:</strong> ฿${details.total_commissions}</p>
                        <p><strong>Status:</strong> ${details.maintaining_sales ? '✓ Maintaining Sales' : '✗ Not Maintaining Sales'}</p>
                    </div>
                `,
                width: 600,
                confirmButtonText: 'Close'
            });
        }
    }

    /**
     * Center tree view
     */
    centerTree() {
        const bounds = this.g.node().getBBox();
        const parent = this.svg.node().parentElement;
        const fullWidth = parent.clientWidth;
        const fullHeight = parent.clientHeight;

        const width = bounds.width;
        const height = bounds.height;

        const midX = bounds.x + width / 2;
        const midY = bounds.y + height / 2;

        const scale = 0.8 / Math.max(width / fullWidth, height / fullHeight);
        const translate = [
            fullWidth / 2 - scale * midX,
            fullHeight / 2 - scale * midY
        ];

        this.svg.transition()
            .duration(this.options.animationDuration)
            .call(
                this.zoom.transform,
                d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale)
            );
    }

    /**
     * Truncate text to max length
     */
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength - 3) + '...';
    }

    /**
     * Expand/collapse node
     */
    toggleNode(d) {
        if (d.children) {
            d._children = d.children;
            d.children = null;
        } else {
            d.children = d._children;
            d._children = null;
        }
        this.update(d);
    }

    /**
     * Update tree after changes
     */
    update(source) {
        this.treeLayout(this.root);
        this.renderLinks();
        this.renderNodes();
    }
}

export default MLMTree;
