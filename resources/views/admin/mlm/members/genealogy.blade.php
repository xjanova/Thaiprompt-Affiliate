@extends('layouts.admin')

@section('title', 'ผังสายงาน MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">ผังสายงาน MLM</h1>
            <p class="text-gray-600 mt-1">{{ $member->user->name }} ({{ $member->member_code }})</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.mlm.members.show', $member) }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                ดูข้อมูล
            </a>
            <a href="{{ route('admin.mlm.members.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>

    <!-- Member Info -->
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($member->user->name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold">{{ $member->user->name }}</h2>
                    <p class="text-sm opacity-90">{{ $member->member_code }} • {{ $member->plan->display_name }}</p>
                    <div class="flex items-center gap-4 mt-2 text-sm">
                        <span>PV: {{ number_format($member->total_pv, 2) }}</span>
                        <span>•</span>
                        <span>รายได้: ฿{{ number_format($member->total_earnings, 2) }}</span>
                        <span>•</span>
                        <span>ทีม: {{ number_format($member->total_team_members) }} คน</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="bg-white rounded-xl shadow-lg p-4 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <!-- Tree Type Selector -->
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">ประเภทผัง:</label>
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <a href="{{ route('admin.mlm.members.genealogy', ['member' => $member, 'type' => 'unilevel']) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $treeType === 'unilevel' ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-200' }}">
                        Unilevel
                    </a>
                    <a href="{{ route('admin.mlm.members.genealogy', ['member' => $member, 'type' => 'binary']) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $treeType === 'binary' ? 'bg-purple-600 text-white' : 'text-gray-700 hover:bg-gray-200' }}">
                        Binary
                    </a>
                </div>
            </div>

            <!-- Depth Selector -->
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">ความลึก:</label>
                <select id="depth-selector" class="border-gray-300 rounded-lg text-sm" onchange="updateDepth(this.value)">
                    <option value="3" {{ request('depth', 5) == 3 ? 'selected' : '' }}>3 ระดับ</option>
                    <option value="5" {{ request('depth', 5) == 5 ? 'selected' : '' }}>5 ระดับ</option>
                    <option value="7" {{ request('depth', 5) == 7 ? 'selected' : '' }}>7 ระดับ</option>
                    <option value="10" {{ request('depth', 5) == 10 ? 'selected' : '' }}>10 ระดับ</option>
                </select>
            </div>

            <!-- Zoom Controls -->
            <div class="flex items-center gap-2">
                <button onclick="zoomIn()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                    </svg>
                </button>
                <button onclick="resetZoom()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 transition-colors">
                    รีเซ็ต
                </button>
                <button onclick="zoomOut()" class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                    </svg>
                </button>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-4 text-xs">
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-gray-600">Active</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                    <span class="text-gray-600">Inactive</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-gray-600">Suspended</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tree Container -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden" style="height: 800px;">
        <div id="genealogy-tree" class="w-full h-full relative bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Loading State -->
            <div id="tree-loading" class="absolute inset-0 flex items-center justify-center">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">กำลังโหลดผังสายงาน...</p>
                </div>
            </div>

            <!-- Tree will be rendered here -->
            <svg id="tree-svg" class="w-full h-full"></svg>

            <!-- Pan/Zoom Instructions -->
            <div class="absolute bottom-4 right-4 bg-white bg-opacity-90 rounded-lg p-3 text-xs text-gray-600 shadow-lg">
                <p class="font-semibold mb-1">การใช้งาน:</p>
                <ul class="space-y-1">
                    <li>• ลากเพื่อเลื่อนดู</li>
                    <li>• Scroll เพื่อซูม</li>
                    <li>• คลิกโหนดเพื่อดูรายละเอียด</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tree Statistics -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">จำนวนโหนดในผัง</p>
                    <p class="text-2xl font-bold text-gray-800" id="node-count">-</p>
                </div>
                <div class="text-blue-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        @if($treeType === 'binary')
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Left Leg</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($member->left_leg_members) }}</p>
                </div>
                <div class="text-blue-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Right Leg</p>
                    <p class="text-2xl font-bold text-pink-600">{{ number_format($member->right_leg_members) }}</p>
                </div>
                <div class="text-pink-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Balance</p>
                    <p class="text-2xl font-bold text-purple-600">
                        @php
                            $diff = abs($member->left_leg_members - $member->right_leg_members);
                            $total = $member->left_leg_members + $member->right_leg_members;
                            $balance = $total > 0 ? (1 - ($diff / $total)) * 100 : 100;
                        @endphp
                        {{ number_format($balance, 0) }}%
                    </p>
                </div>
                <div class="text-purple-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
            </div>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">ผู้แนะนำโดยตรง</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($member->total_direct_referrals) }}</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">ระดับสูงสุด</p>
                    <p class="text-2xl font-bold text-purple-600" id="max-depth">-</p>
                </div>
                <div class="text-purple-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">ทีมทั้งหมด</p>
                    <p class="text-2xl font-bold text-pink-600">{{ number_format($member->total_team_members) }}</p>
                </div>
                <div class="text-pink-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
// Tree data from backend
const treeData = @json($treeData);
const treeType = '{{ $treeType }}';
const memberId = '{{ $member->id }}';

// Simple tree renderer
let scale = 1;
let translateX = 0;
let translateY = 0;

function renderTree() {
    const svg = document.getElementById('tree-svg');
    const loading = document.getElementById('tree-loading');

    // Hide loading
    loading.style.display = 'none';

    // Simple tree rendering
    const nodes = flattenTree(treeData);
    document.getElementById('node-count').textContent = nodes.length;

    // For now, display a message that tree visualization is being loaded
    const message = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    message.setAttribute('x', '50%');
    message.setAttribute('y', '50%');
    message.setAttribute('text-anchor', 'middle');
    message.setAttribute('class', 'text-lg fill-gray-600');
    message.textContent = `พบ ${nodes.length} สมาชิกในผัง (${treeType === 'unilevel' ? 'Unilevel' : 'Binary'})`;
    svg.appendChild(message);

    // Add info text
    const info = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    info.setAttribute('x', '50%');
    info.setAttribute('y', '55%');
    info.setAttribute('text-anchor', 'middle');
    info.setAttribute('class', 'text-sm fill-gray-500');
    info.textContent = 'Tree visualization with pan/zoom controls';
    svg.appendChild(info);
}

function flattenTree(node, depth = 0) {
    let nodes = [{ ...node, depth }];
    if (node.children) {
        node.children.forEach(child => {
            nodes = nodes.concat(flattenTree(child, depth + 1));
        });
    }
    if (node.left) {
        nodes = nodes.concat(flattenTree(node.left, depth + 1));
    }
    if (node.right) {
        nodes = nodes.concat(flattenTree(node.right, depth + 1));
    }
    return nodes;
}

function updateDepth(depth) {
    window.location.href = `{{ route('admin.mlm.members.genealogy', $member) }}?type={{ $treeType }}&depth=${depth}`;
}

function zoomIn() {
    scale *= 1.2;
    applyTransform();
}

function zoomOut() {
    scale /= 1.2;
    applyTransform();
}

function resetZoom() {
    scale = 1;
    translateX = 0;
    translateY = 0;
    applyTransform();
}

function applyTransform() {
    const svg = document.getElementById('tree-svg');
    svg.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    renderTree();
});
</script>
@endsection
