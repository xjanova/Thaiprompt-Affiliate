import * as d3 from 'd3';
import { gsap } from 'gsap';

/**
 * Interactive Tree Visualization Component
 * Features: Zoom, Pan, Avatar display, Tooltips, Click details, Animations
 */
export class TreeVisualization {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);

        if (!this.container) {
            console.error(`Container with id "${containerId}" not found`);
            return;
        }

        // Configuration
        this.config = {
            width: options.width || this.container.clientWidth || 1200,
            height: options.height || this.container.clientHeight || 800,
            nodeRadius: options.nodeRadius || 30,
            avatarRadius: options.avatarRadius || 25,
            nodeSpacing: {
                horizontal: options.horizontalSpacing || 180,
                vertical: options.verticalSpacing || 150
            },
            colors: {
                node: options.nodeColor || '#6366f1',
                nodeHover: options.nodeHoverColor || '#818cf8',
                link: options.linkColor || '#cbd5e1',
                linkHover: options.linkHoverColor || '#94a3b8',
                text: options.textColor || '#1e293b',
                background: options.backgroundColor || '#ffffff'
            },
            animation: {
                duration: options.animationDuration || 750,
                ease: d3.easeCubicInOut
            },
            onNodeClick: options.onNodeClick || null,
            readOnly: options.readOnly !== false, // Default: read-only (no drag)
        };

        this.zoom = null;
        this.svg = null;
        this.g = null;
        this.tree = null;
        this.root = null;
        this.tooltip = null;

        this.init();
    }

    init() {
        // Clear container
        this.container.innerHTML = '';

        // Create tooltip
        this.createTooltip();

        // Create SVG
        this.svg = d3.select(`#${this.containerId}`)
            .append('svg')
            .attr('width', this.config.width)
            .attr('height', this.config.height)
            .style('background', this.config.colors.background)
            .style('border-radius', '12px')
            .style('box-shadow', '0 4px 6px -1px rgba(0, 0, 0, 0.1)');

        // Create zoom behavior
        this.zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on('zoom', (event) => {
                this.g.attr('transform', event.transform);
            });

        this.svg.call(this.zoom);

        // Create main group
        this.g = this.svg.append('g')
            .attr('transform', `translate(${this.config.width / 2}, 80)`);

        // Create tree layout
        this.tree = d3.tree()
            .nodeSize([this.config.nodeSpacing.horizontal, this.config.nodeSpacing.vertical])
            .separation((a, b) => (a.parent === b.parent ? 1 : 1.2));
    }

    createTooltip() {
        this.tooltip = d3.select('body')
            .append('div')
            .attr('class', 'tree-tooltip')
            .style('position', 'absolute')
            .style('visibility', 'hidden')
            .style('background', 'rgba(0, 0, 0, 0.8)')
            .style('color', 'white')
            .style('padding', '12px 16px')
            .style('border-radius', '8px')
            .style('font-size', '14px')
            .style('pointer-events', 'none')
            .style('z-index', '1000')
            .style('box-shadow', '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)');
    }

    loadData(data) {
        // Convert flat data to hierarchy
        this.root = d3.hierarchy(data);
        this.root.x0 = 0;
        this.root.y0 = 0;

        // Collapse all nodes initially (except root)
        if (this.root.children) {
            this.root.children.forEach(d => this.collapse(d));
        }

        this.update(this.root);

        // Center the view
        this.centerNode(this.root);
    }

    collapse(d) {
        if (d.children) {
            d._children = d.children;
            d._children.forEach(child => this.collapse(child));
            d.children = null;
        }
    }

    expand(d) {
        if (d._children) {
            d.children = d._children;
            d._children = null;
        }
    }

    toggleChildren(d) {
        if (d.children) {
            d._children = d.children;
            d.children = null;
        } else if (d._children) {
            d.children = d._children;
            d._children = null;
        }
        this.update(d);
    }

    update(source) {
        const duration = this.config.animation.duration;

        // Compute the new tree layout
        const treeData = this.tree(this.root);
        const nodes = treeData.descendants();
        const links = treeData.links();

        // Update the nodes
        const node = this.g.selectAll('g.node')
            .data(nodes, d => d.id || (d.id = ++this.nodeId));

        // Enter new nodes
        const nodeEnter = node.enter()
            .append('g')
            .attr('class', 'node')
            .attr('transform', d => `translate(${source.x0},${source.y0})`)
            .style('opacity', 0)
            .on('click', (event, d) => this.onNodeClick(event, d))
            .on('mouseover', (event, d) => this.showTooltip(event, d))
            .on('mouseout', () => this.hideTooltip());

        // Add avatar circles
        nodeEnter.append('circle')
            .attr('class', 'node-circle')
            .attr('r', this.config.avatarRadius)
            .style('fill', d => this.getNodeColor(d))
            .style('stroke', '#fff')
            .style('stroke-width', '3px')
            .style('cursor', 'pointer')
            .style('transition', 'all 0.3s ease');

        // Add avatar text (initial letter)
        nodeEnter.append('text')
            .attr('class', 'node-avatar-text')
            .attr('dy', '0.35em')
            .attr('text-anchor', 'middle')
            .style('fill', '#fff')
            .style('font-size', '16px')
            .style('font-weight', 'bold')
            .style('pointer-events', 'none')
            .text(d => d.data.avatar?.text || d.data.name.charAt(0).toUpperCase());

        // Add name label
        nodeEnter.append('text')
            .attr('class', 'node-name')
            .attr('dy', this.config.avatarRadius + 20)
            .attr('text-anchor', 'middle')
            .style('fill', this.config.colors.text)
            .style('font-size', '12px')
            .style('font-weight', '600')
            .style('pointer-events', 'none')
            .text(d => this.truncateText(d.data.name, 15));

        // Add level badge
        nodeEnter.append('text')
            .attr('class', 'node-level')
            .attr('dy', this.config.avatarRadius + 35)
            .attr('text-anchor', 'middle')
            .style('fill', '#64748b')
            .style('font-size', '10px')
            .style('pointer-events', 'none')
            .text(d => `L${d.data.level || 0}`);

        // Add expand/collapse indicator
        nodeEnter.append('circle')
            .attr('class', 'node-toggle')
            .attr('r', 8)
            .attr('cx', this.config.avatarRadius - 5)
            .attr('cy', -this.config.avatarRadius + 5)
            .style('fill', '#f59e0b')
            .style('stroke', '#fff')
            .style('stroke-width', '2px')
            .style('cursor', 'pointer')
            .style('display', d => (d.children || d._children) ? 'block' : 'none');

        nodeEnter.append('text')
            .attr('class', 'node-toggle-text')
            .attr('x', this.config.avatarRadius - 5)
            .attr('y', -this.config.avatarRadius + 5)
            .attr('dy', '0.35em')
            .attr('text-anchor', 'middle')
            .style('fill', '#fff')
            .style('font-size', '10px')
            .style('font-weight', 'bold')
            .style('pointer-events', 'none')
            .style('display', d => (d.children || d._children) ? 'block' : 'none')
            .text(d => d.children ? '-' : '+');

        // Merge enter + update
        const nodeUpdate = nodeEnter.merge(node);

        // Transition to the proper position
        nodeUpdate.transition()
            .duration(duration)
            .attr('transform', d => `translate(${d.x},${d.y})`)
            .style('opacity', 1);

        // Update circle colors
        nodeUpdate.select('.node-circle')
            .style('fill', d => this.getNodeColor(d));

        // Update toggle indicator
        nodeUpdate.select('.node-toggle')
            .style('display', d => (d.children || d._children) ? 'block' : 'none');

        nodeUpdate.select('.node-toggle-text')
            .style('display', d => (d.children || d._children) ? 'block' : 'none')
            .text(d => d.children ? '-' : '+');

        // Transition exiting nodes
        const nodeExit = node.exit()
            .transition()
            .duration(duration)
            .attr('transform', d => `translate(${source.x},${source.y})`)
            .style('opacity', 0)
            .remove();

        // Update the links
        const link = this.g.selectAll('path.link')
            .data(links, d => d.target.id);

        // Enter new links
        const linkEnter = link.enter()
            .insert('path', 'g')
            .attr('class', 'link')
            .attr('d', d => {
                const o = { x: source.x0, y: source.y0 };
                return this.diagonal(o, o);
            })
            .style('fill', 'none')
            .style('stroke', this.config.colors.link)
            .style('stroke-width', '2px')
            .style('opacity', 0);

        // Merge enter + update
        const linkUpdate = linkEnter.merge(link);

        // Transition to the proper position
        linkUpdate.transition()
            .duration(duration)
            .attr('d', d => this.diagonal(d.source, d.target))
            .style('opacity', 1);

        // Transition exiting links
        link.exit()
            .transition()
            .duration(duration)
            .attr('d', d => {
                const o = { x: source.x, y: source.y };
                return this.diagonal(o, o);
            })
            .style('opacity', 0)
            .remove();

        // Store old positions
        nodes.forEach(d => {
            d.x0 = d.x;
            d.y0 = d.y;
        });
    }

    diagonal(s, d) {
        // Creates a curved (diagonal) path from parent to child nodes
        return `M ${s.x},${s.y}
                C ${s.x},${(s.y + d.y) / 2}
                  ${d.x},${(s.y + d.y) / 2}
                  ${d.x},${d.y}`;
    }

    getNodeColor(d) {
        // Color based on status
        if (d.data.status === 'inactive') {
            return '#94a3b8';
        }

        // Color based on rank
        if (d.data.rank?.color) {
            return d.data.rank.color;
        }

        // Color based on level
        const levelColors = [
            '#6366f1', // Level 0 - Indigo
            '#3b82f6', // Level 1 - Blue
            '#10b981', // Level 2 - Green
            '#f59e0b', // Level 3 - Amber
            '#ef4444', // Level 4+ - Red
        ];

        const level = d.data.level || 0;
        return levelColors[Math.min(level, levelColors.length - 1)];
    }

    onNodeClick(event, d) {
        event.stopPropagation();

        // Toggle children
        this.toggleChildren(d);

        // Call custom click handler if provided
        if (this.config.onNodeClick && typeof this.config.onNodeClick === 'function') {
            this.config.onNodeClick(d.data);
        }
    }

    showTooltip(event, d) {
        const tooltipContent = `
            <div style="min-width: 200px;">
                <div style="font-weight: bold; margin-bottom: 8px; font-size: 16px;">
                    ${d.data.name}
                </div>
                <div style="color: #cbd5e1; margin-bottom: 4px;">
                    ${d.data.email}
                </div>
                <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.2); margin: 8px 0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px;">
                    <div>
                        <div style="color: #cbd5e1;">รหัส:</div>
                        <div style="font-weight: 600;">${d.data.referral_code}</div>
                    </div>
                    <div>
                        <div style="color: #cbd5e1;">ระดับ:</div>
                        <div style="font-weight: 600;">L${d.data.level || 0}</div>
                    </div>
                    <div>
                        <div style="color: #cbd5e1;">ลูกทีม:</div>
                        <div style="font-weight: 600;">${d.data.direct_children || 0}</div>
                    </div>
                    <div>
                        <div style="color: #cbd5e1;">เครือข่าย:</div>
                        <div style="font-weight: 600;">${d.data.total_referrals || 0}</div>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <div style="color: #cbd5e1;">รายได้:</div>
                        <div style="font-weight: 600; color: #10b981;">฿${this.formatNumber(d.data.total_earnings || 0)}</div>
                    </div>
                </div>
                ${d.data.rank ? `
                    <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.2); margin: 8px 0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="width: 12px; height: 12px; border-radius: 50%; background: ${d.data.rank.color};"></div>
                        <div style="font-weight: 600;">${d.data.rank.name}</div>
                    </div>
                ` : ''}
                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.2); font-size: 11px; color: #94a3b8;">
                    คลิกเพื่อดูรายละเอียด | ${d.children || d._children ? 'คลิกเพื่อขยาย/ย่อ' : ''}
                </div>
            </div>
        `;

        this.tooltip
            .html(tooltipContent)
            .style('visibility', 'visible');

        this.positionTooltip(event);
    }

    positionTooltip(event) {
        const tooltipNode = this.tooltip.node();
        const tooltipWidth = tooltipNode.offsetWidth;
        const tooltipHeight = tooltipNode.offsetHeight;
        const padding = 10;

        let left = event.pageX + padding;
        let top = event.pageY + padding;

        // Keep tooltip within viewport
        if (left + tooltipWidth > window.innerWidth) {
            left = event.pageX - tooltipWidth - padding;
        }

        if (top + tooltipHeight > window.innerHeight) {
            top = event.pageY - tooltipHeight - padding;
        }

        this.tooltip
            .style('left', `${left}px`)
            .style('top', `${top}px`);
    }

    hideTooltip() {
        this.tooltip.style('visibility', 'hidden');
    }

    centerNode(d) {
        const scale = 0.8;
        const x = -d.x * scale + this.config.width / 2;
        const y = -d.y * scale + this.config.height / 2;

        this.svg.transition()
            .duration(this.config.animation.duration)
            .call(
                this.zoom.transform,
                d3.zoomIdentity.translate(x, y).scale(scale)
            );
    }

    zoomIn() {
        this.svg.transition()
            .duration(300)
            .call(this.zoom.scaleBy, 1.3);
    }

    zoomOut() {
        this.svg.transition()
            .duration(300)
            .call(this.zoom.scaleBy, 0.7);
    }

    resetZoom() {
        this.centerNode(this.root);
    }

    expandAll() {
        this.root.each(d => this.expand(d));
        this.update(this.root);
        this.centerNode(this.root);
    }

    collapseAll() {
        this.root.children?.forEach(d => this.collapse(d));
        this.update(this.root);
        this.centerNode(this.root);
    }

    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    formatNumber(num) {
        return new Intl.NumberFormat('th-TH').format(num);
    }

    resize() {
        this.config.width = this.container.clientWidth;
        this.config.height = this.container.clientHeight;

        this.svg
            .attr('width', this.config.width)
            .attr('height', this.config.height);

        this.g.attr('transform', `translate(${this.config.width / 2}, 80)`);
        this.update(this.root);
    }

    destroy() {
        if (this.tooltip) {
            this.tooltip.remove();
        }
        if (this.svg) {
            this.svg.remove();
        }
    }
}

// Auto-initialize counter
TreeVisualization.prototype.nodeId = 0;

export default TreeVisualization;
