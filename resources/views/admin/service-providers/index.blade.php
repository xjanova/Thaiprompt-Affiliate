@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'จัดการผู้ให้บริการ')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl flex justify-between items-center">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
            <i class="fas fa-user-hard-hat mr-3"></i>{{ $pageTitle ?? 'จัดการผู้ให้บริการ' }}
        </h1>
        <a href="{{ route('admin.service-providers.create') }}"
           class="px-4 py-2 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl hover:shadow-lg transition">
            <i class="fas fa-plus mr-2"></i>เพิ่มผู้ให้บริการ
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-2xl font-bold">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">รอตรวจสอบ</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">อนุมัติแล้ว</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">ปฏิเสธ</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['rejected'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">เปิดใช้งาน</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['active'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">ปิดใช้งาน</p>
            <p class="text-2xl font-bold text-gray-600">{{ $stats['inactive'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ชื่อ, เบอร์โทร, อีเมล..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-orange-500">
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะการอนุมัติ</label>
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะใช้งาน</label>
                <select name="is_active" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <option value="">ทั้งหมด</option>
                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>ปิดใช้งาน</option>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
        </form>
    </div>

    {{-- Table --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-xl overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ให้บริการ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ติดต่อ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คะแนน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">งานทั้งหมด</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($providers as $provider)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center text-white font-bold">
                                {{ substr($provider->name ?? $provider->display_name ?? 'P', 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $provider->name ?? $provider->display_name }}</p>
                                <p class="text-sm text-gray-500">{{ $provider->user->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <p>{{ $provider->phone }}</p>
                        <p class="text-gray-500">{{ $provider->email ?? $provider->user->email ?? '-' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-500"></i>
                            <span class="font-bold">{{ number_format($provider->average_rating ?? 0, 1) }}</span>
                            <span class="text-gray-500 text-sm">({{ $provider->total_reviews ?? 0 }})</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold">{{ $provider->total_bookings ?? 0 }}</td>
                    <td class="px-6 py-4">
                        @if($provider->verification_status == 'pending')
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">รอตรวจสอบ</span>
                        @elseif($provider->verification_status == 'approved')
                            @if($provider->is_active)
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">เปิดใช้งาน</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">ปิดใช้งาน</span>
                            @endif
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">ปฏิเสธ</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('admin.service-providers.show', $provider) }}" class="text-blue-600 hover:text-blue-800" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.service-providers.edit', $provider) }}" class="text-orange-600 hover:text-orange-800" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($provider->verification_status == 'pending')
                            <form action="{{ route('admin.service-providers.verify', $provider) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800" title="อนุมัติ" onclick="return confirm('อนุมัติผู้ให้บริการนี้?')">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </form>
                        @endif
                        @if($provider->is_active)
                            <form action="{{ route('admin.service-providers.toggle-active', $provider) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-gray-800" title="ปิดใช้งาน">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.service-providers.toggle-active', $provider) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-gray-600" title="เปิดใช้งาน">
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-user-hard-hat text-4xl mb-4 opacity-50"></i>
                        <p>ไม่พบผู้ให้บริการ</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($providers->hasPages())
        <div class="mt-6">{{ $providers->links() }}</div>
    @endif
</div>
@endsection
