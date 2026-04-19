@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'กฎการคิดราคาบริการ')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
                    <i class="fas fa-calculator mr-3"></i>{{ $pageTitle ?? 'กฎการคิดราคาบริการ' }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">ตั้งค่ากฎการคำนวณราคาตามระยะทาง ช่วงเวลา และประเภทบริการ</p>
            </div>
            <a href="{{ route('admin.service-pricing-rules.create') }}" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl hover:shadow-lg transition flex items-center">
                <i class="fas fa-plus mr-2"></i>เพิ่มกฎใหม่
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-green-50 dark:bg-green-900/20 shadow-lg">
            <p class="text-sm text-green-600 dark:text-green-400">เปิดใช้งาน</p>
            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $stats['active'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-gray-50 dark:bg-gray-700/50 shadow-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">ปิดใช้งาน</p>
            <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $stats['inactive'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-blue-50 dark:bg-blue-900/20 shadow-lg">
            <p class="text-sm text-blue-600 dark:text-blue-400">ตามระยะทาง</p>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['distance_based'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-purple-50 dark:bg-purple-900/20 shadow-lg">
            <p class="text-sm text-purple-600 dark:text-purple-400">ตามเวลา</p>
            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $stats['time_based'] ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-xl backdrop-blur-xl bg-orange-50 dark:bg-orange-900/20 shadow-lg">
            <p class="text-sm text-orange-600 dark:text-orange-400">ราคาคงที่</p>
            <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">{{ $stats['fixed'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 p-4 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl">
        <form action="{{ route('admin.service-pricing-rules.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อกฎ..." class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
            </div>
            <div class="min-w-[150px]">
                <select name="type" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <option value="">ประเภททั้งหมด</option>
                    <option value="fixed" {{ request('type') == 'fixed' ? 'selected' : '' }}>ราคาคงที่</option>
                    <option value="distance" {{ request('type') == 'distance' ? 'selected' : '' }}>ตามระยะทาง</option>
                    <option value="time" {{ request('type') == 'time' ? 'selected' : '' }}>ตามเวลา</option>
                    <option value="zone" {{ request('type') == 'zone' ? 'selected' : '' }}>ตามโซน</option>
                    <option value="peak" {{ request('type') == 'peak' ? 'selected' : '' }}>ช่วงพีค</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <select name="is_active" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>ปิดใช้งาน</option>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>
            @if(request()->hasAny(['search', 'type', 'is_active']))
                <a href="{{ route('admin.service-pricing-rules.index') }}" class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">
                    <i class="fas fa-times mr-2"></i>ล้าง
                </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ชื่อกฎ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ราคาพื้นฐาน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ราคา/km</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Priority</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rules as $rule)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $rule->name }}</p>
                                    @if($rule->service)
                                        <p class="text-sm text-gray-500">{{ $rule->service->name }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $typeColors = [
                                        'fixed' => 'bg-gray-100 text-gray-800',
                                        'distance' => 'bg-blue-100 text-blue-800',
                                        'time' => 'bg-purple-100 text-purple-800',
                                        'zone' => 'bg-green-100 text-green-800',
                                        'peak' => 'bg-red-100 text-red-800',
                                    ];
                                    $typeLabels = [
                                        'fixed' => 'ราคาคงที่',
                                        'distance' => 'ตามระยะทาง',
                                        'time' => 'ตามเวลา',
                                        'zone' => 'ตามโซน',
                                        'peak' => 'ช่วงพีค',
                                    ];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $typeColors[$rule->type ?? 'fixed'] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $typeLabels[$rule->type ?? 'fixed'] ?? $rule->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white font-mono">
                                ฿{{ number_format($rule->base_price ?? $rule->flat_fee ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 font-mono">
                                @if($rule->price_per_km > 0)
                                    ฿{{ number_format($rule->price_per_km, 2) }}/km
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono">
                                    {{ $rule->priority ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($rule->is_active)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">เปิดใช้งาน</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">ปิดใช้งาน</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.service-pricing-rules.show', $rule) }}" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.service-pricing-rules.edit', $rule) }}" class="p-2 text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.service-pricing-rules.toggle-active', $rule) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 {{ $rule->is_active ? 'text-gray-600 hover:bg-gray-50' : 'text-green-600 hover:bg-green-50' }} rounded-lg transition" title="{{ $rule->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                            <i class="fas {{ $rule->is_active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.service-pricing-rules.destroy', $rule) }}" method="POST" class="inline" onsubmit="return confirm('ลบกฎนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-calculator text-4xl mb-3 block"></i>
                                ไม่มีข้อมูลกฎการคิดราคา
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($rules->hasPages())
        <div class="mt-6">
            {{ $rules->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
