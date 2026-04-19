@extends('layouts.admin-v3')

@section('title', 'จัดการคอมมิชชั่นดูดวง')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="commissionManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                ⚙️ จัดการคอมมิชชั่นดูดวง
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                อนุมัติ จ่ายเงิน ปรับจำนวน และตั้งค่าคอมมิชชั่น
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.fortune.commissions.index') }}"
               class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center">
                <span class="mr-2">📊</span>
                ภาพรวม
            </a>
            <button @click="showCreateModal = true"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center">
                <span class="mr-2">➕</span>
                สร้างมือ
            </button>
        </div>
    </div>

    {{-- Settings Panel (Collapsible) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6 overflow-hidden">
        <button @click="showSettings = !showSettings"
                class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <div class="flex items-center gap-3">
                <span class="text-xl">⚙️</span>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white">ตั้งค่าคอมมิชชั่น</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        L1: {{ $settings?->fortune_level1_commission_type === 'percent' ? ($settings->fortune_level1_commission_amount ?? 10).'%' : '฿'.($settings->fortune_level1_commission_amount ?? 10) }}
                        |
                        L2: {{ $settings?->isFortuneLevel2Enabled() ? ($settings->fortune_level2_commission_type === 'percent' ? ($settings->fortune_level2_commission_amount ?? 5).'%' : '฿'.($settings->fortune_level2_commission_amount ?? 5)) : 'ปิด' }}
                    </p>
                </div>
            </div>
            <svg :class="showSettings ? 'rotate-180' : ''" class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="showSettings" x-collapse class="px-6 pb-6 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                {{-- Level 1 Settings --}}
                <div class="p-4 border border-blue-200 dark:border-blue-800 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                    <h4 class="font-bold text-blue-800 dark:text-blue-300 mb-3">👤 Level 1 — สายตรง</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                            <select x-model="settingsForm.fortune_level1_commission_type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                <option value="fixed">คงที่ (บาท)</option>
                                <option value="percent">เปอร์เซ็นต์ (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                จำนวน <span x-text="settingsForm.fortune_level1_commission_type === 'percent' ? '(%)' : '(บาท)'" class="text-gray-500"></span>
                            </label>
                            <input type="number" step="0.01" min="0"
                                   x-model="settingsForm.fortune_level1_commission_amount"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        </div>
                    </div>
                </div>

                {{-- Level 2 Settings --}}
                <div class="p-4 border border-purple-200 dark:border-purple-800 rounded-lg bg-purple-50 dark:bg-purple-900/20">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-bold text-purple-800 dark:text-purple-300">👥 Level 2 — ชั้นหลาน</h4>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settingsForm.fortune_level2_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-purple-600"></div>
                        </label>
                    </div>
                    <div x-show="settingsForm.fortune_level2_enabled" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                            <select x-model="settingsForm.fortune_level2_commission_type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                <option value="fixed">คงที่ (บาท)</option>
                                <option value="percent">เปอร์เซ็นต์ (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                จำนวน <span x-text="settingsForm.fortune_level2_commission_type === 'percent' ? '(%)' : '(บาท)'" class="text-gray-500"></span>
                            </label>
                            <input type="number" step="0.01" min="0"
                                   x-model="settingsForm.fortune_level2_commission_amount"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        </div>
                    </div>
                    <div x-show="!settingsForm.fortune_level2_enabled" class="text-sm text-gray-500 dark:text-gray-400 py-2">
                        Level 2 ปิดอยู่ — ไม่จ่ายคอมมิชชั่นชั้นหลาน
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button @click="saveSettings()"
                        :disabled="savingSettings"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg transition flex items-center gap-2">
                    <span x-show="!savingSettings">💾 บันทึกการตั้งค่า</span>
                    <span x-show="savingSettings">กำลังบันทึก...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border-l-4 border-yellow-500">
            <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">รอดำเนินการ</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['pending_count']) }}</div>
            <div class="text-yellow-600 dark:text-yellow-400 text-sm">฿{{ number_format($stats['pending_amount'], 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border-l-4 border-green-500">
            <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">อนุมัติแล้ว (รอจ่าย)</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['approved_count']) }}</div>
            <div class="text-green-600 dark:text-green-400 text-sm">฿{{ number_format($stats['approved_amount'], 2) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 border-l-4 border-blue-500">
            <div class="text-gray-500 dark:text-gray-400 text-sm mb-1">จ่ายแล้ว</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['paid_count']) }}</div>
            <div class="text-blue-600 dark:text-blue-400 text-sm">฿{{ number_format($stats['paid_amount'], 2) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <form method="GET" action="{{ route('admin.fortune.commissions.manage') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="all" {{ ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชั้น</label>
                <select name="level" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="all" {{ ($filters['level'] ?? 'all') === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                    <option value="1" {{ ($filters['level'] ?? '') == '1' ? 'selected' : '' }}>L1 สายตรง</option>
                    <option value="2" {{ ($filters['level'] ?? '') == '2' ? 'selected' : '' }}>L2 ชั้นหลาน</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เริ่มต้น</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่สิ้นสุด</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="flex items-end gap-3">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    🔍 กรอง
                </button>
                <a href="{{ route('admin.fortune.commissions.manage') }}"
                   class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- Bulk Action Bar --}}
    <div x-show="selectedIds.length > 0" x-transition
         class="bg-indigo-50 dark:bg-indigo-900/30 rounded-xl shadow p-4 mb-4 flex items-center justify-between">
        <div class="text-indigo-700 dark:text-indigo-300 font-medium">
            เลือก <span x-text="selectedIds.length" class="font-bold"></span> รายการ
        </div>
        <div class="flex gap-3">
            <button @click="bulkAction('approve')"
                    :disabled="processing"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-lg transition text-sm">
                ✅ อนุมัติที่เลือก
            </button>
            <button @click="bulkAction('pay')"
                    :disabled="processing"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg transition text-sm">
                💵 จ่ายที่เลือก
            </button>
            <button @click="selectedIds = []"
                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition text-sm">
                ยกเลิกเลือก
            </button>
        </div>
    </div>

    {{-- Commission Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded border-gray-300 dark:border-gray-600">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ผู้รับ
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จากผู้ใช้
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ชั้น
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จำนวน (฿)
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($commissions as $c)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            {{-- Checkbox --}}
                            <td class="px-4 py-4">
                                <input type="checkbox" :value="{{ $c->id }}"
                                       x-model="selectedIds"
                                       class="rounded border-gray-300 dark:border-gray-600">
                            </td>

                            {{-- ผู้รับ --}}
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center mr-3">
                                        <span class="text-purple-600 dark:text-purple-300 text-xs font-bold">
                                            {{ mb_substr($c->user?->name ?? '?', 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-gray-900 dark:text-gray-100 font-medium">{{ Str::limit($c->user?->name ?? '-', 18) }}</div>
                                        <div class="text-gray-500 dark:text-gray-400 text-xs">{{ Str::limit($c->user?->email ?? '', 22) }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- จากผู้ใช้ --}}
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ Str::limit($c->fromUser?->name ?? '-', 18) }}
                            </td>

                            {{-- ชั้น --}}
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                @if($c->level === 1)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">L1</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">L2</span>
                                @endif
                            </td>

                            {{-- จำนวน --}}
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-gray-100">
                                ฿{{ number_format($c->amount, 2) }}
                            </td>

                            {{-- สถานะ --}}
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                @switch($c->status)
                                    @case('pending')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">⏳ รอ</span>
                                        @break
                                    @case('approved')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">✅ อนุมัติ</span>
                                        @break
                                    @case('paid')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">💵 จ่ายแล้ว</span>
                                        @break
                                    @case('rejected')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">❌ ปฏิเสธ</span>
                                        @break
                                @endswitch
                            </td>

                            {{-- วันที่ --}}
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <div>{{ $c->created_at?->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $c->created_at?->format('H:i') }}</div>
                            </td>

                            {{-- จัดการ --}}
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    @if($c->status === 'pending')
                                        <button @click="approveOne({{ $c->id }})"
                                                class="px-2 py-1 text-xs bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded hover:bg-green-200 dark:hover:bg-green-800 transition">
                                            อนุมัติ
                                        </button>
                                        <button @click="openRejectModal({{ $c->id }})"
                                                class="px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-800 transition">
                                            ปฏิเสธ
                                        </button>
                                    @endif
                                    @if(in_array($c->status, ['pending', 'approved']))
                                        <button @click="openAdjustModal({{ $c->id }}, {{ $c->amount }})"
                                                class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 rounded hover:bg-yellow-200 dark:hover:bg-yellow-800 transition">
                                            ปรับ
                                        </button>
                                    @endif
                                    @if($c->status === 'approved')
                                        <button @click="payOne({{ $c->id }})"
                                                class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800 transition">
                                            จ่าย
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-3">📭</div>
                                <p>ไม่พบข้อมูลคอมมิชชั่น</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($commissions->hasPages())
        <div class="mt-6">
            {{ $commissions->appends(request()->query())->links() }}
        </div>
    @endif

    {{-- Reject Modal --}}
    <div x-show="showRejectModal" x-transition
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
         @keydown.escape.window="showRejectModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md mx-4" @click.outside="showRejectModal = false">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">❌ ปฏิเสธคอมมิชชั่น</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล (ไม่บังคับ)</label>
                <textarea x-model="rejectReason" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                          placeholder="ระบุเหตุผลที่ปฏิเสธ..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button @click="showRejectModal = false"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition">
                    ยกเลิก
                </button>
                <button @click="confirmReject()"
                        :disabled="processing"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-lg transition">
                    ยืนยันปฏิเสธ
                </button>
            </div>
        </div>
    </div>

    {{-- Adjust Amount Modal --}}
    <div x-show="showAdjustModal" x-transition
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
         @keydown.escape.window="showAdjustModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md mx-4" @click.outside="showAdjustModal = false">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">✏️ ปรับจำนวนเงิน</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนเงินใหม่ (บาท)</label>
                <input type="number" step="0.01" min="0" x-model="adjustAmount"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล (ไม่บังคับ)</label>
                <input type="text" x-model="adjustReason"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                       placeholder="เหตุผลที่ปรับ...">
            </div>
            <div class="flex justify-end gap-3">
                <button @click="showAdjustModal = false"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition">
                    ยกเลิก
                </button>
                <button @click="confirmAdjust()"
                        :disabled="processing"
                        class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 disabled:opacity-50 text-white rounded-lg transition">
                    ยืนยันปรับ
                </button>
            </div>
        </div>
    </div>

    {{-- Create Manual Modal --}}
    <div x-show="showCreateModal" x-transition
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
         @keydown.escape.window="showCreateModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4" @click.outside="showCreateModal = false">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">➕ สร้างคอมมิชชั่นด้วยมือ</h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User ID ผู้รับ</label>
                        <input type="number" x-model="createForm.user_id"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                               placeholder="ID ผู้รับคอมมิชชั่น">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User ID ผู้จ่าย (คนดูดวง)</label>
                        <input type="number" x-model="createForm.from_user_id"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                               placeholder="ID คนดูดวง">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชั้น</label>
                        <select x-model="createForm.level"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <option value="1">L1 สายตรง</option>
                            <option value="2">L2 ชั้นหลาน</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวน (บาท)</label>
                        <input type="number" step="0.01" min="0.01" x-model="createForm.amount"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                               placeholder="0.00">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">บิลดูดวง # (ไม่บังคับ)</label>
                    <input type="number" x-model="createForm.fortune_reading_id"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                           placeholder="ID บิลดูดวง (ถ้ามี)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเหตุ</label>
                    <textarea x-model="createForm.notes" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                              placeholder="หมายเหตุ..."></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-3">
                <button @click="showCreateModal = false"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition">
                    ยกเลิก
                </button>
                <button @click="confirmCreate()"
                        :disabled="processing"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-lg transition">
                    สร้างคอมมิชชั่น
                </button>
            </div>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div x-show="toast.show" x-transition
         :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
         class="fixed bottom-6 right-6 z-[100] text-white px-6 py-3 rounded-xl shadow-lg">
        <span x-text="toast.message"></span>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับจัดการคอมมิชชั่น
 */
function commissionManager() {
    return {
        // สถานะ
        selectedIds: [],
        processing: false,
        showSettings: false,
        savingSettings: false,

        // Modals
        showRejectModal: false,
        showAdjustModal: false,
        showCreateModal: false,

        // ข้อมูล modal
        rejectId: null,
        rejectReason: '',
        adjustId: null,
        adjustAmount: 0,
        adjustReason: '',

        // ฟอร์มสร้างมือ
        createForm: {
            user_id: '',
            from_user_id: '',
            level: '1',
            amount: '',
            fortune_reading_id: '',
            notes: '',
        },

        // การตั้งค่า
        settingsForm: {
            fortune_level1_commission_type: '{{ $settings->fortune_level1_commission_type ?? "fixed" }}',
            fortune_level1_commission_amount: '{{ $settings->fortune_level1_commission_amount ?? 10 }}',
            fortune_level2_enabled: {{ ($settings?->isFortuneLevel2Enabled() ?? true) ? 'true' : 'false' }},
            fortune_level2_commission_type: '{{ $settings->fortune_level2_commission_type ?? "fixed" }}',
            fortune_level2_commission_amount: '{{ $settings->fortune_level2_commission_amount ?? 5 }}',
        },

        // Toast notification
        toast: { show: false, message: '', type: 'success' },

        /**
         * แสดง toast notification
         */
        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        /**
         * เลือก/ยกเลิกเลือกทั้งหมด
         */
        toggleAll(event) {
            if (event.target.checked) {
                this.selectedIds = @json($commissions->pluck('id'));
            } else {
                this.selectedIds = [];
            }
        },

        /**
         * Bulk action (อนุมัติ/จ่าย)
         */
        async bulkAction(action) {
            if (this.selectedIds.length === 0) return;

            const actionText = action === 'approve' ? 'อนุมัติ' : 'จ่าย';
            if (!confirm(`ต้องการ${actionText} ${this.selectedIds.length} รายการ?`)) return;

            this.processing = true;
            try {
                const url = action === 'approve'
                    ? '{{ route("admin.fortune.commissions.approve") }}'
                    : '{{ route("admin.fortune.commissions.pay") }}';

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ commission_ids: this.selectedIds }),
                });

                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => location.reload(), 1000);
                }
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            }
            this.processing = false;
        },

        /**
         * อนุมัติรายการเดียว
         */
        async approveOne(id) {
            if (!confirm('ต้องการอนุมัติคอมมิชชั่นนี้?')) return;

            this.processing = true;
            try {
                const res = await fetch('{{ route("admin.fortune.commissions.approve") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ commission_ids: [id] }),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },

        /**
         * จ่ายรายการเดียว
         */
        async payOne(id) {
            if (!confirm('ต้องการจ่ายคอมมิชชั่นนี้เข้า wallet?')) return;

            this.processing = true;
            try {
                const res = await fetch('{{ route("admin.fortune.commissions.pay") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ commission_ids: [id] }),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },

        /**
         * เปิด modal ปฏิเสธ
         */
        openRejectModal(id) {
            this.rejectId = id;
            this.rejectReason = '';
            this.showRejectModal = true;
        },

        /**
         * ยืนยันปฏิเสธ
         */
        async confirmReject() {
            this.processing = true;
            try {
                const res = await fetch(`{{ url('admin/fortune/commissions') }}/${this.rejectId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ reason: this.rejectReason }),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                this.showRejectModal = false;
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },

        /**
         * เปิด modal ปรับจำนวนเงิน
         */
        openAdjustModal(id, currentAmount) {
            this.adjustId = id;
            this.adjustAmount = currentAmount;
            this.adjustReason = '';
            this.showAdjustModal = true;
        },

        /**
         * ยืนยันปรับจำนวนเงิน
         */
        async confirmAdjust() {
            this.processing = true;
            try {
                const res = await fetch(`{{ url('admin/fortune/commissions') }}/${this.adjustId}/adjust`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        amount: this.adjustAmount,
                        reason: this.adjustReason,
                    }),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                this.showAdjustModal = false;
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },

        /**
         * บันทึกการตั้งค่า
         */
        async saveSettings() {
            this.savingSettings = true;
            try {
                const res = await fetch('{{ route("admin.fortune.commissions.update-settings") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.settingsForm),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.savingSettings = false;
        },

        /**
         * สร้างคอมมิชชั่นด้วยมือ
         */
        async confirmCreate() {
            if (!this.createForm.user_id || !this.createForm.from_user_id || !this.createForm.amount) {
                this.showToast('กรุณากรอก User ID ผู้รับ, ผู้จ่าย, และจำนวนเงิน', 'error');
                return;
            }

            this.processing = true;
            try {
                const res = await fetch('{{ route("admin.fortune.commissions.create-manual") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.createForm),
                });
                const data = await res.json();
                this.showToast(data.message, data.success ? 'success' : 'error');
                this.showCreateModal = false;
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (e) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.processing = false;
        },
    };
}
</script>
@endpush
@endsection
