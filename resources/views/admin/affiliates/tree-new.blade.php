@extends('layouts.admin')

@section('title', 'ผังสายงาน Affiliate (Interactive)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold mb-2">🌳 ผังสายงาน Affiliate (Interactive)</h1>
                <p class="text-purple-100 text-sm lg:text-base">แสดงโครงสร้างเครือข่าย Affiliate ทั้งหมดแบบ Interactive</p>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Affiliate Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลือก Affiliate</label>
                <select id="affiliate-select" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">ทั้งหมด (All Networks)</option>
                    @foreach($rootAffiliates as $aff)
                        <option value="{{ $aff->id }}">{{ $aff->user->name }} ({{ $aff->referral_code }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Max Depth Control -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ความลึกสูงสุด: <span id="depth-value" class="font-bold text-indigo-600">10</span> ชั้น
                </label>
                <input type="range" id="depth-slider" min="1" max="100" value="10" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>1</span>
                    <span>25</span>
                    <span>50</span>
                    <span>75</span>
                    <span>100</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-end gap-2">
                <button onclick="loadTreeData()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                    🔄 โหลดข้อมูล
                </button>
                <button onclick="exportTreeData()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    📥 Export
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6">
            <p class="text-xs lg:text-sm text-gray-600 mb-1">Total Affiliates</p>
            <p class="text-2xl lg:text-3xl font-bold text-indigo-600" id="stat-total">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6">
            <p class="text-xs lg:text-sm text-gray-600 mb-1">Active</p>
            <p class="text-2xl lg:text-3xl font-bold text-green-600" id="stat-active">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6">
            <p class="text-xs lg:text-sm text-gray-600 mb-1">Total Earnings</p>
            <p class="text-xl lg:text-2xl font-bold text-blue-600" id="stat-earnings">-</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6">
            <p class="text-xs lg:text-sm text-gray-600 mb-1">Max Depth</p>
            <p class="text-2xl lg:text-3xl font-bold text-purple-600" id="stat-depth">-</p>
        </div>
    </div>

    <!-- Tree Visualization -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <!-- Toolbar -->
        <div class="border-b border-gray-200 p-4 bg-gray-50">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h2 class="text-xl font-bold text-gray-900">โครงสร้างเครือข่าย</h2>
                <div class="flex items-center gap-2">
                    <button onclick="treeViz.zoomIn()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        🔍 Zoom In
                    </button>
                    <button onclick="treeViz.zoomOut()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        🔍 Zoom Out
                    </button>
                    <button onclick="treeViz.resetZoom()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        🎯 Reset
                    </button>
                    <button onclick="treeViz.expandAll()" class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                        ▼ Expand All
                    </button>
                    <button onclick="treeViz.collapseAll()" class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm font-medium">
                        ▶ Collapse All
                    </button>
                </div>
            </div>
        </div>

        <!-- Tree Container -->
        <div id="tree-container" class="w-full" style="height: 700px; min-height: 700px; position: relative;">
            <!-- Loading State -->
            <div id="tree-loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-indigo-600 mx-auto mb-4"></div>
                    <p class="text-gray-600 font-medium">กำลังโหลดข้อมูล...</p>
                </div>
            </div>

            <!-- Error State -->
            <div id="tree-error" class="hidden absolute inset-0 flex items-center justify-center bg-white">
                <div class="text-center p-8">
                    <div class="text-6xl mb-4">⚠️</div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">เกิดข้อผิดพลาด</h3>
                    <p class="text-gray-500 mb-4" id="tree-error-message"></p>
                    <button onclick="loadTreeData()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        ลองใหม่อีกครั้ง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" onclick="closeDetailModal(event)">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="sticky top-0 bg-gradient-to-r from-purple-500 to-indigo-600 text-white p-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold">รายละเอียด Affiliate</h3>
                    <button onclick="closeDetailModal()" class="text-white hover:text-gray-200 text-2xl">×</button>
                </div>
            </div>
            <div id="detail-content" class="p-6">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- D3.js -->
<script src="https://cdn.jsdelivr.net/npm/d3@7"></script>

<script>
let treeViz = null;
let currentData = null;
let currentAffiliateId = null;
let currentDepth = 10;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadTreeData();
});

function setupEventListeners() {
    // Affiliate select
    document.getElementById('affiliate-select')?.addEventListener('change', function(e) {
        currentAffiliateId = e.target.value || null;
        loadTreeData();
    });

    // Depth slider
    const depthSlider = document.getElementById('depth-slider');
    const depthValue = document.getElementById('depth-value');

    depthSlider?.addEventListener('input', function(e) {
        currentDepth = parseInt(e.target.value);
        if (depthValue) depthValue.textContent = currentDepth;
    });

    depthSlider?.addEventListener('change', function() {
        loadTreeData();
    });
}

async function loadTreeData() {
    const container = document.getElementById('tree-container');
    const loading = document.getElementById('tree-loading');
    const error = document.getElementById('tree-error');

    try {
        loading?.classList.remove('hidden');
        error?.classList.add('hidden');

        // Build API URL
        let apiUrl = '/api/v1/tree/admin';
        if (currentAffiliateId) {
            apiUrl += `/${currentAffiliateId}`;
        }
        apiUrl += `?max_depth=${currentDepth}`;

        const response = await fetch(apiUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error('Failed to load tree data');
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to load tree data');
        }

        currentData = result.data;

        // Update statistics
        if (result.stats) {
            updateStatistics(result.stats);
        }

        // Initialize or update tree
        if (!treeViz) {
            treeViz = new TreeVisualization('tree-container', {
                width: container.clientWidth,
                height: 700,
                onNodeClick: handleNodeClick,
                readOnly: true
            });
        }

        treeViz.loadData(currentData);
        loading?.classList.add('hidden');

    } catch (err) {
        console.error('Error loading tree data:', err);
        loading?.classList.add('hidden');
        error?.classList.remove('hidden');
        const errorMessage = document.getElementById('tree-error-message');
        if (errorMessage) {
            errorMessage.textContent = err.message || 'ไม่สามารถโหลดข้อมูลได้';
        }
    }
}

function updateStatistics(stats) {
    document.getElementById('stat-total').textContent = stats.total_network || 0;
    document.getElementById('stat-active').textContent = stats.direct_referrals || 0;
    document.getElementById('stat-earnings').textContent = '฿' + (stats.total_earnings || 0).toLocaleString('th-TH');
    document.getElementById('stat-depth').textContent = stats.max_level || 0;
}

function handleNodeClick(nodeData) {
    showDetailModal(nodeData);
}

function showDetailModal(nodeData) {
    const modal = document.getElementById('detail-modal');
    const content = document.getElementById('detail-content');

    if (!modal || !content) return;

    const rankBadge = nodeData.rank ? `
        <div class="flex items-center gap-2 px-3 py-1 rounded-full" style="background: ${nodeData.rank.color}20;">
            <div class="w-3 h-3 rounded-full" style="background: ${nodeData.rank.color};"></div>
            <span class="font-semibold" style="color: ${nodeData.rank.color};">${nodeData.rank.name}</span>
        </div>
    ` : '<span class="text-gray-500">No Rank</span>';

    const statusBadge = nodeData.status === 'active'
        ? '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">✅ Active</span>'
        : '<span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">⏸️ Inactive</span>';

    content.innerHTML = `
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                    ${nodeData.avatar?.text || nodeData.name.charAt(0).toUpperCase()}
                </div>
                <div class="flex-1">
                    <h4 class="text-2xl font-bold text-gray-900 mb-1">${nodeData.name}</h4>
                    <p class="text-gray-600 mb-2">${nodeData.email}</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        ${rankBadge}
                        ${statusBadge}
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm text-blue-600 mb-1">Referral Code</p>
                    <p class="text-xl font-bold text-blue-900">${nodeData.referral_code}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-purple-600 mb-1">Level</p>
                    <p class="text-xl font-bold text-purple-900">L${nodeData.level || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-green-600 mb-1">Direct Referrals</p>
                    <p class="text-xl font-bold text-green-900">${nodeData.direct_children || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg p-4 border border-yellow-200">
                    <p class="text-sm text-yellow-600 mb-1">Total Network</p>
                    <p class="text-xl font-bold text-yellow-900">${nodeData.total_referrals || 0}</p>
                </div>
            </div>

            <!-- Financial Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-green-50 to-teal-50 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-green-600 mb-1">Total Earnings</p>
                    <p class="text-2xl font-bold text-green-900">฿${(nodeData.total_earnings || 0).toLocaleString('th-TH')}</p>
                </div>
                ${nodeData.monthly_sales !== undefined ? `
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm text-blue-600 mb-1">Monthly Sales</p>
                    <p class="text-2xl font-bold text-blue-900">฿${(nodeData.monthly_sales || 0).toLocaleString('th-TH')}</p>
                </div>
                ` : ''}
                ${nodeData.team_sales !== undefined ? `
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-purple-600 mb-1">Team Sales</p>
                    <p class="text-2xl font-bold text-purple-900">฿${(nodeData.team_sales || 0).toLocaleString('th-TH')}</p>
                </div>
                ` : ''}
            </div>

            ${nodeData.rank_points !== undefined ? `
            <!-- Rank Points -->
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-lg p-4 border border-amber-200">
                <p class="text-sm text-amber-600 mb-1">Rank Points</p>
                <div class="flex items-center gap-3">
                    <p class="text-2xl font-bold text-amber-900">${nodeData.rank_points || 0}</p>
                    <div class="flex-1 bg-amber-200 rounded-full h-3">
                        <div class="bg-amber-600 h-3 rounded-full" style="width: ${Math.min((nodeData.rank_points / 1000) * 100, 100)}%"></div>
                    </div>
                </div>
            </div>
            ` : ''}

            <!-- Additional Info -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h5 class="font-semibold text-gray-900 mb-3">Additional Information</h5>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-600">Joined Date</p>
                        <p class="font-medium text-gray-900">${nodeData.created_at}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Tree Depth</p>
                        <p class="font-medium text-gray-900">Level ${nodeData.depth || 0}</p>
                    </div>
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="flex gap-3 pt-4 border-t">
                <a href="/admin/affiliates/${nodeData.id}/edit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-center font-medium">
                    ✏️ Edit
                </a>
                <a href="/admin/affiliates/${nodeData.id}" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-center font-medium">
                    👁️ View Full Details
                </a>
            </div>
        </div>
    `;

    modal.classList.remove('hidden');
}

function closeDetailModal(event) {
    if (!event || event.target.id === 'detail-modal') {
        document.getElementById('detail-modal')?.classList.add('hidden');
    }
}

function exportTreeData() {
    if (!currentData) {
        alert('No data to export');
        return;
    }

    const dataStr = JSON.stringify(currentData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `affiliate-tree-${Date.now()}.json`;
    link.click();
    URL.revokeObjectURL(url);
}

// Tree Visualization Class (same as user version)
class TreeVisualization {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.config = {
            width: options.width || 1200,
            height: options.height || 700,
            nodeSpacing: { horizontal: 180, vertical: 150 },
            avatarRadius: 30,
            onNodeClick: options.onNodeClick || null
        };
        this.init();
    }

    init() {
        d3.select(`#${this.containerId}`).selectAll('svg').remove();

        this.svg = d3.select(`#${this.containerId}`)
            .append('svg')
            .attr('width', this.config.width)
            .attr('height', this.config.height)
            .style('background', '#fafafa');

        this.zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on('zoom', (event) => {
                this.g.attr('transform', event.transform);
            });

        this.svg.call(this.zoom);
        this.g = this.svg.append('g').attr('transform', `translate(${this.config.width / 2}, 80)`);
        this.tree = d3.tree().nodeSize([this.config.nodeSpacing.horizontal, this.config.nodeSpacing.vertical]);
        this.nodeId = 0;
    }

    loadData(data) {
        this.root = d3.hierarchy(data);
        this.root.x0 = 0;
        this.root.y0 = 0;
        if (this.root.children) {
            this.root.children.forEach(d => this.collapse(d));
        }
        this.update(this.root);
        this.centerNode(this.root);
    }

    collapse(d) {
        if (d.children) {
            d._children = d.children;
            d._children.forEach(child => this.collapse(child));
            d.children = null;
        }
    }

    update(source) {
        const duration = 750;
        const treeData = this.tree(this.root);
        const nodes = treeData.descendants();
        const links = treeData.links();

        const node = this.g.selectAll('g.node').data(nodes, d => d.id || (d.id = ++this.nodeId));
        const nodeEnter = node.enter().append('g')
            .attr('class', 'node')
            .attr('transform', `translate(${source.x0},${source.y0})`)
            .style('opacity', 0)
            .on('click', (event, d) => {
                this.toggleChildren(d);
                if (this.config.onNodeClick) this.config.onNodeClick(d.data);
            });

        nodeEnter.append('circle')
            .attr('r', this.config.avatarRadius)
            .style('fill', d => d.data.rank?.color || '#6366f1')
            .style('stroke', '#fff')
            .style('stroke-width', '3px')
            .style('cursor', 'pointer');

        nodeEnter.append('text')
            .attr('dy', '0.35em')
            .style('text-anchor', 'middle')
            .style('fill', '#fff')
            .style('font-weight', 'bold')
            .style('pointer-events', 'none')
            .text(d => d.data.avatar?.text || d.data.name.charAt(0).toUpperCase());

        nodeEnter.append('text')
            .attr('dy', this.config.avatarRadius + 20)
            .style('text-anchor', 'middle')
            .style('font-size', '12px')
            .style('font-weight', '600')
            .text(d => d.data.name.length > 15 ? d.data.name.substring(0, 15) + '...' : d.data.name);

        const nodeUpdate = nodeEnter.merge(node);
        nodeUpdate.transition().duration(duration)
            .attr('transform', d => `translate(${d.x},${d.y})`)
            .style('opacity', 1);

        node.exit().transition().duration(duration)
            .attr('transform', `translate(${source.x},${source.y})`)
            .style('opacity', 0).remove();

        const link = this.g.selectAll('path.link').data(links, d => d.target.id);
        link.enter().insert('path', 'g')
            .attr('class', 'link')
            .attr('d', d => this.diagonal({ x: source.x0, y: source.y0 }, { x: source.x0, y: source.y0 }))
            .style('fill', 'none')
            .style('stroke', '#cbd5e1')
            .style('stroke-width', '2px')
            .transition().duration(duration)
            .attr('d', d => this.diagonal(d.source, d.target));

        link.transition().duration(duration).attr('d', d => this.diagonal(d.source, d.target));
        link.exit().transition().duration(duration)
            .attr('d', d => this.diagonal({ x: source.x, y: source.y }, { x: source.x, y: source.y }))
            .remove();

        nodes.forEach(d => { d.x0 = d.x; d.y0 = d.y; });
    }

    diagonal(s, d) {
        return `M ${s.x},${s.y} C ${s.x},${(s.y + d.y) / 2} ${d.x},${(s.y + d.y) / 2} ${d.x},${d.y}`;
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

    centerNode(d) {
        const scale = 0.7;
        const x = -d.x * scale + this.config.width / 2;
        const y = -d.y * scale + this.config.height / 2;
        this.svg.transition().duration(750)
            .call(this.zoom.transform, d3.zoomIdentity.translate(x, y).scale(scale));
    }

    zoomIn() {
        this.svg.transition().duration(300).call(this.zoom.scaleBy, 1.3);
    }

    zoomOut() {
        this.svg.transition().duration(300).call(this.zoom.scaleBy, 0.7);
    }

    resetZoom() {
        this.centerNode(this.root);
    }

    expandAll() {
        this.root.each(d => { if (d._children) { d.children = d._children; d._children = null; } });
        this.update(this.root);
        this.centerNode(this.root);
    }

    collapseAll() {
        this.root.children?.forEach(d => this.collapse(d));
        this.update(this.root);
        this.centerNode(this.root);
    }

    resize() {
        this.config.width = this.container.clientWidth;
        this.svg.attr('width', this.config.width);
        this.g.attr('transform', `translate(${this.config.width / 2}, 80)`);
        this.update(this.root);
    }
}
</script>
@endsection
