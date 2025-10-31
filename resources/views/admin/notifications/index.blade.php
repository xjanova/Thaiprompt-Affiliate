@extends('layouts.admin')

@section('title', 'จัดการการแจ้งเตือน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">จัดการการแจ้งเตือน</h1>
            <p class="text-sm text-gray-600 mt-1">ส่งและจัดการการแจ้งเตือนให้กับสมาชิก</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.notifications.statistics') }}"
               class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                ดูสถิติ
            </a>
            <a href="{{ route('admin.notifications.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all">
                + ส่งการแจ้งเตือนใหม่
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">ระดับความสำคัญ</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">ทั้งหมด</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>ต่ำ</option>
                    <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>ปกติ</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>สูง</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>เร่งด่วน</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทการส่ง</label>
                <select name="is_broadcast" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">ทั้งหมด</option>
                    <option value="1" {{ request('is_broadcast') === '1' ? 'selected' : '' }}>ประกาศทั่วไป</option>
                    <option value="0" {{ request('is_broadcast') === '0' ? 'selected' : '' }}>รายบุคคล</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    <!-- Notifications Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ไอคอน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">หัวข้อ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้รับ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ความสำคัญ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่สร้าง</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($notifications as $notification)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-2xl">{{ $notification->icon }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $notification->title }}</div>
                            <div class="text-sm text-gray-500">{{ Str::limit($notification->message, 50) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($notification->is_broadcast)
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                    ประกาศทั่วไป
                                </span>
                            @else
                                <span class="text-sm text-gray-900">{{ $notification->user->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $notification->type_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full
                                @if($notification->priority === 'urgent') bg-red-100 text-red-800
                                @elseif($notification->priority === 'high') bg-orange-100 text-orange-800
                                @elseif($notification->priority === 'normal') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $notification->priority_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-1">
                                @if($notification->is_read)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">อ่านแล้ว</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">ยังไม่อ่าน</span>
                                @endif
                                @if($notification->show_immediately)
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">แสดงทันที</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.notifications.show', $notification->id) }}"
                                   class="text-indigo-600 hover:text-indigo-900">ดู</a>
                                <form action="{{ route('admin.notifications.destroy', $notification->id) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('ต้องการลบการแจ้งเตือนนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            ไม่มีการแจ้งเตือน
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
