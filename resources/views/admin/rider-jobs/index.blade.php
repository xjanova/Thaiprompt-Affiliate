{{--
    รายการงานไรเดอร์ทั้งหมด
    แสดงงานส่งของ/บริการ พร้อมสถิติ
--}}
@extends('layouts.admin')

@section('title', 'จัดการงานไรเดอร์')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-shipping-fast text-purple-400"></i>
                จัดการงานไรเดอร์
            </h1>
            <p class="text-gray-400 text-sm mt-1">ดูและจัดการงานทั้งหมดในระบบ</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.riders.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition">
                <i class="fas fa-users mr-2"></i> จัดการไรเดอร์
            </a>
            <a href="{{ route('admin.riders.map') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl transition">
                <i class="fas fa-map mr-2"></i> แผนที่ GPS
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-600/30 to-blue-700/30 backdrop-blur-xl rounded-2xl p-4 border border-blue-500/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/30 flex items-center justify-center">
                    <i class="fas fa-list text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['total'] ?? 0) }}</p>
                    <p class="text-blue-400 text-xs">ทั้งหมด</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-600/30 to-yellow-700/30 backdrop-blur-xl rounded-2xl p-4 border border-yellow-500/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-yellow-500/30 flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['pending'] ?? 0) }}</p>
                    <p class="text-yellow-400 text-xs">รอรับงาน</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-600/30 to-purple-700/30 backdrop-blur-xl rounded-2xl p-4 border border-purple-500/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/30 flex items-center justify-center">
                    <i class="fas fa-motorcycle text-purple-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['in_progress'] ?? 0) }}</p>
                    <p class="text-purple-400 text-xs">กำลังดำเนินการ</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-600/30 to-green-700/30 backdrop-blur-xl rounded-2xl p-4 border border-green-500/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-500/30 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['completed'] ?? 0) }}</p>
                    <p class="text-green-400 text-xs">สำเร็จ</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-600/30 to-red-700/30 backdrop-blur-xl rounded-2xl p-4 border border-red-500/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-500/30 flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['cancelled'] ?? 0) }}</p>
                    <p class="text-red-400 text-xs">ยกเลิก</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-indigo-600/30 to-indigo-700/30 backdrop-blur-xl rounded-2xl p-4 border border-indigo-500/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-500/30 flex items-center justify-center">
                    <i class="fas fa-wallet text-indigo-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['total_revenue'] ?? 0, 0) }}</p>
                    <p class="text-indigo-400 text-xs">รายได้ (บาท)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-4 mb-6 border border-white/10">
        <form action="" method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาเลขงาน, ที่อยู่..."
                       class="w-full px-4 py-2 bg-white/10 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <select name="status"
                    class="px-4 py-2 bg-white/10 border border-white/10 rounded-xl text-white focus:ring-purple-500 focus:border-purple-500">
                <option value="">ทุกสถานะ</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอรับงาน</option>
                <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>รับงานแล้ว</option>
                <option value="picked_up" {{ request('status') == 'picked_up' ? 'selected' : '' }}>รับสินค้าแล้ว</option>
                <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>กำลังจัดส่ง</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
            </select>

            <input type="date" name="date" value="{{ request('date') }}"
                   class="px-4 py-2 bg-white/10 border border-white/10 rounded-xl text-white focus:ring-purple-500 focus:border-purple-500">

            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl transition">
                <i class="fas fa-search mr-2"></i> ค้นหา
            </button>
        </form>
    </div>

    {{-- Jobs Table --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">เลขงาน</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ลูกค้า</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ไรเดอร์</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">จุดรับ</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">จุดส่ง</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ค่าบริการ</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($jobs as $job)
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-white font-mono">#{{ $job->id }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center">
                                        <i class="fas fa-user text-blue-400 text-xs"></i>
                                    </div>
                                    <span class="text-white">{{ $job->customer->name ?? 'ไม่ระบุ' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($job->rider)
                                    <a href="{{ route('admin.riders.show', $job->rider) }}" class="flex items-center gap-2 text-green-400 hover:text-green-300">
                                        <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center">
                                            <i class="fas fa-motorcycle text-green-400 text-xs"></i>
                                        </div>
                                        <span>{{ $job->rider->full_name }}</span>
                                    </a>
                                @else
                                    <span class="text-gray-500">รอไรเดอร์รับงาน</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-300 text-sm line-clamp-1 max-w-[150px]">{{ $job->pickup_address ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-300 text-sm line-clamp-1 max-w-[150px]">{{ $job->dropoff_address ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-purple-400 font-medium">{{ number_format($job->total_fee ?? 0, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
                                        'accepted' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                                        'picked_up' => 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30',
                                        'in_transit' => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
                                        'completed' => 'bg-green-500/20 text-green-400 border-green-500/30',
                                        'cancelled' => 'bg-red-500/20 text-red-400 border-red-500/30',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'รอรับงาน',
                                        'accepted' => 'รับงานแล้ว',
                                        'picked_up' => 'รับสินค้าแล้ว',
                                        'in_transit' => 'กำลังจัดส่ง',
                                        'completed' => 'สำเร็จ',
                                        'cancelled' => 'ยกเลิก',
                                    ];
                                @endphp
                                <span class="px-3 py-1 text-xs rounded-full border {{ $statusColors[$job->status] ?? 'bg-gray-500/20 text-gray-400 border-gray-500/30' }}">
                                    {{ $statusLabels[$job->status] ?? $job->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-400 text-sm">{{ $job->created_at->thaidate('j M Y H:i') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.rider-jobs.show', $job) }}"
                                       class="p-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 rounded-lg transition"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(in_array($job->status, ['pending', 'accepted', 'picked_up', 'in_transit']))
                                        <button onclick="cancelJob({{ $job->id }})"
                                                class="p-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg transition"
                                                title="ยกเลิกงาน">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>ไม่พบข้อมูลงาน</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($jobs->hasPages())
            <div class="px-6 py-4 border-t border-white/10">
                {{ $jobs->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function cancelJob(jobId) {
    const reason = prompt('เหตุผลในการยกเลิก:');
    if (reason === null) return;

    fetch(`/admin/rider-jobs/${jobId}/cancel`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ reason: reason }),
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    })
    .catch(err => alert('เกิดข้อผิดพลาด'));
}
</script>
@endpush
