@extends('layouts.admin-v3')

@section('title', 'ผังสายงาน Affiliate (Interactive)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold mb-2">🌳 ผังสายงาน Affiliate (Interactive)</h1>
                <p class="text-purple-100 text-sm lg:text-base">แสดงโครงสร้างเครือข่าย Affiliate แบบ Google Maps Style</p>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Affiliate Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลือก Affiliate</label>
                <select id="affiliate-select" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
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
                    <button onclick="treeNetwork.zoomIn()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        ➕ Zoom In
                    </button>
                    <button onclick="treeNetwork.zoomOut()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        ➖ Zoom Out
                    </button>
                    <button onclick="treeNetwork.resetView()" class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                        🎯 Reset View
                    </button>
                </div>
            </div>
        </div>

        <!-- Network Container -->
        <div class="relative">
            <div id="tree-container" class="w-full" style="height: 700px; min-height: 700px;"></div>

            <!-- Loading State -->
            <div id="tree-container-loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 hidden">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-indigo-600 mx-auto mb-4"></div>
                    <p class="text-gray-600 font-medium" id="tree-container-progress">กำลังโหลดข้อมูล...</p>
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
            <div id="detail-content" class="p-6"></div>
        </div>
    </div>
</div>

<!-- vis-network CDN -->
<link href="https://unpkg.com/vis-network@9.1.9/styles/vis-network.min.css" rel="stylesheet" type="text/css" />
<script src="https://unpkg.com/vis-network@9.1.9/standalone/umd/vis-network.min.js"></script>

<script>
let treeNetwork = null;
let currentData = null;
let currentAffiliateId = null;
let currentDepth = 10;

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadTreeData();
});

