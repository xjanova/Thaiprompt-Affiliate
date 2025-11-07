@extends('layouts.admin')

@section('title', 'POS Devices')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">📱 จัดการอุปกรณ์ POS</h1>
            <p class="text-gray-500 mt-1">จัดการและตรวจสอบอุปกรณ์ Point of Sale</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.pos.devices.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            + เพิ่มอุปกรณ์ใหม่
        </a>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="ชื่ออุปกรณ์, รหัส, License Key"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ร้านค้า</label>
                <select name="store_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                        {{ $store->store_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">สถานะแพคเกจ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="trial" {{ request('status') == 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    ค้นหา
                </button>
                <a href="{{ route('admin.pos.devices.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    <!-- Devices List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">อุปกรณ์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ร้านค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะแพคเกจ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันหมดอายุ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($devices as $device)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <span class="text-2xl">
                                        @if($device->is_online)
                                            🟢
                                        @else
                                            ⚪
                                        @endif
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $device->device_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $device->device_code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $device->store->store_name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $device->store->store_code ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($device->device_type === 'premium')
                                <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">💎 Premium</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">📱 Standard</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($device->subscription_status === 'active')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">✅ Active</span>
                            @elseif($device->subscription_status === 'trial')
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">🆓 Trial</span>
                            @elseif($device->subscription_status === 'expired')
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full">⚠️ Expired</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">🔒 Suspended</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if($device->subscription_ends_at)
                                {{ $device->subscription_ends_at->format('d/m/Y') }}
                                @if($device->subscription_ends_at->isPast() && $device->subscription_status !== 'expired')
                                    <span class="text-red-600 text-xs">(หมดอายุแล้ว)</span>
                                @elseif($device->subscription_ends_at->diffInDays(now()) <= 7)
                                    <span class="text-orange-600 text-xs">(ใกล้หมดอายุ)</span>
                                @endif
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($device->is_active)
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">เปิดใช้งาน</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">ปิดใช้งาน</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.pos.devices.show', $device) }}"
                                   class="text-blue-600 hover:text-blue-900" title="ดูรายละเอียด">
                                    👁️
                                </a>
                                @if(auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.pos.devices.edit', $device) }}"
                                   class="text-green-600 hover:text-green-900" title="แก้ไข">
                                    ✏️
                                </a>
                                <form action="{{ route('admin.pos.devices.toggle-status', $device) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-orange-600 hover:text-orange-900" title="{{ $device->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                        {{ $device->is_active ? '🔴' : '🟢' }}
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-400 text-lg">ไม่พบข้อมูลอุปกรณ์</div>
                            <p class="text-gray-500 text-sm mt-2">ลองปรับเปลี่ยนตัวกรองหรือเพิ่มอุปกรณ์ใหม่</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($devices->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $devices->links() }}
        </div>
        @endif
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">อุปกรณ์ทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900">{{ $devices->total() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">แสดงหน้านี้</div>
            <div class="text-2xl font-bold text-gray-900">{{ $devices->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">หน้า</div>
            <div class="text-2xl font-bold text-gray-900">{{ $devices->currentPage() }} / {{ $devices->lastPage() }}</div>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="bg-white rounded-lg shadow p-4">
            <a href="{{ route('admin.pos.devices-export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
               class="flex items-center justify-center h-full text-blue-600 hover:text-blue-700">
                <span class="text-lg mr-2">📥</span>
                Export CSV
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
