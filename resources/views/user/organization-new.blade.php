@extends('layouts.user')

@section('title', 'ผังสายงาน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold mb-2">🌳 ผังสายงานของคุณ</h1>
                <p class="text-indigo-100 text-sm lg:text-base">แสดงโครงสร้างทีมงานและลูกข่ายแบบ Interactive</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-sm text-indigo-100">รหัสแนะนำของคุณ</p>
                    <p class="text-2xl font-bold">{{ $affiliate->referral_code }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Direct Team -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">ลูกทีมโดยตรง</p>
                    <p class="text-2xl lg:text-3xl font-bold text-indigo-600">{{ $affiliate->children->count() }}</p>
                </div>
                <div class="text-3xl lg:text-4xl">👥</div>
            </div>
        </div>

        <!-- Total Network -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">เครือข่ายทั้งหมด</p>
                    <p class="text-2xl lg:text-3xl font-bold text-blue-600" id="stat-total-network">-</p>
                </div>
                <div class="text-3xl lg:text-4xl">🌐</div>
            </div>
        </div>

        <!-- Network Earnings -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">รายได้เครือข่าย</p>
                    <p class="text-xl lg:text-2xl font-bold text-green-600" id="stat-earnings">-</p>
                </div>
                <div class="text-3xl lg:text-4xl">💰</div>
            </div>
        </div>

        <!-- Organization Depth -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">ความลึกองค์กร</p>
                    <p class="text-2xl lg:text-3xl font-bold text-purple-600" id="stat-depth">-</p>
                    <p class="text-xs text-gray-500">ระดับ</p>
                </div>
                <div class="text-3xl lg:text-4xl">📊</div>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 lg:p-5">
        <div class="flex items-start gap-3">
            <div class="text-2xl lg:text-3xl">💡</div>
            <div class="flex-1">
                <p class="font-semibold text-blue-900 mb-2">คุณสมบัติใหม่:</p>
                <ul class="list-disc list-inside space-y-1 text-sm text-blue-800">
                    <li>แสดงผังแบบ Interactive พร้อม Zoom และ Pan</li>
                    <li>แสดงเป็น Avatar พร้อมข้อมูลสำคัญ</li>
                    <li>คลิกที่สมาชิกเพื่อดูรายละเอียดและขยาย/ย่อ</li>
                    <li>ชี้เมาส์ที่สมาชิกเพื่อดูข้อมูลเพิ่มเติม</li>
                    <li>แสดงเฉพาะลูกข่ายของคุณ {{ $commissionDepth }} ชั้น (ตามการตั้งค่าคอมมิชชั่น)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Organization Chart -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <!-- Toolbar -->
        <div class="border-b border-gray-200 p-4 bg-gray-50">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h2 class="text-xl font-bold text-gray-900">โครงสร้างองค์กร</h2>
                <div class="flex items-center gap-2">
                    <button onclick="treeViz.zoomIn()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        🔍 ซูมเข้า
                    </button>
                    <button onclick="treeViz.zoomOut()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        🔍 ซูมออก
                    </button>
                    <button onclick="treeViz.resetZoom()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        🎯 รีเซ็ต
                    </button>
                    <button onclick="treeViz.expandAll()" class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                        ▼ ขยายทั้งหมด
                    </button>
                    <button onclick="treeViz.collapseAll()" class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm font-medium">
                        ▶ ย่อทั้งหมด
                    </button>
                </div>
            </div>
        </div>

        <!-- Tree Container -->
        <div id="tree-container" class="w-full" style="height: 600px; min-height: 600px;"></div>

        <!-- Loading State -->
        <div id="tree-loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 hidden">
            <div class="text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-indigo-600 mx-auto mb-4"></div>
                <p class="text-gray-600 font-medium">กำลังโหลดข้อมูล...</p>
            </div>
        </div>

        <!-- Error State -->
        <div id="tree-error" class="hidden p-8 text-center">
            <div class="text-6xl mb-4">⚠️</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">เกิดข้อผิดพลาด</h3>
            <p class="text-gray-500 mb-4" id="tree-error-message"></p>
            <button onclick="loadTreeData()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                ลองใหม่อีกครั้ง
            </button>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detail-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" onclick="closeDetailModal(event)">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="sticky top-0 bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold">รายละเอียดสมาชิก</h3>
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

@push('scripts')
<script>
let treeViz = null;
let treeData = null;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadTreeData();
});

