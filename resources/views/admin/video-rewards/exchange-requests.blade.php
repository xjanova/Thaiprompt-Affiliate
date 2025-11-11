@extends('layouts.admin')

@section('title', 'จัดการคำขอแลกเหรียญ')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.video-rewards.dashboard') }}"
                   class="text-blue-600 hover:text-blue-700 dark:text-blue-400 mb-2 inline-block">
                    ← กลับไป Dashboard
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    💰 จัดการคำขอแลกเหรียญ
                </h1>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 mb-6">
        <div class="flex flex-wrap gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    สถานะ
                </label>
                <select id="status-filter"
                        class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white">
                    <option value="pending">รออนุมัติ</option>
                    <option value="approved">อนุมัติแล้ว</option>
                    <option value="rejected">ปฏิเสธ</option>
                    <option value="completed">เสร็จสิ้น</option>
                </select>
            </div>

            <div class="flex-1"></div>

            <div class="flex items-end">
                <button onclick="loadExchangeRequests()"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>รีเฟรช
                </button>
            </div>
        </div>
    </div>

    <!-- Requests List -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            เหรียญ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            เงิน (THB)
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            อัตราแลก
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            การกระทำ
                        </th>
                    </tr>
                </thead>
                <tbody id="requests-tbody" class="divide-y divide-gray-200 dark:divide-slate-700">
                    <!-- Loading state -->
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-3"></i>
                            <p class="text-gray-500">กำลังโหลดข้อมูล...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
            <!-- Will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div id="approve-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            ✅ อนุมัติคำขอแลกเหรียญ
        </h3>

        <div class="mb-6">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                คุณต้องการอนุมัติคำขอแลกเหรียญนี้หรือไม่?
            </p>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-4">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-700 dark:text-gray-300">เหรียญ:</span>
                    <span class="font-bold" id="approve-coins">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">เงิน:</span>
                    <span class="font-bold" id="approve-money">-</span>
                </div>
            </div>

            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                หมายเหตุ (ถ้ามี)
            </label>
            <textarea id="approve-note"
                      rows="3"
                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white"
                      placeholder="เช่น: อนุมัติตามเงื่อนไข"></textarea>
        </div>

        <div class="flex gap-3">
            <button onclick="closeApproveModal()"
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                ยกเลิก
            </button>
            <button onclick="confirmApprove()"
                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-check mr-2"></i>อนุมัติ
            </button>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="reject-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            ❌ ปฏิเสธคำขอแลกเหรียญ
        </h3>

        <div class="mb-6">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                กรุณาระบุเหตุผลในการปฏิเสธ
            </p>

            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                เหตุผล <span class="text-red-500">*</span>
            </label>
            <textarea id="reject-note"
                      rows="4"
                      required
                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white"
                      placeholder="เช่น: ข้อมูลไม่ครบถ้วน, ไม่ผ่านเงื่อนไข"></textarea>
            <p class="text-sm text-gray-500 mt-1">เหรียญจะถูกคืนให้ผู้ใช้โดยอัตโนมัติ</p>
        </div>

        <div class="flex gap-3">
            <button onclick="closeRejectModal()"
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                ยกเลิก
            </button>
            <button onclick="confirmReject()"
                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-times mr-2"></i>ปฏิเสธ
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentRequestId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadExchangeRequests();

    // Listen to status filter change
    document.getElementById('status-filter').addEventListener('change', loadExchangeRequests);
});

