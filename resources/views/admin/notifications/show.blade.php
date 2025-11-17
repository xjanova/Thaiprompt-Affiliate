@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการแจ้งเตือน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.notifications.index') }}" class="text-gray-600 hover:text-gray-900">
                ← กลับ
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">รายละเอียดการแจ้งเตือน</h1>
                <p class="text-sm text-gray-600 mt-1">ข้อมูลการแจ้งเตือน #{{ $notification->id }}</p>
            </div>
        </div>
        <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST"
              onsubmit="return confirm('ต้องการลบการแจ้งเตือนนี้?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                ลบการแจ้งเตือน
            </button>
        </form>
    </div>

    <!-- Notification Details -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6 space-y-6">
            <!-- Icon and Title -->
            <div class="flex items-start gap-4">
                <div class="text-4xl">{{ $notification->icon }}</div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $notification->title }}</h2>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            {{ $notification->type_label }}
                        </span>
                        <span class="px-2 py-1 text-xs rounded-full
                            @if($notification->priority === 'urgent') bg-red-100 text-red-800
                            @elseif($notification->priority === 'high') bg-orange-100 text-orange-800
                            @elseif($notification->priority === 'normal') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $notification->priority_label }}
                        </span>
                        @if($notification->is_broadcast)
                            <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                ประกาศทั่วไป
                            </span>
                        @endif
                        @if($notification->is_important)
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                สำคัญ
                            </span>
                        @endif
                        @if($notification->show_immediately)
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                แสดงทันที
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Message -->
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-2">ข้อความ</h3>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-gray-900 whitespace-pre-wrap">{{ $notification->message }}</p>
                </div>
            </div>

            <!-- Action Button -->
            @if($notification->action_url && $notification->action_text)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">ปุ่มดำเนินการ</h3>
                    <a href="{{ $notification->action_url }}" target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-{{ $notification->color }}-600 text-white rounded-lg hover:bg-{{ $notification->color }}-700">
                        {{ $notification->action_text }}
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            @endif

            <!-- Recipient Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">ผู้รับ</h3>
                    @if($notification->is_broadcast)
                        <p class="text-gray-900">ประกาศให้สมาชิกทั้งหมด</p>
                    @else
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                                {{ strtoupper(substr($notification->user->name ?? 'N', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-gray-900 font-medium">{{ $notification->user->name ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-500">{{ $notification->user->email ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">สถานะการอ่าน</h3>
                    @if($notification->is_read)
                        <div class="space-y-1">
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">อ่านแล้ว</span>
                            <p class="text-sm text-gray-500">
                                อ่านเมื่อ: {{ $notification->read_at ? $notification->read_at->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                        </div>
                    @else
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">ยังไม่อ่าน</span>
                    @endif
                </div>
            </div>

            <!-- Timestamps -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-gray-200">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-1">วันที่สร้าง</h3>
                    <p class="text-gray-900">{{ $notification->created_at->format('d/m/Y H:i') }}</p>
                </div>

                @if($notification->shown_at)
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">แสดงเมื่อ</h3>
                        <p class="text-gray-900">{{ $notification->shown_at->format('d/m/Y H:i') }}</p>
                    </div>
                @endif

                @if($notification->expires_at)
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">หมดอายุเมื่อ</h3>
                        <p class="text-gray-900">{{ $notification->expires_at->format('d/m/Y H:i') }}</p>
                    </div>
                @endif
            </div>

            <!-- Additional Data -->
            @if($notification->data && count($notification->data) > 0)
                <div class="pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">ข้อมูลเพิ่มเติม</h3>
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <pre class="text-sm text-gray-900">{{ json_encode($notification->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