function setupEventListeners() {
    document.getElementById('affiliate-select')?.addEventListener('change', function(e) {
        currentAffiliateId = e.target.value || null;
        loadTreeData();
    });

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
    const loading = document.getElementById('tree-container-loading');
    const error = document.getElementById('tree-error');

    try {
        loading?.classList.remove('hidden');
        error?.classList.add('hidden');

        let apiUrl = '/api/v1/tree/admin';
        if (currentAffiliateId) apiUrl += `/${currentAffiliateId}`;
        apiUrl += `?max_depth=${currentDepth}`;

        const response = await fetch(apiUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            credentials: 'same-origin'
        });

        if (!response.ok) throw new Error('Failed to load tree data');

        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load tree data');

        currentData = result.data;

        if (result.stats) {
            updateStatistics(result.stats);
        }

        if (!treeNetwork) {
            treeNetwork = new TreeNetwork('tree-container', {
                onNodeClick: handleNodeClick,
                readOnly: true,
                physics: true,
                hierarchical: true
            });
        }

        treeNetwork.loadData(currentData);
        loading?.classList.add('hidden');

    } catch (err) {
        console.error('Error loading tree data:', err);
        loading?.classList.add('hidden');
        error?.classList.remove('hidden');
        const errorMessage = document.getElementById('tree-error-message');
        if (errorMessage) errorMessage.textContent = err.message || 'ไม่สามารถโหลดข้อมูลได้';
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

            <div class="flex gap-3 pt-4 border-t">
                <a href="/admin/affiliates/${nodeData.id}/edit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-center font-medium">
                    ✏️ Edit
                </a>
                <a href="/admin/affiliates/${nodeData.id}" class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-center font-medium">
                    👁️ View Details
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

// TreeNetwork class (same as user version)
class TreeNetwork {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.options = options;
        this.network = null;
        this.nodes = null;
        this.edges = null;
        this.init();
    }

    init() {
        const opts = {
            nodes: {
                shape: 'circularImage',
                size: 40,
                borderWidth: 3,
                color: { border: '#6366f1', background: '#ffffff' },
                font: { size: 14, face: 'Arial', color: '#1e293b' },
                shadow: { enabled: true, color: 'rgba(0,0,0,0.2)', size: 10, x: 2, y: 2 }
            },
            edges: {
                width: 2,
                color: { color: '#cbd5e1' },
                smooth: { enabled: true, type: 'cubicBezier', roundness: 0.5 },
                arrows: { to: { enabled: true, scaleFactor: 0.5 } },
                shadow: { enabled: true }
            },
            layout: {
                hierarchical: {
                    enabled: true,
                    direction: 'UD',
                    sortMethod: 'directed',
                    nodeSpacing: 180,
                    levelSeparation: 150
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
                }
            },
            interaction: {
                dragNodes: !this.options.readOnly,
                dragView: true,
                zoomView: true,
                hover: true
            }
        };

        this.network = new vis.Network(this.container, {}, opts);
        this.setupEventListeners();
    }

    setupEventListeners() {
        this.network.on('click', (params) => {
            if (params.nodes.length > 0 && this.options.onNodeClick) {
                const node = this.nodes.find(n => n.id === params.nodes[0]);
                if (node) this.options.onNodeClick(node.data);
            }
        });

        this.network.on('hoverNode', () => {
            this.container.style.cursor = 'pointer';
        });

        this.network.on('blurNode', () => {
            this.container.style.cursor = 'default';
        });

        this.network.on('doubleClick', (params) => {
            if (params.nodes.length > 0) {
                this.network.focus(params.nodes[0], { scale: 1.5, animation: true });
            }
        });
    }

    loadData(data) {
        const { nodes, edges } = this.convertToNetwork(data);
        this.nodes = nodes;
        this.edges = edges;
        this.network.setData({ nodes: nodes, edges: edges });
    }

    convertToNetwork(data, parentId = null, nodes = [], edges = [], level = 0) {
        if (!data) return { nodes, edges };

        const nodeId = data.id || `node_${nodes.length}`;
        const imageUrl = this.createAvatarDataUrl(
            data.avatar?.text || data.name?.charAt(0)?.toUpperCase() || '?',
            this.getNodeColor(data)
        );

        nodes.push({
            id: nodeId,
            label: data.name && data.name.length > 20 ? data.name.substring(0, 20) + '...' : (data.name || 'Unknown'),
            image: imageUrl,
            title: this.createTooltipHtml(data),
            level: level,
            color: { border: this.getNodeColor(data) },
            data: data
        });

        if (parentId !== null) {
            edges.push({ from: parentId, to: nodeId });
        }

        if (data.children) {
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

        ctx.beginPath();
        ctx.arc(50, 50, 48, 0, 2 * Math.PI);
        ctx.fillStyle = color;
        ctx.fill();
        ctx.lineWidth = 4;
        ctx.strokeStyle = '#ffffff';
        ctx.stroke();

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 48px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(letter, 50, 50);

        return canvas.toDataURL();
    }

    createTooltipHtml(data) {
        return `<div style="padding: 8px;">
            <strong>${data.name || 'Unknown'}</strong><br>
            ${data.email || ''}<br>
            รหัส: ${data.referral_code || 'N/A'} | L${data.level || 0}<br>
            ลูกทีม: ${data.direct_children || 0} | รายได้: ฿${(data.total_earnings || 0).toLocaleString()}
        </div>`;
    }

    getNodeColor(data) {
        if (data.status === 'inactive') return '#94a3b8';
        if (data.rank?.color) return data.rank.color;
        const colors = ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'];
        return colors[Math.min(data.level || 0, colors.length - 1)];
    }

    zoomIn() {
        const scale = this.network.getScale();
        this.network.moveTo({ scale: scale * 1.3, animation: true });
    }

    zoomOut() {
        const scale = this.network.getScale();
        this.network.moveTo({ scale: scale * 0.7, animation: true });
    }

    resetView() {
        this.network.fit({ animation: true });
    }
}

window.addEventListener('resize', () => {
    if (treeNetwork?.network) {
        treeNetwork.network.redraw();
    }
});
</script>
@endsection
