@extends('layouts.admin')

@section('title', 'จัดการผู้ใช้ที่เชื่อมต่อ LINE')

@section('content')
{{--
/**
 * หน้าจัดการผู้ใช้ที่เชื่อมต่อ LINE
 *
 * ฟีเจอร์:
 * - แสดงรายการผู้ใช้ที่เชื่อมต่อ LINE
 * - ค้นหาและฟิลเตอร์
 * - เลือกหลายรายการ (checkbox)
 * - ตัดการเชื่อมต่อ LINE (single/bulk)
 * - Export CSV
 */
--}}
<div x-data="lineConnectionsManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/>
                        </svg>
                    </div>
                    จัดการผู้ใช้ที่เชื่อมต่อ LINE
                </h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    ดูและจัดการผู้ใช้ที่เชื่อมต่อบัญชี LINE กับระบบ
                </p>
            </div>

            {{-- Export Button --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.line-membership-signup.connections.export', request()->query()) }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow transition">
                    <i class="fas fa-file-export mr-2"></i>
                    Export CSV
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-link text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เชื่อมต่อทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_connected']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Verified</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['verified']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-day text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">วันนี้</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['today']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-week text-orange-600 dark:text-orange-400 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สัปดาห์นี้</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['this_week']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow mb-6 border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-filter text-gray-500"></i>
                ค้นหาและกรอง
            </h2>
        </div>
        <div class="p-4">
            <form method="GET" action="{{ route('admin.line-membership-signup.connections.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                {{-- Search --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="ชื่อ, อีเมล, LINE Display Name..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>

                {{-- Verified Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ Verified</label>
                    <select name="verified"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                        <option value="">ทั้งหมด</option>
                        <option value="1" {{ request('verified') === '1' ? 'selected' : '' }}>Verified</option>
                        <option value="0" {{ request('verified') === '0' ? 'selected' : '' }}>Not Verified</option>
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เชื่อมต่อ (จาก)</label>
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ถึงวันที่</label>
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500">
                </div>

                {{-- Actions --}}
                <div class="lg:col-span-5 flex items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow transition">
                        <i class="fas fa-search mr-2"></i>
                        ค้นหา
                    </button>
                    <a href="{{ route('admin.line-membership-signup.connections.index') }}"
                       class="inline-flex items-center px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>
                        ล้าง
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Actions (แสดงเมื่อเลือกรายการ) --}}
    <div x-show="selectedIds.length > 0"
         x-transition
         class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-square text-yellow-600 dark:text-yellow-400 text-xl"></i>
                <span class="text-yellow-800 dark:text-yellow-200 font-semibold">
                    เลือก <span x-text="selectedIds.length"></span> รายการ
                </span>
            </div>
            <div class="flex items-center gap-3">
                <button type="button"
                        @click="bulkDisconnect()"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow transition">
                    <i class="fas fa-unlink mr-2"></i>
                    ตัดการเชื่อมต่อที่เลือก
                </button>
                <button type="button"
                        @click="clearSelection()"
                        class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>
                    ยกเลิกการเลือก
                </button>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox"
                                   @change="toggleSelectAll($event)"
                                   :checked="isAllSelected"
                                   class="w-4 h-4 text-green-600 rounded border-gray-300 dark:border-gray-600 focus:ring-green-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            LINE Account
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            วันที่เชื่อมต่อ
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                            :class="{ 'bg-green-50 dark:bg-green-900/20': selectedIds.includes({{ $user->id }}) }">
                            <td class="px-4 py-3">
                                <input type="checkbox"
                                       value="{{ $user->id }}"
                                       @change="toggleSelect({{ $user->id }})"
                                       :checked="selectedIds.includes({{ $user->id }})"
                                       class="w-4 h-4 text-green-600 rounded border-gray-300 dark:border-gray-600 focus:ring-green-500">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->line_picture_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(substr($user->name, 0, 1)) . '&background=10b981&color=fff' }}"
                                         alt="{{ $user->name }}"
                                         class="w-10 h-10 rounded-full object-cover"
                                         onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=10b981&color=fff';">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/>
                                        </svg>
                                        {{ $user->line_display_name ?? 'N/A' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ Str::limit($user->line_user_id, 20) }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($user->line_verified)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fas fa-minus-circle mr-1"></i>
                                        Not Verified
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                @if($user->line_linked_at)
                                    <div>
                                        <p>{{ $user->line_linked_at->format('d/m/Y') }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->line_linked_at->format('H:i') }}</p>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                            @click="showUserDetail({{ $user->id }})"
                                            class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                            title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button"
                                            @click="confirmDisconnect({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                            title="ตัดการเชื่อมต่อ">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-users-slash text-gray-400 text-2xl"></i>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบผู้ใช้ที่เชื่อมต่อ LINE</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500">ลองปรับเงื่อนไขการค้นหา</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- User Detail Modal --}}
    <div x-show="showDetailModal"
         x-transition
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDetailModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">รายละเอียดผู้ใช้</h3>
                        <button @click="showDetailModal = false"
                                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div x-show="detailLoading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-green-500"></i>
                    </div>

                    <div x-show="!detailLoading && userDetail" class="space-y-4">
                        {{-- User Avatar & Name --}}
                        <div class="text-center">
                            <img :src="userDetail?.line_picture_url || 'https://ui-avatars.com/api/?name=U&background=10b981&color=fff'"
                                 class="w-20 h-20 rounded-full mx-auto mb-3 object-cover">
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white" x-text="userDetail?.name"></h4>
                            <p class="text-gray-500 dark:text-gray-400" x-text="userDetail?.email"></p>
                        </div>

                        {{-- Info Grid --}}
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">LINE Display Name</p>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="userDetail?.line_display_name || '-'"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">เบอร์โทร</p>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="userDetail?.phone || '-'"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Verified</p>
                                <p class="font-medium" :class="userDetail?.line_verified ? 'text-green-600' : 'text-gray-500'"
                                   x-text="userDetail?.line_verified ? 'Yes' : 'No'"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">วันที่เชื่อมต่อ</p>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="userDetail?.line_linked_at || '-'"></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">LINE User ID</p>
                                <p class="font-mono text-sm text-gray-900 dark:text-white break-all" x-text="userDetail?.line_user_id"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm Disconnect Modal --}}
    <div x-show="showConfirmModal"
         x-transition
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showConfirmModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-auto">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-unlink text-red-600 dark:text-red-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการตัดการเชื่อมต่อ</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        คุณต้องการตัดการเชื่อมต่อ LINE ของ
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="confirmUserName"></span>
                        ใช่หรือไม่?
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showConfirmModal = false"
                                class="px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
                            ยกเลิก
                        </button>
                        <button @click="executeDisconnect()"
                                :disabled="disconnecting"
                                class="px-6 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white font-semibold rounded-lg transition flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin" x-show="disconnecting"></i>
                            <span x-text="disconnecting ? 'กำลังดำเนินการ...' : 'ตัดการเชื่อมต่อ'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับจัดการ LINE Connections
 */
