@extends('layouts.admin-v3')

@section('title', 'Video Reward System Dashboard')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    🎬 ระบบวิดีโอรางวัล
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    จัดการระบบดูคลิปรับรางวัลแบบครบวงจร
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.video-rewards.videos.index') }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-video mr-2"></i>จัดการวิดีโอ
                </a>
                <a href="{{ route('admin.video-rewards.quests.index') }}"
                   class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-trophy mr-2"></i>จัดการภาระกิจ
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 rounded-xl">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <span class="text-sm font-medium opacity-80">ผู้ใช้ทั้งหมด</span>
            </div>
            <div class="text-3xl font-bold mb-1" id="total-users">-</div>
            <p class="text-sm opacity-80">ผู้ใช้งานระบบ</p>
        </div>

        <!-- Total Videos -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 rounded-xl">
                    <i class="fas fa-video text-2xl"></i>
                </div>
                <span class="text-sm font-medium opacity-80">วิดีโอ</span>
            </div>
            <div class="text-3xl font-bold mb-1" id="total-videos">-</div>
            <p class="text-sm opacity-80">วิดีโอทั้งหมด</p>
        </div>

        <!-- Coins Distributed -->
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 rounded-xl">
                    <i class="fas fa-coins text-2xl"></i>
                </div>
                <span class="text-sm font-medium opacity-80">เหรียญแจกแล้ว</span>
            </div>
            <div class="text-3xl font-bold mb-1" id="total-coins">-</div>
            <p class="text-sm opacity-80">เหรียญทั้งหมด</p>
        </div>

        <!-- Pending Exchanges -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 rounded-xl">
                    <i class="fas fa-exchange-alt text-2xl"></i>
                </div>
                <span class="text-sm font-medium opacity-80">รออนุมัติ</span>
            </div>
            <div class="text-3xl font-bold mb-1" id="pending-exchanges">-</div>
            <p class="text-sm opacity-80">คำขอแลกเหรียญ</p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Pending Exchange Requests -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-clock text-amber-500 mr-2"></i>
                        คำขอแลกเหรียญที่รออนุมัติ
                    </h2>
                    <a href="{{ route('admin.video-rewards.exchange.requests') }}"
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        ดูทั้งหมด →
                    </a>
                </div>

                <div id="pending-requests-list" class="space-y-4">
                    <!-- Will be populated by JavaScript -->
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                        <p>กำลังโหลด...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="space-y-6">
            <!-- Users by Level -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-chart-line text-green-500 mr-2"></i>
                    ผู้ใช้ตามระดับ
                </h3>
                <div id="users-by-level" class="space-y-3">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                    การกระทำด่วน
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.video-rewards.channels.index') }}"
                       class="block px-4 py-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition">
                        <i class="fas fa-tv text-blue-500 mr-2"></i>
                        <span class="text-gray-900 dark:text-white">จัดการช่อง</span>
                    </a>
                    <a href="{{ route('admin.video-rewards.videos.index') }}"
                       class="block px-4 py-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition">
                        <i class="fas fa-film text-purple-500 mr-2"></i>
                        <span class="text-gray-900 dark:text-white">เพิ่มวิดีโอ</span>
                    </a>
                    <a href="{{ route('admin.video-rewards.quests.index') }}"
                       class="block px-4 py-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition">
                        <i class="fas fa-tasks text-green-500 mr-2"></i>
                        <span class="text-gray-900 dark:text-white">สร้างภาระกิจ</span>
                    </a>
                    <a href="{{ route('admin.video-rewards.exchange-rates.index') }}"
                       class="block px-4 py-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition">
                        <i class="fas fa-money-bill-wave text-amber-500 mr-2"></i>
                        <span class="text-gray-900 dark:text-white">ตั้งค่าอัตราแลก</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

async function loadDashboardData() {
    try {
        const response = await fetch('/admin/video-rewards/dashboard', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load dashboard data');
        }

        const result = await response.json();
        if (result.success) {
            updateStats(result.data);
            updatePendingRequests(result.data);
            updateUsersByLevel(result.data.users_by_level || []);
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showErrorMessage();
    }
}

function updateStats(data) {
    document.getElementById('total-users').textContent = formatNumber(data.total_users || 0);
    document.getElementById('total-videos').textContent = formatNumber(data.total_videos || 0);
    document.getElementById('total-coins').textContent = formatNumber(data.total_coins_distributed || 0);
    document.getElementById('pending-exchanges').textContent = formatNumber(data.pending_exchanges || 0);
}

function updatePendingRequests(data) {
    const container = document.getElementById('pending-requests-list');

    if (!data.pending_exchanges || data.pending_exchanges === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-check-circle text-4xl mb-3 text-green-500"></i>
                <p>ไม่มีคำขอที่รออนุมัติ</p>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div class="text-center py-4">
            <a href="/admin/video-rewards/exchange-requests"
               class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-eye mr-2"></i>
                ดูคำขอทั้งหมด (${data.pending_exchanges})
            </a>
        </div>
    `;
}

function updateUsersByLevel(levels) {
    const container = document.getElementById('users-by-level');

    if (levels.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-sm">ยังไม่มีข้อมูล</p>';
        return;
    }

    container.innerHTML = levels.map(level => `
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-2xl">${level.currentLevel?.icon || '⭐'}</span>
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    ${level.currentLevel?.name || 'Unknown'}
                </span>
            </div>
            <span class="font-bold text-gray-900 dark:text-white">
                ${formatNumber(level.count || 0)}
            </span>
        </div>
    `).join('');
}

function formatNumber(num) {
    return new Intl.NumberFormat('th-TH').format(num);
}

function showErrorMessage() {
    const container = document.getElementById('pending-requests-list');
    container.innerHTML = `
        <div class="text-center py-8 text-red-500">
            <i class="fas fa-exclamation-triangle text-3xl mb-3"></i>
            <p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
        </div>
    `;
}
</script>
@endpush
@endsection
