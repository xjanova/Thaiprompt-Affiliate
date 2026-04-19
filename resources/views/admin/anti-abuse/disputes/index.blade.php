{{--
    Admin - รายการข้อร้องเรียนทั้งหมด
--}}
@extends('layouts.admin-v3')

@section('title', 'จัดการข้อร้องเรียน')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                จัดการข้อร้องเรียน
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ตรวจสอบและแก้ไขข้อร้องเรียนจากผู้ใช้และผู้ให้บริการ
            </p>
        </div>
        <a href="{{ route('admin.anti-abuse.dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-arrow-left"></i>
            กลับ Dashboard
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="หมายเลข, หัวข้อ..."
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                <select name="dispute_type" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($disputeTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('dispute_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความสำคัญ</label>
                <select name="priority" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                    <option value="">ทั้งหมด</option>
                    <option value="critical" {{ request('priority') === 'critical' ? 'selected' : '' }}>วิกฤต</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>สูง</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>ต่ำ</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-search mr-1"></i> ค้นหา
                </button>
                <a href="{{ route('admin.anti-abuse.disputes') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Disputes Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หมายเลข</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หัวข้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ร้อง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ถูกร้อง</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ความสำคัญ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($disputes as $dispute)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-sm text-purple-600 dark:text-purple-400">
                                    {{ $dispute->dispute_number }}
                                </span>
                                <p class="text-xs text-gray-500">{{ $dispute->created_at->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                    {{ $dispute->title }}
                                </p>
                                @if($dispute->booking)
                                    <p class="text-xs text-gray-500">
                                        Booking: {{ $dispute->booking->booking_number }}
                                    </p>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $dispute->getDisputeTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($dispute->reporter_type === 'user')
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $dispute->reporterUser?->name ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 ml-1">(ลูกค้า)</span>
                                @elseif($dispute->reporter_type === 'provider')
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $dispute->reporterProvider?->display_name ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 ml-1">(ผู้ให้บริการ)</span>
                                @else
                                    <span class="text-sm text-gray-500">ระบบ</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($dispute->accused_type === 'user')
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $dispute->accusedUser?->name ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 ml-1">(ลูกค้า)</span>
                                @else
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $dispute->accusedProvider?->display_name ?? '-' }}</span>
                                    <span class="text-xs text-gray-500 ml-1">(ผู้ให้บริการ)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @switch($dispute->priority)
                                    @case('critical')
                                        <span class="px-2 py-1 text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full">
                                            <i class="fas fa-fire mr-1"></i>วิกฤต
                                        </span>
                                        @break
                                    @case('high')
                                        <span class="px-2 py-1 text-xs font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded-full">
                                            สูง
                                        </span>
                                        @break
                                    @case('medium')
                                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                                            ปานกลาง
                                        </span>
                                        @break
                                    @default
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 rounded-full">
                                            ต่ำ
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @switch($dispute->status)
                                    @case('pending')
                                        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                                            รอตรวจสอบ
                                        </span>
                                        @break
                                    @case('investigating')
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full">
                                            กำลังสอบสวน
                                        </span>
                                        @break
                                    @case('resolved_favor_reporter')
                                    @case('resolved_favor_accused')
                                    @case('resolved_mutual')
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                            แก้ไขแล้ว
                                        </span>
                                        @break
                                    @case('dismissed')
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 rounded-full">
                                            ยกฟ้อง
                                        </span>
                                        @break
                                    @default
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 rounded-full">
                                            {{ $dispute->getStatusLabel() }}
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a href="{{ route('admin.anti-abuse.disputes.show', $dispute) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors">
                                    <i class="fas fa-eye"></i>
                                    ดู
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>ไม่พบข้อร้องเรียน</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($disputes->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $disputes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
