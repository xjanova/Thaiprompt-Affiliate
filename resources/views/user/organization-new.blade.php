@extends('layouts.user')

@section('title', 'ผังสายงาน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold mb-2">🌳 ผังสายงานของคุณ</h1>
                <p class="text-indigo-100 text-sm lg:text-base">แสดงโครงสร้างทีมงานแบบ Google Maps Style - ลากเลื่อนและซูมได้อิสระ</p>
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
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">ลูกทีมโดยตรง</p>
                    <p class="text-2xl lg:text-3xl font-bold text-indigo-600">{{ $affiliate->children->count() }}</p>
                </div>
                <div class="text-3xl lg:text-4xl">👥</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">เครือข่ายทั้งหมด</p>
                    <p class="text-2xl lg:text-3xl font-bold text-blue-600" id="stat-total-network">-</p>
                </div>
                <div class="text-3xl lg:text-4xl">🌐</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">รายได้เครือข่าย</p>
                    <p class="text-xl lg:text-2xl font-bold text-green-600" id="stat-earnings">-</p>
                </div>
                <div class="text-3xl lg:text-4xl">💰</div>
            </div>
        </div>

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
                <p class="font-semibold text-blue-900 mb-2">วิธีใช้งาน (สไตล์ Google Maps):</p>
                <ul class="list-disc list-inside space-y-1 text-sm text-blue-800">
                    <li><strong>ลากเลื่อน:</strong> คลิกค้างแล้วลากเพื่อเลื่อนดูผัง</li>
                    <li><strong>ซูม:</strong> ใช้ scroll mouse หรือปุ่ม +/- เพื่อซูม</li>
                    <li><strong>ชี้เมาส์:</strong> ชี้ที่สมาชิกเพื่อดูข้อมูลย่อ</li>
                    <li><strong>คลิกเดียว:</strong> คลิกเพื่อดูรายละเอียดเต็ม</li>
                    <li><strong>ดับเบิลคลิก:</strong> โฟกัสไปที่สมาชิกคนนั้น</li>
                    <li>แสดงเฉพาะลูกข่ายของคุณ {{ $commissionDepth }} ชั้น</li>
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
                    <button onclick="treeNetwork.zoomIn()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        ➕ ซูมเข้า
                    </button>
                    <button onclick="treeNetwork.zoomOut()" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        ➖ ซูมออก
                    </button>
                    <button onclick="treeNetwork.resetView()" class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                        🎯 รีเซ็ตมุมมอง
                    </button>
                </div>
            </div>
        </div>

        <!-- Network Container -->
        <div class="relative">
            <div id="tree-container" class="w-full" style="height: 600px; min-height: 600px;"></div>

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
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="sticky top-0 bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold">รายละเอียดสมาชิก</h3>
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

@push('scripts')
<script>
let treeNetwork = null;

document.addEventListener('DOMContentLoaded', function() {
    loadTreeData();
});

async function loadTreeData() {
    const container = document.getElementById('tree-container');
    const loading = document.getElementById('tree-container-loading');
    const error = document.getElementById('tree-error');

    try {
        loading?.classList.remove('hidden');
        error?.classList.add('hidden');

        const response = await fetch('/api/v1/tree/user', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            credentials: 'same-origin'
        });

        if (!response.ok) throw new Error('Failed to load tree data');

        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load tree data');

        // Update statistics
        if (result.stats) {
            updateStatistics(result.stats);
        }

        // Initialize network
        if (!treeNetwork) {
            treeNetwork = new TreeNetwork('tree-container', {
                onNodeClick: handleNodeClick,
                readOnly: true,
                physics: true,
                hierarchical: true
            });
        }

        treeNetwork.loadData(result.data);

    } catch (err) {
        console.error('Error loading tree data:', err);
        loading?.classList.add('hidden');
        error?.classList.remove('hidden');
        const errorMessage = document.getElementById('tree-error-message');
        if (errorMessage) errorMessage.textContent = err.message || 'ไม่สามารถโหลดข้อมูลได้';
    }
}

function updateStatistics(stats) {
    const totalNetworkEl = document.getElementById('stat-total-network');
    const earningsEl = document.getElementById('stat-earnings');
    const depthEl = document.getElementById('stat-depth');

    if (totalNetworkEl) totalNetworkEl.textContent = stats.total_network || 0;
    if (earningsEl) earningsEl.textContent = '฿' + (stats.total_earnings || 0).toLocaleString('th-TH');
    if (depthEl) depthEl.textContent = stats.max_level || 0;
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
    ` : '<span class="text-gray-500">ไม่มีแรงค์</span>';

    const statusBadge = nodeData.status === 'active'
        ? '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">✅ ใช้งาน</span>'
        : '<span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">⏸️ ไม่ใช้งาน</span>';

    content.innerHTML = `
        <div class="space-y-6">
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

function closeDetailModal(event) {
    if (!event || event.target.id === 'detail-modal') {
        document.getElementById('detail-modal')?.classList.add('hidden');
    }
}

// TreeNetwork class (inline)
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
            label: data.name.length > 20 ? data.name.substring(0, 20) + '...' : data.name,
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
            <strong>${data.name}</strong><br>
            ${data.email}<br>
            รหัส: ${data.referral_code} | L${data.level || 0}<br>
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
@endpush
@endsection
