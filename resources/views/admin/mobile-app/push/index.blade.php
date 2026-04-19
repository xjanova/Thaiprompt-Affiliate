@extends('layouts.admin-v3')

@section('title', 'Push Notifications')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-4xl">📢</span>
                Push Notifications
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">
                ส่งข้อความถึงผู้ใช้แอพมือถือ
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.mobile-app.push.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition">
                <span>➕</span>
                <span>ส่ง Push ใหม่</span>
            </a>
            <a href="{{ route('admin.mobile-app.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                <span>←</span>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">ส่งแล้ว</div>
            <div class="text-2xl font-bold text-green-500">{{ number_format($stats['sent']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">รอส่ง</div>
            <div class="text-2xl font-bold text-yellow-500">{{ number_format($stats['pending']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">ล้มเหลว</div>
            <div class="text-2xl font-bold text-red-500">{{ number_format($stats['failed']) }}</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            ข้อความ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Platform
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            ผลลัพธ์
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            วันที่ส่ง
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($notifications as $notification)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $notification->title }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ Str::limit($notification->body, 50) }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                                    {{ $notification->target_platform === 'all' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    {{ $notification->target_platform === 'ios' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                    {{ $notification->target_platform === 'android' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}">
                                    {{ strtoupper($notification->target_platform) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                                    bg-{{ $notification->status_color }}-100 text-{{ $notification->status_color }}-800
                                    dark:bg-{{ $notification->status_color }}-900/30 dark:text-{{ $notification->status_color }}-400">
                                    {{ $notification->status_text }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                @if($notification->status === 'sent')
                                    <span class="text-green-500">✓ {{ number_format($notification->success_count) }}</span>
                                    @if($notification->failure_count > 0)
                                        <span class="text-red-500">✗ {{ number_format($notification->failure_count) }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                {{ $notification->sent_at?->format('d/m/Y H:i') ?? $notification->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ยังไม่มี Push Notification
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
