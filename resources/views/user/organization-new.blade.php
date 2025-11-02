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

        const response = await fetch('{{ route('user.organization.tree-data') }}', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
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

        // Load data (even if user has no children, they should see themselves)
        if (result.data) {
            treeNetwork.loadData(result.data);
        }

        // Hide loading state
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
        <div class="flex items-center gap-2 px-4 py-2 rounded-full shadow-lg" style="background: linear-gradient(135deg, ${nodeData.rank.color}, ${nodeData.rank.color}dd);">
            <div class="w-3 h-3 rounded-full bg-white shadow-inner"></div>
            <span class="font-bold text-white">⭐ ${nodeData.rank.name}</span>
        </div>
    ` : '<span class="px-4 py-2 bg-gray-200 text-gray-600 rounded-full text-sm font-medium">ไม่มีแรงค์</span>';

    const statusBadge = nodeData.status === 'active'
        ? '<span class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full text-sm font-bold shadow-lg">✅ ใช้งาน</span>'
        : '<span class="px-4 py-2 bg-gradient-to-r from-gray-400 to-gray-500 text-white rounded-full text-sm font-bold shadow-lg">⏸️ ไม่ใช้งาน</span>';

    const avatarColor = nodeData.rank?.color || '#6366f1';
    const avatarLetter = nodeData.avatar?.text || nodeData.name.charAt(0).toUpperCase();

    content.innerHTML = `
        <div class="space-y-6">
            <!-- Header Section with 3D Avatar -->
            <div class="flex items-center gap-6 pb-6 border-b-2 border-gray-100">
                <div class="relative">
                    <div class="w-24 h-24 rounded-full flex items-center justify-center text-white text-4xl font-bold shadow-2xl"
                         style="background: linear-gradient(135deg, ${avatarColor}, ${avatarColor}dd);
                                box-shadow: 0 10px 30px rgba(0,0,0,0.3), 0 0 0 4px white, 0 0 0 6px ${avatarColor}40;">
                        ${avatarLetter}
                    </div>
                    <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                        ${nodeData.level || 0}
                    </div>
                </div>
                <div class="flex-1">
                    <h4 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                        ${nodeData.name}
                    </h4>
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        ${rankBadge}
                        ${statusBadge}
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg font-semibold">
                            🎫 ${nodeData.referral_code}
                        </span>
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-lg font-semibold">
                            📅 ${nodeData.created_at}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Marketing Performance Section -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border-2 border-blue-200">
                <h5 class="font-bold text-blue-900 mb-4 text-lg flex items-center gap-2">
                    📊 ประสิทธิภาพการตลาด
                </h5>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/80 backdrop-blur rounded-xl p-4 shadow-lg border border-blue-200">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm text-blue-600 font-medium">ลูกทีมโดยตรง</p>
                            <span class="text-2xl">👥</span>
                        </div>
                        <p class="text-3xl font-bold text-blue-900">${nodeData.direct_children || 0}</p>
                        <p class="text-xs text-blue-600 mt-1">คนที่สมัครผ่านคุณ</p>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-xl p-4 shadow-lg border border-indigo-200">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm text-indigo-600 font-medium">เครือข่ายทั้งหมด</p>
                            <span class="text-2xl">🌐</span>
                        </div>
                        <p class="text-3xl font-bold text-indigo-900">${nodeData.total_referrals || 0}</p>
                        <p class="text-xs text-indigo-600 mt-1">ขนาดทีมทั้งหมด</p>
                    </div>
                </div>
            </div>

            <!-- Revenue Section -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border-2 border-green-200">
                <h5 class="font-bold text-green-900 mb-4 text-lg flex items-center gap-2">
                    💰 รายได้และผลตอบแทน
                </h5>
                <div class="bg-white/80 backdrop-blur rounded-xl p-6 shadow-lg border border-green-200 text-center">
                    <p class="text-sm text-green-600 font-medium mb-2">รายได้รวมทั้งหมด</p>
                    <p class="text-4xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
                        ฿${(nodeData.total_earnings || 0).toLocaleString('th-TH')}
                    </p>
                    <div class="mt-4 pt-4 border-t border-green-200">
                        <p class="text-xs text-green-600">รายได้เฉลี่ยต่อคน: <span class="font-bold">฿${nodeData.total_referrals > 0 ? Math.round((nodeData.total_earnings || 0) / nodeData.total_referrals).toLocaleString('th-TH') : '0'}</span></p>
                    </div>
                </div>
            </div>

            <!-- Team Structure Insight -->
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5 border-2 border-purple-200">
                <h5 class="font-bold text-purple-900 mb-4 text-lg flex items-center gap-2">
                    🎯 โครงสร้างทีม
                </h5>
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white/80 backdrop-blur rounded-lg p-3 shadow text-center border border-purple-200">
                        <p class="text-2xl mb-1">📊</p>
                        <p class="text-xs text-purple-600 mb-1">ระดับในผัง</p>
                        <p class="text-lg font-bold text-purple-900">L${nodeData.level || 0}</p>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-lg p-3 shadow text-center border border-purple-200">
                        <p class="text-2xl mb-1">🏗️</p>
                        <p class="text-xs text-purple-600 mb-1">ความลึก</p>
                        <p class="text-lg font-bold text-purple-900">ชั้น ${nodeData.depth || 0}</p>
                    </div>
                    <div class="bg-white/80 backdrop-blur rounded-lg p-3 shadow text-center border border-purple-200">
                        <p class="text-2xl mb-1">🎖️</p>
                        <p class="text-xs text-purple-600 mb-1">ระดับแรงค์</p>
                        <p class="text-lg font-bold text-purple-900">${nodeData.rank?.level || 0}</p>
                    </div>
                </div>
            </div>

            <!-- Membership Retention Status -->
            ${nodeData.retention ? `
                <div class="bg-gradient-to-br from-rose-50 to-pink-50 rounded-xl p-5 border-2 border-rose-200">
                    <h5 class="font-bold text-rose-900 mb-4 text-lg flex items-center gap-2">
                        🎯 สถานะการรักษายอด
                    </h5>
                    <div class="space-y-3">
                        <!-- Days Remaining -->
                        <div class="bg-white/80 backdrop-blur rounded-xl p-4 shadow-lg border border-rose-200">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">⏰</span>
                                    <div>
                                        <p class="text-sm text-rose-600 font-medium">วันที่เหลือ</p>
                                        <p class="text-xs text-rose-500">จนถึงวันต่ออายุ</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-3xl font-bold ${nodeData.retention.health_color === 'red' ? 'text-red-600' : nodeData.retention.health_color === 'orange' ? 'text-orange-600' : nodeData.retention.health_color === 'yellow' ? 'text-yellow-600' : 'text-green-600'}">
                                        ${nodeData.retention.days_remaining}
                                    </p>
                                    <p class="text-sm font-medium text-gray-600">วัน</p>
                                </div>
                            </div>
                            ${nodeData.retention.next_renewal_date ? `
                                <p class="text-xs text-center text-rose-600 bg-rose-50 rounded-lg py-2">
                                    📅 ต่ออายุวันที่: ${nodeData.retention.next_renewal_date}
                                </p>
                            ` : ''}
                        </div>

                        <!-- Points Progress -->
                        <div class="bg-white/80 backdrop-blur rounded-xl p-4 shadow-lg border border-rose-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm text-rose-600 font-medium">📊 ความคืบหน้าคะแนน</p>
                                <p class="text-sm font-bold text-rose-900">${Math.round(nodeData.retention.points_percentage)}%</p>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 mb-2 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 ${nodeData.retention.points_percentage >= 100 ? 'bg-gradient-to-r from-green-500 to-emerald-600' : nodeData.retention.points_percentage >= 70 ? 'bg-gradient-to-r from-yellow-500 to-orange-500' : 'bg-gradient-to-r from-red-500 to-rose-600'}"
                                     style="width: ${Math.min(100, nodeData.retention.points_percentage)}%">
                                </div>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-rose-600">คะแนนปัจจุบัน: <strong>${nodeData.retention.current_points.toLocaleString('th-TH')}</strong></span>
                                <span class="text-rose-600">เป้าหมาย: <strong>${nodeData.retention.required_points.toLocaleString('th-TH')}</strong></span>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="text-center">
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full font-bold ${nodeData.retention.status === 'active' ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-gray-400 to-gray-500'} text-white shadow-lg">
                                ${nodeData.retention.status === 'active' ? '✅ การรักษายอดปกติ' : '⚠️ การรักษายอดหมดอายุ'}
                            </span>
                        </div>
                    </div>
                </div>
            ` : ''}

            <!-- Marketing Tips -->
            <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl p-5 border-2 border-yellow-200">
                <h5 class="font-bold text-orange-900 mb-3 text-lg flex items-center gap-2">
                    💡 คำแนะนำสำหรับแม่ทีม
                </h5>
                <div class="space-y-2 text-sm">
                    ${nodeData.retention && nodeData.retention.days_remaining <= 7 && nodeData.retention.days_remaining > 0 ? `
                        <div class="flex items-start gap-2 bg-red-50 p-3 rounded-lg shadow-sm border border-red-200">
                            <span>🚨</span>
                            <p class="text-red-800 font-semibold">การรักษายอดใกล้หมดอายุ (${nodeData.retention.days_remaining} วัน) - ควรติดตามและช่วยเหลือด่วน!</p>
                        </div>
                    ` : ''}
                    ${nodeData.retention && nodeData.retention.points_percentage < 50 && nodeData.retention.days_remaining > 0 ? `
                        <div class="flex items-start gap-2 bg-orange-50 p-3 rounded-lg shadow-sm border border-orange-200">
                            <span>📉</span>
                            <p class="text-orange-800 font-semibold">คะแนนการรักษายอดต่ำ (${Math.round(nodeData.retention.points_percentage)}%) - ควรช่วยวางแผนการทำยอด</p>
                        </div>
                    ` : ''}
                    ${nodeData.direct_children === 0 ? `
                        <div class="flex items-start gap-2 bg-white/80 backdrop-blur p-3 rounded-lg shadow-sm border border-yellow-200">
                            <span>🎯</span>
                            <p class="text-orange-800">สมาชิกท่านนี้ยังไม่มีลูกทีม - เหมาะสำหรับการโค้ชด้านการหาลูกข่าย</p>
                        </div>
                    ` : ''}
                    ${nodeData.direct_children > 0 && nodeData.direct_children < 3 ? `
                        <div class="flex items-start gap-2 bg-white/80 backdrop-blur p-3 rounded-lg shadow-sm border border-yellow-200">
                            <span>🌱</span>
                            <p class="text-orange-800">กำลังสร้างทีม - ควรให้กำลังใจและช่วยฝึกทักษะการปิดการขาย</p>
                        </div>
                    ` : ''}
                    ${nodeData.direct_children >= 3 ? `
                        <div class="flex items-start gap-2 bg-white/80 backdrop-blur p-3 rounded-lg shadow-sm border border-yellow-200">
                            <span>⭐</span>
                            <p class="text-orange-800">มีทีมที่แข็งแรง - ควรฝึกทักษะการเป็นผู้นำและดูแลทีม</p>
                        </div>
                    ` : ''}
                    ${nodeData.total_referrals > 10 ? `
                        <div class="flex items-start gap-2 bg-white/80 backdrop-blur p-3 rounded-lg shadow-sm border border-yellow-200">
                            <span>🏆</span>
                            <p class="text-orange-800">เครือข่ายใหญ่ - สามารถเป็นพี่เลี้ยงให้กับสมาชิกใหม่ได้</p>
                        </div>
                    ` : ''}
                    ${nodeData.status === 'inactive' ? `
                        <div class="flex items-start gap-2 bg-red-50 p-3 rounded-lg shadow-sm border border-red-200">
                            <span>⚠️</span>
                            <p class="text-red-800 font-semibold">สมาชิกไม่ใช้งาน - ควรติดตามและช่วยเหลือด่วน!</p>
                        </div>
                    ` : ''}
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
                size: 50,
                borderWidth: 4,
                color: { border: '#6366f1', background: '#ffffff' },
                font: {
                    size: 15,
                    face: 'Inter, system-ui, sans-serif',
                    color: '#1e293b',
                    bold: '600'
                },
                shadow: {
                    enabled: true,
                    color: 'rgba(99, 102, 241, 0.4)',
                    size: 20,
                    x: 0,
                    y: 4
                }
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
        const nodeLabel = data.name || 'Unknown';
        const imageUrl = this.createAvatarDataUrl(
            data.avatar?.text || nodeLabel.charAt(0)?.toUpperCase() || '?',
            this.getNodeColor(data)
        );

        nodes.push({
            id: nodeId,
            label: nodeLabel.length > 20 ? nodeLabel.substring(0, 20) + '...' : nodeLabel,
            image: imageUrl,
            title: this.createTooltipHtml(data),
            level: level,
            color: { border: this.getNodeColor(data) },
            data: data
        });

        if (parentId !== null) {
            edges.push({ from: parentId, to: nodeId });
        }

        // Process children if they exist and is an array
        if (data.children && Array.isArray(data.children) && data.children.length > 0) {
            data.children.forEach(child => {
                this.convertToNetwork(child, nodeId, nodes, edges, level + 1);
            });
        }

        return { nodes, edges };
    }

    createAvatarDataUrl(letter, color) {
        const canvas = document.createElement('canvas');
        canvas.width = 120;
        canvas.height = 120;
        const ctx = canvas.getContext('2d');

        // Add outer shadow glow
        ctx.shadowColor = color;
        ctx.shadowBlur = 15;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 0;

        // Draw main circle with gradient
        const gradient = ctx.createLinearGradient(0, 0, 120, 120);
        gradient.addColorStop(0, color);
        gradient.addColorStop(1, this.adjustColorBrightness(color, -20));

        ctx.beginPath();
        ctx.arc(60, 60, 50, 0, 2 * Math.PI);
        ctx.fillStyle = gradient;
        ctx.fill();

        // Reset shadow for border
        ctx.shadowColor = 'transparent';
        ctx.shadowBlur = 0;

        // Draw white border
        ctx.lineWidth = 5;
        ctx.strokeStyle = '#ffffff';
        ctx.stroke();

        // Add inner highlight for 3D effect
        const highlightGradient = ctx.createRadialGradient(45, 45, 10, 60, 60, 50);
        highlightGradient.addColorStop(0, 'rgba(255, 255, 255, 0.4)');
        highlightGradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
        ctx.fillStyle = highlightGradient;
        ctx.fill();

        // Add subtle inner shadow at bottom
        ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
        ctx.shadowBlur = 8;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 3;

        // Draw letter
        ctx.shadowColor = 'rgba(0, 0, 0, 0.4)';
        ctx.shadowBlur = 4;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 2;
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 56px Inter, system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(letter, 60, 62);

        return canvas.toDataURL();
    }

    adjustColorBrightness(color, amount) {
        // Convert hex to RGB
        let usePound = false;
        if (color[0] === "#") {
            color = color.slice(1);
            usePound = true;
        }

        const num = parseInt(color, 16);
        let r = (num >> 16) + amount;
        let g = ((num >> 8) & 0x00FF) + amount;
        let b = (num & 0x0000FF) + amount;

        r = Math.max(Math.min(255, r), 0);
        g = Math.max(Math.min(255, g), 0);
        b = Math.max(Math.min(255, b), 0);

        return (usePound ? "#" : "") + ((r << 16) | (g << 8) | b).toString(16).padStart(6, '0');
    }

    createTooltipHtml(data) {
        const name = data.name || 'Unknown';
        const referralCode = data.referral_code || '-';
        const level = data.level || 0;
        const directChildren = data.direct_children || 0;
        const totalReferrals = data.total_referrals || 0;
        const totalEarnings = data.total_earnings || 0;
        const status = data.status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน';
        const rankName = data.rank?.name || 'ไม่มีแรงค์';

        // Plain text tooltip (vis-network doesn't support HTML)
        let tooltip = `👤 ${name}\n`;
        tooltip += `📌 รหัส: ${referralCode} | Level ${level}\n`;
        tooltip += `⭐ แรงค์: ${rankName}\n`;
        tooltip += `━━━━━━━━━━━━━━━━━━━━\n`;
        tooltip += `👥 ลูกทีมตรง: ${directChildren} คน\n`;
        tooltip += `🌐 เครือข่าย: ${totalReferrals} คน\n`;
        tooltip += `💰 รายได้: ฿${totalEarnings.toLocaleString('th-TH')}\n`;
        tooltip += `🔥 สถานะ: ${status}\n`;

        if (data.retention) {
            tooltip += `━━━━━━━━━━━━━━━━━━━━\n`;
            tooltip += `🎯 การรักษายอด:\n`;
            tooltip += `   วันที่เหลือ: ${data.retention.days_remaining} วัน\n`;
            tooltip += `   คะแนน: ${data.retention.current_points}/${data.retention.required_points}\n`;
        }

        tooltip += `\n💡 คลิกเพื่อดูรายละเอียด`;

        return tooltip;
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
