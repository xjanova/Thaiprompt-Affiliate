@extends('layouts.admin-v3')

@section('title', 'จัดการอุปกรณ์ SMS Checker')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                📱 จัดการอุปกรณ์ SMS Checker
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                ดูและจัดการอุปกรณ์ Android ที่ลงทะเบียนในระบบ
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.smschecker.index') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ← กลับ
            </a>
            <a href="{{ route('admin.smschecker.device-create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                + เพิ่มอุปกรณ์ใหม่
            </a>
        </div>
    </div>

    {{-- ตัวกรอง --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('admin.smschecker.devices') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาชื่ออุปกรณ์ หรือ Device ID..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>⚪ Inactive</option>
                    <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>🔴 Blocked</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.smschecker.devices') }}"
                   class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
        ✅ {{ session('success') }}
    </div>
    @endif

    {{-- ตารางอุปกรณ์ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">อุปกรณ์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Device ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เจ้าของ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">SMS</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Active ล่าสุด</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($devices as $device)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.smschecker.device-show', $device) }}"
                               class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                {{ $device->device_name ?? 'ไม่มีชื่อ' }}
                            </a>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $device->platform }} {{ $device->app_version ? "v{$device->app_version}" : '' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">
                            {{ $device->device_id }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            @if($device->user)
                                {{ $device->user->name ?? 'User #' . $device->user_id }}
                            @else
                                <span class="text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($device->status === 'active')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">🟢 Active</span>
                            @elseif($device->status === 'inactive')
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-full">⚪ Inactive</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-full">🔴 Blocked</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                            {{ number_format($device->notifications_count) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $device->last_active_at ? $device->last_active_at->diffForHumans() : 'ไม่เคย' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.smschecker.device-show', $device) }}"
                               class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            📱 ยังไม่มีอุปกรณ์ในระบบ
                            <br>
                            <a href="{{ route('admin.smschecker.device-create') }}"
                               class="text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
                                + เพิ่มอุปกรณ์ใหม่
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $devices->links() }}
    </div>
</div>
@endsection