// Load tree data from API
async function loadTreeData() {
    const container = document.getElementById('tree-container');
    const loading = document.getElementById('tree-loading');
    const error = document.getElementById('tree-error');

    try {
        // Show loading
        loading?.classList.remove('hidden');
        error?.classList.add('hidden');

        // Fetch data from API
        const response = await fetch('/api/v1/tree/user', {
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

        treeData = result.data;

        // Update statistics
        if (result.stats) {
            updateStatistics(result.stats);
        }

        // Initialize or update tree visualization
        if (!treeViz) {
            treeViz = new TreeVisualization('tree-container', {
                width: container.clientWidth,
                height: 600,
                onNodeClick: handleNodeClick,
                readOnly: true
            });
        }

        treeViz.loadData(treeData);

        // Hide loading
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

// Update statistics
function updateStatistics(stats) {
    const totalNetworkEl = document.getElementById('stat-total-network');
    const earningsEl = document.getElementById('stat-earnings');
    const depthEl = document.getElementById('stat-depth');

    if (totalNetworkEl) totalNetworkEl.textContent = stats.total_network || 0;
    if (earningsEl) earningsEl.textContent = '฿' + (stats.total_earnings || 0).toLocaleString('th-TH');
    if (depthEl) depthEl.textContent = stats.max_level || 0;
}

// Handle node click
function handleNodeClick(nodeData) {
    showDetailModal(nodeData);
}

// Show detail modal
function showDetailModal(nodeData) {
    const modal = document.getElementById('detail-modal');
    const content = document.getElementById('detail-content');

    if (!modal || !content) return;

    // Build detail content
    const rankBadge = nodeData.rank ? `
        <div class="flex items-center gap-2 px-3 py-1 rounded-full" style="background: ${nodeData.rank.color}20;">
            <div class="w-3 h-3 rounded-full" style="background: ${nodeData.rank.color};"></div>
            <span class="font-semibold" style="color: ${nodeData.rank.color};">${nodeData.rank.name}</span>
        </div>
    ` : '<span class="text-gray-500">ไม่มีแรงค์</span>';

    const statusBadge = nodeData.status === 'active'
        ? '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">✅ ใช้งาน</span>'
        : '<span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">⏸️ ไม่ใช้งาน</span>';

    content.innerHTML = `
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                    ${nodeData.avatar?.text || nodeData.name.charAt(0).toUpperCase()}
                </div>
                <div class="flex-1">
                    <h4 class="text-2xl font-bold text-gray-900 mb-1">${nodeData.name}</h4>
                    <p class="text-gray-600 mb-2">${nodeData.email}</p>
                    <div class="flex items-center gap-2">
                        ${rankBadge}
                        ${statusBadge}
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm text-blue-600 mb-1">รหัสแนะนำ</p>
                    <p class="text-xl font-bold text-blue-900">${nodeData.referral_code}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-purple-600 mb-1">ระดับ</p>
                    <p class="text-xl font-bold text-purple-900">L${nodeData.level || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-green-600 mb-1">ลูกทีมโดยตรง</p>
                    <p class="text-xl font-bold text-green-900">${nodeData.direct_children || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-lg p-4 border border-yellow-200">
                    <p class="text-sm text-yellow-600 mb-1">เครือข่ายทั้งหมด</p>
                    <p class="text-xl font-bold text-yellow-900">${nodeData.total_referrals || 0}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-teal-50 rounded-lg p-4 border border-green-200 lg:col-span-2">
                    <p class="text-sm text-green-600 mb-1">รายได้รวม</p>
                    <p class="text-2xl font-bold text-green-900">฿${(nodeData.total_earnings || 0).toLocaleString('th-TH')}</p>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h5 class="font-semibold text-gray-900 mb-3">ข้อมูลเพิ่มเติม</h5>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-gray-600">สมัครเมื่อ</p>
                        <p class="font-medium text-gray-900">${nodeData.created_at}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">ความลึกในผัง</p>
                        <p class="font-medium text-gray-900">ชั้นที่ ${nodeData.depth || 0}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    modal.classList.remove('hidden');
}

// Close detail modal
function closeDetailModal(event) {
    if (!event || event.target.id === 'detail-modal') {
        document.getElementById('detail-modal')?.classList.add('hidden');
    }
}

// Tree Visualization Class (simplified for inline use)
class TreeVisualization {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.config = {
            width: options.width || 1200,
            height: options.height || 600,
            nodeSpacing: { horizontal: 180, vertical: 150 },
            avatarRadius: 30,
            onNodeClick: options.onNodeClick || null
        };
        this.init();
    }

    init() {
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
        const scale = 0.8;
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
}

// Handle window resize
window.addEventListener('resize', () => {
    if (treeViz) {
        treeViz.resize();
    }
});
</script>
@endpush
@endsection
