@extends('layouts.admin')

@section('title', 'ผังสายงาน MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-sitemap text-purple-600 dark:text-purple-400"></i>
                ผังสายงาน MLM
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $member->user->name }} ({{ $member->member_code }})</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.mlm.members.show', $member) }}"
               class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-eye"></i>
                ดูข้อมูล
            </a>
            <a href="{{ route('admin.mlm.members.index') }}"
               class="bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white px-6 py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                กลับ
            </a>
        </div>
    </div>

    <!-- Member Info -->
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 rounded-xl shadow-lg p-6 mb-6 text-white">
        <div class="flex flex-col md:flex-row items-start md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center text-2xl font-bold shadow-lg">
                    {{ strtoupper(substr($member->user->name, 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-xl font-bold">{{ $member->user->name }}</h2>
                    <p class="text-sm opacity-90">{{ $member->member_code }} • {{ $member->plan->display_name }}</p>
                    <div class="flex flex-wrap items-center gap-3 mt-2 text-sm">
                        <span class="flex items-center gap-1">
                            <i class="fas fa-chart-line"></i>
                            PV: {{ number_format($member->total_pv, 2) }}
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-dollar-sign"></i>
                            รายได้: ฿{{ number_format($member->total_earnings, 2) }}
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-users"></i>
                            ทีม: {{ number_format($member->total_team_members) }} คน
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Tree Type Selector -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <i class="fas fa-stream text-purple-600 dark:text-purple-400"></i>
                    ประเภทผัง:
                </label>
                <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <a href="{{ route('admin.mlm.members.genealogy', ['member' => $member, 'type' => 'unilevel']) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1 {{ $treeType === 'unilevel' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        <i class="fas fa-network-wired"></i>
                        Unilevel
                    </a>
                    <a href="{{ route('admin.mlm.members.genealogy', ['member' => $member, 'type' => 'binary']) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1 {{ $treeType === 'binary' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        <i class="fas fa-code-branch"></i>
                        Binary
                    </a>
                </div>
            </div>

            <!-- Depth Selector -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <i class="fas fa-layer-group text-purple-600 dark:text-purple-400"></i>
                    ความลึก:
                </label>
                <select id="depth-selector" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:border-transparent transition text-sm" onchange="updateDepth(this.value)">
                    <option value="3" {{ request('depth', 5) == 3 ? 'selected' : '' }}>3 ระดับ</option>
                    <option value="5" {{ request('depth', 5) == 5 ? 'selected' : '' }}>5 ระดับ</option>
                    <option value="7" {{ request('depth', 5) == 7 ? 'selected' : '' }}>7 ระดับ</option>
                    <option value="10" {{ request('depth', 5) == 10 ? 'selected' : '' }}>10 ระดับ</option>
                </select>
            </div>

            <!-- Zoom Controls -->
            <div class="flex items-center gap-2">
                <button onclick="zoomIn()" class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all duration-200" title="ซูมเข้า">
                    <i class="fas fa-search-plus text-lg"></i>
                </button>
                <button onclick="resetZoom()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 transition-all duration-200 flex items-center gap-1">
                    <i class="fas fa-redo-alt"></i>
                    รีเซ็ต
                </button>
                <button onclick="zoomOut()" class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all duration-200" title="ซูมออก">
                    <i class="fas fa-search-minus text-lg"></i>
                </button>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-circle text-emerald-500"></i>
                    <span class="text-gray-600 dark:text-gray-400">Active</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-circle text-gray-400"></i>
                    <span class="text-gray-600 dark:text-gray-400">Inactive</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-circle text-red-500"></i>
                    <span class="text-gray-600 dark:text-gray-400">Suspended</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tree Container -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700" style="height: 800px;">
        <div id="genealogy-tree" class="w-full h-full relative bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
            <!-- Loading State -->
            <div id="tree-loading" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm">
                <div class="text-center">
                    <div class="relative w-12 h-12 mx-auto mb-4">
                        <div class="absolute inset-0 border-4 border-purple-200 dark:border-purple-900 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-transparent border-t-purple-600 dark:border-t-purple-400 rounded-full animate-spin"></div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 font-medium flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin text-purple-600 dark:text-purple-400"></i>
                        กำลังโหลดผังสายงาน...
                    </p>
                </div>
            </div>

            <!-- Tree will be rendered here -->
            <svg id="tree-svg" class="w-full h-full"></svg>

            <!-- Pan/Zoom Instructions -->
            <div class="absolute bottom-4 right-4 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg p-4 text-xs text-gray-700 dark:text-gray-300 shadow-xl border border-gray-200 dark:border-gray-700">
                <p class="font-bold mb-2 flex items-center gap-2 text-sm text-purple-600 dark:text-purple-400">
                    <i class="fas fa-info-circle"></i>
                    การใช้งาน
                </p>
                <ul class="space-y-1.5">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-hand-paper text-blue-600 dark:text-blue-400"></i>
                        ลากเพื่อเลื่อนดู
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-mouse text-purple-600 dark:text-purple-400"></i>
                        Scroll เพื่อซูม
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-mouse-pointer text-pink-600 dark:text-pink-400"></i>
                        คลิกโหนดเพื่อดูรายละเอียด
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tree Statistics -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">จำนวนโหนดในผัง</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white" id="node-count">-</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-full">
                    <i class="fas fa-users text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        @if($treeType === 'binary')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Left Leg</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($member->left_leg_members) }}</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-full">
                    <i class="fas fa-arrow-left text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Right Leg</p>
                    <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ number_format($member->right_leg_members) }}</p>
                </div>
                <div class="bg-pink-100 dark:bg-pink-900/30 p-3 rounded-full">
                    <i class="fas fa-arrow-right text-2xl text-pink-600 dark:text-pink-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Balance</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        @php
                            $diff = abs($member->left_leg_members - $member->right_leg_members);
                            $total = $member->left_leg_members + $member->right_leg_members;
                            $balance = $total > 0 ? (1 - ($diff / $total)) * 100 : 100;
                        @endphp
                        {{ number_format($balance, 0) }}%
                    </p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-full">
                    <i class="fas fa-balance-scale text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ผู้แนะนำโดยตรง</p>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($member->total_direct_referrals) }}</p>
                </div>
                <div class="bg-emerald-100 dark:bg-emerald-900/30 p-3 rounded-full">
                    <i class="fas fa-user-plus text-2xl text-emerald-600 dark:text-emerald-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ระดับสูงสุด</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400" id="max-depth">-</p>
                </div>
                <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-full">
                    <i class="fas fa-layer-group text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ทีมทั้งหมด</p>
                    <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">{{ number_format($member->total_team_members) }}</p>
                </div>
                <div class="bg-pink-100 dark:bg-pink-900/30 p-3 rounded-full">
                    <i class="fas fa-users text-2xl text-pink-600 dark:text-pink-400"></i>
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

    // Calculate max depth
    const maxDepth = Math.max(...nodes.map(n => n.depth));
    const maxDepthElement = document.getElementById('max-depth');
    if (maxDepthElement) {
        maxDepthElement.textContent = maxDepth;
    }

    // For now, display a message that tree visualization is being loaded
    const message = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    message.setAttribute('x', '50%');
    message.setAttribute('y', '50%');
    message.setAttribute('text-anchor', 'middle');
    message.setAttribute('class', 'text-xl font-bold fill-gray-700 dark:fill-gray-300');
    message.textContent = `พบ ${nodes.length} สมาชิกในผัง (${treeType === 'unilevel' ? 'Unilevel' : 'Binary'})`;
    svg.appendChild(message);

    // Add info text
    const info = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    info.setAttribute('x', '50%');
    info.setAttribute('y', '56%');
    info.setAttribute('text-anchor', 'middle');
    info.setAttribute('class', 'text-sm fill-gray-500 dark:fill-gray-400');
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
    svg.style.transition = 'transform 0.3s ease';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    renderTree();
});
</script>
@endsection