function lineConnectionsManager() {
    return {
        // Selection
        selectedIds: [],
        allUserIds: @json($users->pluck('id')->toArray()),

        // Modals
        showDetailModal: false,
        showConfirmModal: false,
        detailLoading: false,
        disconnecting: false,

        // Data
        userDetail: null,
        confirmUserId: null,
        confirmUserName: '',

        // Computed
        get isAllSelected() {
            return this.allUserIds.length > 0 && this.selectedIds.length === this.allUserIds.length;
        },

        init() {
            // Initialize
        },

        /**
         * Toggle เลือก/ยกเลิกเลือก single item
         */
        toggleSelect(id) {
            const index = this.selectedIds.indexOf(id);
            if (index === -1) {
                this.selectedIds.push(id);
            } else {
                this.selectedIds.splice(index, 1);
            }
        },

        /**
         * Toggle เลือกทั้งหมด
         */
        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedIds = [...this.allUserIds];
            } else {
                this.selectedIds = [];
            }
        },

        /**
         * ล้างการเลือก
         */
        clearSelection() {
            this.selectedIds = [];
        },

        /**
         * แสดง User Detail Modal
         */
        async showUserDetail(userId) {
            this.showDetailModal = true;
            this.detailLoading = true;
            this.userDetail = null;

            try {
                const response = await fetch(`/admin/line-membership-signup/connections/${userId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.userDetail = data.user;
                }
            } catch (error) {
                console.error('Error fetching user detail:', error);
            } finally {
                this.detailLoading = false;
            }
        },

        /**
         * แสดง Confirm Modal สำหรับ disconnect
         */
        confirmDisconnect(userId, userName) {
            this.confirmUserId = userId;
            this.confirmUserName = userName;
            this.showConfirmModal = true;
        },

        /**
         * Execute disconnect single user
         */
        async executeDisconnect() {
            if (!this.confirmUserId || this.disconnecting) return;

            this.disconnecting = true;

            try {
                const response = await fetch(`/admin/line-membership-signup/connections/${this.confirmUserId}/disconnect`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                const data = await response.json();

                if (data.success) {
                    // Reload page
                    window.location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error disconnecting:', error);
                alert('เกิดข้อผิดพลาดในการตัดการเชื่อมต่อ');
            } finally {
                this.disconnecting = false;
                this.showConfirmModal = false;
            }
        },

        /**
         * Bulk disconnect
         */
        async bulkDisconnect() {
            if (this.selectedIds.length === 0) return;

            if (!confirm(`คุณต้องการตัดการเชื่อมต่อ LINE ของ ${this.selectedIds.length} รายการใช่หรือไม่?`)) {
                return;
            }

            try {
                const response = await fetch('/admin/line-membership-signup/connections/bulk-disconnect', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ user_ids: this.selectedIds })
                });
                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error bulk disconnecting:', error);
                alert('เกิดข้อผิดพลาดในการตัดการเชื่อมต่อ');
            }
        }
    };
}
</script>
@endpush
@endsection
