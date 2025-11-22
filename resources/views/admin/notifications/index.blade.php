@extends('layouts.admin')

@section('title', 'จัดการการแจ้งเตือน')
@section('page-title', 'จัดการการแจ้งเตือน')

@section('content')
<div class="space-y-6" x-data="{ selectedNotifications: [] }">
    {{-- Header Card พร้อม Glassmorphism --}}
    <div class="glass-card rounded-2xl p-6 border border-white/20 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent drop-shadow-lg">
                    📨 จัดการการแจ้งเตือน
                </h2>
                <p class="text-white/70 mt-1 text-sm drop-shadow">ส่งและจัดการการแจ้งเตือนให้กับสมาชิก</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.notifications.statistics') }}"
                   class="px-5 py-2.5 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl hover:from-purple-600 hover:to-indigo-700 shadow-lg hover:shadow-xl transition-all transform hover:scale-105 active:scale-95 flex items-center gap-2 font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="drop-shadow">ดูสถิติ</span>
                </a>
                <a href="{{ route('admin.notifications.create') }}"
                   class="px-5 py-2.5 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-xl hover:from-blue-600 hover:to-cyan-700 shadow-lg hover:shadow-xl transition-all transform hover:scale-105 active:scale-95 font-medium">
                    <span class="drop-shadow">+ ส่งการแจ้งเตือนใหม่</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Filters Card พร้อม Glassmorphism --}}
    <div class="glass-card rounded-2xl p-5 border border-white/20 shadow-xl">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-white/90 mb-2 drop-shadow">🏷️ ประเภท</label>
                <select name="type" class="w-full px-4 py-2.5 glass-neu text-white border border-white/20 rounded-xl focus:border-white/40 focus:ring-2 focus:ring-white/20 transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="system" {{ request('type') === 'system' ? 'selected' : '' }}>ระบบ</option>
                    <option value="announcement" {{ request('type') === 'announcement' ? 'selected' : '' }}>ประกาศ</option>
                    <option value="alert" {{ request('type') === 'alert' ? 'selected' : '' }}>แจ้งเตือน</option>
                    <option value="wallet" {{ request('type') === 'wallet' ? 'selected' : '' }}>กระเป๋าเงิน</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>ถอนเงิน</option>
                    <option value="commission" {{ request('type') === 'commission' ? 'selected' : '' }}>คอมมิชชั่น</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-white/90 mb-2 drop-shadow">⚡ ระดับความสำคัญ</label>
                <select name="priority" class="w-full px-4 py-2.5 glass-neu text-white border border-white/20 rounded-xl focus:border-white/40 focus:ring-2 focus:ring-white/20 transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>ต่ำ</option>
                    <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>ปกติ</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>สูง</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-white/90 mb-2 drop-shadow">📡 ประเภทการส่ง</label>
                <select name="is_broadcast" class="w-full px-4 py-2.5 glass-neu text-white border border-white/20 rounded-xl focus:border-white/40 focus:ring-2 focus:ring-white/20 transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="1" {{ request('is_broadcast') === '1' ? 'selected' : '' }}>ประกาศทั่วไป</option>
                    <option value="0" {{ request('is_broadcast') === '0' ? 'selected' : '' }}>รายบุคคล</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl hover:from-gray-600 hover:to-gray-700 shadow-lg hover:shadow-xl transition-all transform hover:scale-105 active:scale-95 font-medium">
                    🔍 ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Notifications Table พร้อม Glassmorphism --}}
    <div class="glass-card rounded-2xl border border-white/20 shadow-2xl overflow-hidden">
        {{-- Table Wrapper สำหรับ responsive --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10">
                <thead class="glass-neu">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">ไอคอน</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">หัวข้อ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">ผู้รับ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">ประเภท</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">ความสำคัญ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">วันที่สร้าง</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-white/90 uppercase tracking-wider drop-shadow">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                @forelse($notifications as $notification)
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg text-2xl">
                                {{ $notification->icon ?? '🔔' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-white drop-shadow">{{ $notification->title }}</div>
                            <div class="text-sm text-white/60 mt-0.5">{{ Str::limit($notification->message, 50) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($notification->is_broadcast)
                                <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gradient-to-r from-purple-500/30 to-indigo-500/30 text-purple-200 border border-purple-400/30">
                                    📡 ประกาศทั่วไป
                                </span>
                            @else
                                <span class="text-sm text-white/80">{{ $notification->user->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gradient-to-r from-blue-500/30 to-cyan-500/30 text-blue-200 border border-blue-400/30">
                                {{ $notification->type_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1.5 text-xs font-medium rounded-lg
                                @if($notification->priority === 'urgent') bg-gradient-to-r from-red-500/30 to-pink-500/30 text-red-200 border border-red-400/30
                                @elseif($notification->priority === 'high') bg-gradient-to-r from-orange-500/30 to-yellow-500/30 text-orange-200 border border-orange-400/30
                                @elseif($notification->priority === 'normal') bg-gradient-to-r from-blue-500/30 to-cyan-500/30 text-blue-200 border border-blue-400/30
                                @else bg-gradient-to-r from-gray-500/30 to-slate-500/30 text-gray-200 border border-gray-400/30
                                @endif">
                                {{ $notification->priority_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-1.5">
                                @if($notification->is_read)
                                    <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gradient-to-r from-green-500/30 to-emerald-500/30 text-green-200 border border-green-400/30">✓ อ่านแล้ว</span>
                                @else
                                    <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gradient-to-r from-gray-500/30 to-slate-500/30 text-gray-200 border border-gray-400/30">○ ยังไม่อ่าน</span>
                                @endif
                                @if($notification->show_immediately)
                                    <span class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gradient-to-r from-yellow-500/30 to-amber-500/30 text-yellow-200 border border-yellow-400/30">⚡ แสดงทันที</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-white/70">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.notifications.show', $notification->id) }}"
                                   class="px-3 py-1.5 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-lg hover:from-blue-600 hover:to-cyan-700 transition-all transform hover:scale-105 active:scale-95 shadow-md">👁️ ดู</a>
                                <form action="{{ route('admin.notifications.destroy', $notification->id) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('ต้องการลบการแจ้งเตือนนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-lg hover:from-red-600 hover:to-pink-700 transition-all transform hover:scale-105 active:scale-95 shadow-md">🗑️ ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-16 h-16 text-white/30 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-white/60 text-lg font-medium">ไม่มีการแจ้งเตือน</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination พร้อม Glassmorphism --}}
        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-white/10 glass-neu">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>

{{-- เพิ่ม Styles สำหรับ Glassmorphism --}}
@push('styles')
<style>
.glass-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.glass-neu {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
</style>
@endpush
@endsection