async function loadExchangeRequests() {
    const status = document.getElementById('status-filter').value;
    const tbody = document.getElementById('requests-tbody');

    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-12 text-center">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-3"></i>
                <p class="text-gray-500">กำลังโหลดข้อมูล...</p>
            </td>
        </tr>
    `;

    try {
        const response = await fetch(`/admin/video-rewards/exchange-requests?status=${status}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            displayRequests(result.data.data || result.data);
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-red-500">
                    <i class="fas fa-exclamation-triangle text-3xl mb-3"></i>
                    <p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
                </td>
            </tr>
        `;
    }
}

function displayRequests(requests) {
    const tbody = document.getElementById('requests-tbody');

    if (!requests || requests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-3"></i>
                    <p>ไม่พบคำขอแลกเหรียญ</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = requests.map(request => `
        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                        ${request.user?.name?.charAt(0).toUpperCase() || 'U'}
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">
                            ${request.user?.name || 'Unknown'}
                        </div>
                        <div class="text-sm text-gray-500">
                            ${request.user?.email || '-'}
                        </div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-coins text-amber-500"></i>
                    <span class="font-bold text-gray-900 dark:text-white">
                        ${formatNumber(request.coins_amount)}
                    </span>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-green-500"></i>
                    <span class="font-bold text-gray-900 dark:text-white">
                        ฿${formatNumber(request.money_amount)}
                    </span>
                </div>
            </td>
            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                ${request.exchange_rate}
            </td>
            <td class="px-6 py-4 text-gray-700 dark:text-gray-300 text-sm">
                ${formatDate(request.created_at)}
            </td>
            <td class="px-6 py-4">
                ${getStatusBadge(request.status)}
            </td>
            <td class="px-6 py-4 text-right">
                ${getActionButtons(request)}
            </td>
        </tr>
    `).join('');
}

function getStatusBadge(status) {
    const badges = {
        pending: '<span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">รออนุมัติ</span>',
        approved: '<span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">อนุมัติแล้ว</span>',
        rejected: '<span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">ปฏิเสธ</span>',
        completed: '<span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">เสร็จสิ้น</span>',
    };
    return badges[status] || status;
}

function getActionButtons(request) {
    if (request.status === 'pending') {
        return `
            <div class="flex gap-2 justify-end">
                <button onclick="openApproveModal(${request.id}, ${request.coins_amount}, ${request.money_amount})"
                        class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                    <i class="fas fa-check"></i>
                </button>
                <button onclick="openRejectModal(${request.id})"
                        class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }
    return '<span class="text-gray-400 text-sm">-</span>';
}

function openApproveModal(requestId, coins, money) {
    currentRequestId = requestId;
    document.getElementById('approve-coins').textContent = formatNumber(coins);
    document.getElementById('approve-money').textContent = '฿' + formatNumber(money);
    document.getElementById('approve-note').value = '';
    document.getElementById('approve-modal').classList.remove('hidden');
}

function closeApproveModal() {
    document.getElementById('approve-modal').classList.add('hidden');
    currentRequestId = null;
}

async function confirmApprove() {
    const note = document.getElementById('approve-note').value;

    try {
        const response = await fetch(`/admin/video-rewards/exchange-requests/${currentRequestId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ note })
        });

        const result = await response.json();

        if (result.success) {
            closeApproveModal();
            showAlert('success', 'อนุมัติคำขอเรียบร้อยแล้ว');
            loadExchangeRequests();
        } else {
            showAlert('error', result.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการอนุมัติ');
    }
}

function openRejectModal(requestId) {
    currentRequestId = requestId;
    document.getElementById('reject-note').value = '';
    document.getElementById('reject-modal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.add('hidden');
    currentRequestId = null;
}

async function confirmReject() {
    const note = document.getElementById('reject-note').value.trim();

    if (!note) {
        showAlert('error', 'กรุณาระบุเหตุผล');
        return;
    }

    try {
        const response = await fetch(`/admin/video-rewards/exchange-requests/${currentRequestId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ note })
        });

        const result = await response.json();

        if (result.success) {
            closeRejectModal();
            showAlert('success', 'ปฏิเสธคำขอเรียบร้อยแล้ว');
            loadExchangeRequests();
        } else {
            showAlert('error', result.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'เกิดข้อผิดพลาดในการปฏิเสธ');
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('th-TH').format(num);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showAlert(type, message) {
    // You can use your existing alert system here
    alert(message);
}
</script>
@endpush
@endsection
