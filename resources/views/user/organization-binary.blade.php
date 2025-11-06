@extends('layouts.user')

@section('title', 'ผังสายงาน Binary')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold mb-2">🌲 ผังสายงาน Binary</h1>
                <p class="text-purple-100 text-sm lg:text-base">แสดงโครงสร้างทีมงานแบบ Binary Tree (ซ้าย-ขวา)</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-sm text-purple-100">รหัสแนะนำของคุณ</p>
                    <p class="text-2xl font-bold">{{ $affiliate->referral_code }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Left Team -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">ทีมฝั่งซ้าย</p>
                    <p class="text-2xl lg:text-3xl font-bold text-blue-600">{{ $affiliate->binary_left_count ?? 0 }}</p>
                </div>
                <div class="text-3xl lg:text-4xl">👈</div>
            </div>
        </div>

        <!-- Right Team -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">ทีมฝั่งขวา</p>
                    <p class="text-2xl lg:text-3xl font-bold text-green-600">{{ $affiliate->binary_right_count ?? 0 }}</p>
                </div>
                <div class="text-3xl lg:text-4xl">👉</div>
            </div>
        </div>

        <!-- Total Binary Network -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">เครือข่ายทั้งหมด</p>
                    <p class="text-2xl lg:text-3xl font-bold text-purple-600">{{ ($affiliate->binary_left_count ?? 0) + ($affiliate->binary_right_count ?? 0) }}</p>
                </div>
                <div class="text-3xl lg:text-4xl">🌐</div>
            </div>
        </div>

        <!-- Binary Level -->
        <div class="bg-white rounded-xl shadow-md p-4 lg:p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs lg:text-sm text-gray-600 mb-1">ความลึก Binary</p>
                    <p class="text-2xl lg:text-3xl font-bold text-pink-600">{{ $maxBinaryDepth ?? 0 }}</p>
                    <p class="text-xs text-gray-500">ระดับ</p>
                </div>
                <div class="text-3xl lg:text-4xl">📊</div>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-purple-50 rounded-xl border border-purple-200 p-4 lg:p-5">
        <div class="flex items-start gap-3">
            <div class="text-2xl lg:text-3xl">💡</div>
            <div class="flex-1">
                <p class="font-semibold text-purple-900 mb-2">ข้อมูล Binary Tree:</p>
                <ul class="list-disc list-inside space-y-1 text-sm text-purple-800">
                    <li>โครงสร้างแบบ Binary Tree: แต่ละคนมีลูกทีมได้สูงสุด 2 คน (ซ้าย และ ขวา)</li>
                    <li>สีเขียว = ตำแหน่งที่มีลูกทีมอยู่แล้ว | สีเทา = ตำแหน่งว่าง</li>
                    <li>คลิกที่สมาชิกเพื่อดูรายละเอียด</li>
                    <li>ใช้ปุ่ม Zoom และ Pan เพื่อดูผังได้ทุกทิศทาง</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Binary Organization Chart -->
    <div class="bg-white rounded-xl shadow-md p-4 lg:p-6">
        <div class="mb-4 pb-4 border-b border-gray-200">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">โครงสร้าง Binary Tree</h2>
                    <p class="text-sm text-gray-600 mt-1">เริ่มจากคุณและแสดงลูกข่ายแบบ Binary (ซ้าย-ขวา)</p>
                </div>
                <div class="flex items-center gap-2">
                    <button id="zoom-in" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm">
                        🔍+
                    </button>
                    <button id="zoom-out" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm">
                        🔍-
                    </button>
                    <button id="reset-zoom" class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm">
                        รีเซ็ต
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loading-indicator" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
            <p class="text-gray-600 mt-4">กำลังโหลดข้อมูล Binary Tree...</p>
        </div>

        <!-- Tree Container -->
        <div id="binary-tree-container" class="hidden" style="min-height: 600px; overflow: auto;">
            <svg id="binary-tree-svg" class="w-full" style="min-height: 600px;"></svg>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden text-center py-12">
            <div class="text-6xl mb-4">📭</div>
            <p class="text-gray-600 text-lg mb-2">ยังไม่มีข้อมูล Binary Tree</p>
            <p class="text-gray-500 text-sm">เมื่อมีลูกทีมเข้าร่วม ข้อมูลจะแสดงที่นี่</p>
        </div>
    </div>

    <!-- Switch View Link -->
    <div class="text-center">
        <a href="{{ route('user.organization') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
            <span>🌳</span>
            <span>ดูผังสายงานแบบปกติ</span>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
// Binary Tree Visualization
document.addEventListener('DOMContentLoaded', function() {
    const loadingIndicator = document.getElementById('loading-indicator');
    const treeContainer = document.getElementById('binary-tree-container');
    const emptyState = document.getElementById('empty-state');
    const svg = d3.select('#binary-tree-svg');

    // Zoom buttons
    const zoomIn = document.getElementById('zoom-in');
    const zoomOut = document.getElementById('zoom-out');
    const resetZoom = document.getElementById('reset-zoom');

    let zoomBehavior;
    let currentTransform = d3.zoomIdentity;

    // Load binary tree data
    fetchBinaryTreeData();

    function fetchBinaryTreeData() {
        fetch('/api/v1/tree/binary')
            .then(response => response.json())
            .then(result => {
                loadingIndicator.classList.add('hidden');

                if (result.success && result.data) {
                    treeContainer.classList.remove('hidden');
                    renderBinaryTree(result.data);
                } else {
                    emptyState.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error loading binary tree:', error);
                loadingIndicator.classList.add('hidden');
                emptyState.classList.remove('hidden');
            });
    }

    function renderBinaryTree(data) {
        const width = treeContainer.clientWidth || 1200;
        const height = 800;
        const nodeWidth = 160;
        const nodeHeight = 80;
        const levelHeight = 120;

        svg.attr('width', width).attr('height', height);

        // Clear previous content
        svg.selectAll('*').remove();

        // Create main group
        const g = svg.append('g');

        // Setup zoom behavior
        zoomBehavior = d3.zoom()
            .scaleExtent([0.3, 3])
            .on('zoom', (event) => {
                currentTransform = event.transform;
                g.attr('transform', event.transform);
            });

        svg.call(zoomBehavior);

        // Create tree layout
        const treeLayout = d3.tree()
            .size([width - 200, height - 200])
            .separation((a, b) => {
                return a.parent === b.parent ? 1 : 1.2;
            });

        // Convert data to hierarchy
        const root = d3.hierarchy(data);
        const treeData = treeLayout(root);

        // Draw links
        g.selectAll('.link')
            .data(treeData.links())
            .enter()
            .append('path')
            .attr('class', 'link')
            .attr('d', d3.linkVertical()
                .x(d => d.x + 100)
                .y(d => d.y + 100)
            )
            .attr('fill', 'none')
            .attr('stroke', d => d.target.data.empty ? '#e5e7eb' : '#9333ea')
            .attr('stroke-width', 2)
            .attr('stroke-opacity', 0.6);

        // Draw nodes
        const nodes = g.selectAll('.node')
            .data(treeData.descendants())
            .enter()
            .append('g')
            .attr('class', 'node')
            .attr('transform', d => `translate(${d.x + 100},${d.y + 100})`);

        // Node rectangles
        nodes.append('rect')
            .attr('x', -nodeWidth / 2)
            .attr('y', -nodeHeight / 2)
            .attr('width', nodeWidth)
            .attr('height', nodeHeight)
            .attr('rx', 8)
            .attr('fill', d => {
                if (d.data.empty) return '#f3f4f6';
                if (d.depth === 0) return 'url(#gradient-purple)';
                return '#ffffff';
            })
            .attr('stroke', d => {
                if (d.data.empty) return '#d1d5db';
                return d.data.status === 'active' ? '#9333ea' : '#ef4444';
            })
            .attr('stroke-width', 2)
            .style('cursor', d => d.data.empty ? 'default' : 'pointer')
            .on('click', function(event, d) {
                if (!d.data.empty && d.data.id) {
                    showNodeDetails(d.data);
                }
            });

        // Add gradient for root node
        const defs = svg.append('defs');
        const gradient = defs.append('linearGradient')
            .attr('id', 'gradient-purple')
            .attr('x1', '0%')
            .attr('y1', '0%')
            .attr('x2', '100%')
            .attr('y2', '100%');
        gradient.append('stop')
            .attr('offset', '0%')
            .attr('stop-color', '#9333ea');
        gradient.append('stop')
            .attr('offset', '100%')
            .attr('stop-color', '#ec4899');

        // Node text - Name
        nodes.append('text')
            .attr('dy', -10)
            .attr('text-anchor', 'middle')
            .style('font-size', '14px')
            .style('font-weight', 'bold')
            .style('fill', d => d.depth === 0 ? '#ffffff' : (d.data.empty ? '#9ca3af' : '#1f2937'))
            .text(d => {
                const name = d.data.name || 'Unknown';
                return name.length > 20 ? name.substring(0, 18) + '...' : name;
            });

        // Node text - Position label
        nodes.filter(d => !d.data.empty)
            .append('text')
            .attr('dy', 10)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', d => d.depth === 0 ? '#ffffff' : '#6b7280')
            .text(d => {
                if (d.data.binary_position) {
                    return d.data.binary_position === 'left' ? '(ซ้าย)' : '(ขวา)';
                }
                return '';
            });

        // Node text - Count info
        nodes.filter(d => !d.data.empty)
            .append('text')
            .attr('dy', 28)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', d => d.depth === 0 ? '#ffffff' : '#9333ea')
            .text(d => {
                const left = d.data.binary_left_count || 0;
                const right = d.data.binary_right_count || 0;
                return `L:${left} | R:${right}`;
            });

        // Center initial view
        const initialTransform = d3.zoomIdentity.translate(width / 2 - 100, 50);
        svg.call(zoomBehavior.transform, initialTransform);
    }

    // Zoom button handlers
    if (zoomIn) {
        zoomIn.addEventListener('click', () => {
            svg.transition().duration(300).call(zoomBehavior.scaleBy, 1.3);
        });
    }

    if (zoomOut) {
        zoomOut.addEventListener('click', () => {
            svg.transition().duration(300).call(zoomBehavior.scaleBy, 0.7);
        });
    }

    if (resetZoom) {
        resetZoom.addEventListener('click', () => {
            const width = treeContainer.clientWidth || 1200;
            const initialTransform = d3.zoomIdentity.translate(width / 2 - 100, 50);
            svg.transition().duration(500).call(zoomBehavior.transform, initialTransform);
        });
    }

    function showNodeDetails(node) {
        const modal = `
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" onclick="this.remove()">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" onclick="event.stopPropagation()">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">รายละเอียดสมาชิก</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">ชื่อ</p>
                            <p class="font-semibold">${node.name}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">อีเมล</p>
                            <p class="font-semibold">${node.email}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">รหัสแนะนำ</p>
                            <p class="font-semibold">${node.referral_code}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">ตำแหน่ง Binary</p>
                            <p class="font-semibold">${node.binary_position === 'left' ? 'ซ้าย' : 'ขวา'}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-sm text-gray-600">ทีมซ้าย</p>
                                <p class="font-semibold text-blue-600">${node.binary_left_count || 0}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">ทีมขวา</p>
                                <p class="font-semibold text-green-600">${node.binary_right_count || 0}</p>
                            </div>
                        </div>
                        ${node.rank ? `
                        <div>
                            <p class="text-sm text-gray-600">อันดับ</p>
                            <p class="font-semibold" style="color: ${node.rank.color}">${node.rank.name}</p>
                        </div>
                        ` : ''}
                    </div>
                    <button onclick="this.closest('.fixed').remove()" class="mt-6 w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        ปิด
                    </button>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modal);
    }
});
</script>
@endpush
