{{--
    Admin Riders Index - หน้าจัดการไรเดอร์ทั้งหมด
    แสดงรายการ สถิติ และเครื่องมือจัดการ
--}}

@extends('layouts.admin-v3')

@section('title', 'จัดการไรเดอร์')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-xl border border-gray-200/50 dark:border-gray-700/50">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent flex items-center">
                    <i class="fas fa-motorcycle mr-3 text-blue-600"></i>จัดการไรเดอร์
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">ดูแลและจัดการไรเดอร์ในระบบ</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.riders.pending') }}"
                   class="px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl font-medium transition shadow-lg shadow-yellow-500/30 flex items-center">
                    <i class="fas fa-clock mr-2"></i>รอตรวจสอบ
                    @if(($stats['pending'] ?? 0) > 0)
                        <span class="ml-2 px-2 py-0.5 bg-white/20 rounded-full text-sm">{{ $stats['pending'] }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.riders.map') }}"
                   class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-medium transition shadow-lg shadow-green-500/30 flex items-center">
                    <i class="fas fa-map-location-dot mr-2"></i>แผนที่
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        {{-- ทั้งหมด --}}
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                    <i class="fas fa-users text-white text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        {{-- รอตรวจสอบ --}}
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                    <i class="fas fa-clock text-white text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">รอตรวจสอบ</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        {{-- อนุมัติแล้ว --}}
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">อนุมัติแล้ว</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['approved'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        {{-- ปฏิเสธ --}}
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                    <i class="fas fa-times-circle text-white text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ปฏิเสธ</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($stats['rejected'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        {{-- พร้อมรับงาน --}}
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                    <i class="fas fa-signal text-white text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">พร้อมรับงาน</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ number_format($stats['online'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        {{-- กำลังส่ง --}}
        <div class="p-4 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                    <i class="fas fa-truck-fast text-white text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">กำลังส่ง</p>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['busy'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 p-5 rounded-xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 shadow-lg border border-gray-200/50 dark:border-gray-700/50">
        <form method="GET" action="{{ route('admin.riders.index') }}" class="flex flex-wrap gap-4 items-end">
            {{-- ค้นหา --}}
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-search mr-1"></i>ค้นหา
                </label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ชื่อ, เบอร์โทร, อีเมล..."
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            {{-- สถานะ --}}
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>ระงับ</option>
                </select>
            </div>

            {{-- สถานะพร้อมรับงาน --}}
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">พร้อมรับงาน</label>
                <select name="availability" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    <option value="online" {{ request('availability') == 'online' ? 'selected' : '' }}>พร้อมรับงาน</option>
                    <option value="offline" {{ request('availability') == 'offline' ? 'selected' : '' }}>ออฟไลน์</option>
                    <option value="busy" {{ request('availability') == 'busy' ? 'selected' : '' }}>กำลังส่ง</option>
                </select>
            </div>

            {{-- ประเภทรถ --}}
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทรถ</label>
                <select name="vehicle_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">ทั้งหมด</option>
                    <option value="motorcycle" {{ request('vehicle_type') == 'motorcycle' ? 'selected' : '' }}>มอเตอร์ไซค์</option>
                    <option value="car" {{ request('vehicle_type') == 'car' ? 'selected' : '' }}>รถยนต์</option>
                    <option value="bicycle" {{ request('vehicle_type') == 'bicycle' ? 'selected' : '' }}>จักรยาน</option>
                    <option value="walk" {{ request('vehicle_type') == 'walk' ? 'selected' : '' }}>เดินเท้า</option>
                </select>
            </div>

            {{-- ปุ่มค้นหา --}}
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-xl font-medium hover:opacity-90 transition shadow-lg shadow-blue-500/30 flex items-center">
                <i class="fas fa-search mr-2"></i>ค้นหา
            </button>

            @if(request()->hasAny(['search', 'status', 'availability', 'vehicle_type']))
                <a href="{{ route('admin.riders.index') }}" class="px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition flex items-center">
                    <i class="fas fa-times mr-2"></i>ล้าง
                </a>
            @endif
        </form>
    </div>

    {{-- Data Table --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ไรเดอร์</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ติดต่อ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">รถ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">คะแนน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">งาน</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($riders as $rider)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            {{-- ไรเดอร์ --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                        {{ mb_substr($rider->full_name ?? 'R', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $rider->full_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $rider->id }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- ติดต่อ --}}
                            <td class="px-6 py-4">
                                <p class="text-gray-900 dark:text-white">{{ $rider->phone }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rider->user->email ?? '-' }}</p>
                            </td>

                            {{-- รถ --}}
                            <td class="px-6 py-4">
                                @php
                                    $vehicleIcons = [
                                        'motorcycle' => 'fa-motorcycle',
                                        'car' => 'fa-car',
                                        'bicycle' => 'fa-bicycle',
                                        'walk' => 'fa-person-walking',
                                    ];
                                    $vehicleNames = [
                                        'motorcycle' => 'มอเตอร์ไซค์',
                                        'car' => 'รถยนต์',
                                        'bicycle' => 'จักรยาน',
                                        'walk' => 'เดินเท้า',
                                    ];
                                @endphp
                                <div class="flex items-center gap-2">
                                    <i class="fas {{ $vehicleIcons[$rider->vehicle_type] ?? 'fa-question' }} text-gray-500"></i>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $vehicleNames[$rider->vehicle_type] ?? $rider->vehicle_type }}</span>
                                </div>
                            </td>

                            {{-- คะแนน --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ number_format($rider->rating ?? 0, 1) }}</span>
                                    <span class="text-xs text-gray-500">({{ $rider->rating_count ?? 0 }})</span>
                                </div>
                            </td>

                            {{-- งาน --}}
                            <td class="px-6 py-4">
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($rider->total_jobs ?? 0) }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">งาน</span>
                                </div>
                                <div class="text-xs">
                                    <span class="text-green-600">{{ number_format($rider->completed_jobs ?? 0) }} สำเร็จ</span>
                                </div>
                            </td>

                            {{-- สถานะ --}}
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        'suspended' => 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300',
                                    ];
                                    $statusNames = [
                                        'pending' => 'รอตรวจสอบ',
                                        'approved' => 'อนุมัติแล้ว',
                                        'rejected' => 'ปฏิเสธ',
                                        'suspended' => 'ระงับ',
                                    ];
                                    $availabilityColors = [
                                        'online' => 'bg-green-500',
                                        'offline' => 'bg-gray-400',
                                        'busy' => 'bg-purple-500',
                                    ];
                                @endphp
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full {{ $statusColors[$rider->status] ?? '' }}">
                                        {{ $statusNames[$rider->status] ?? $rider->status }}
                                    </span>
                                    @if($rider->status === 'approved')
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $availabilityColors[$rider->availability] ?? 'bg-gray-400' }}"></span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $rider->availability === 'online' ? 'พร้อมรับงาน' : ($rider->availability === 'busy' ? 'กำลังส่ง' : 'ออฟไลน์') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- จัดการ --}}
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.riders.show', $rider) }}"
                                       class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.riders.locations', $rider) }}"
                                       class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition" title="ตำแหน่ง GPS">
                                        <i class="fas fa-map-location-dot"></i>
                                    </a>
                                    @if($rider->status === 'pending')
                                        <button onclick="approveRider({{ $rider->id }})"
                                                class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition" title="อนุมัติ">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="rejectRider({{ $rider->id }})"
                                                class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition" title="ปฏิเสธ">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                        <i class="fas fa-motorcycle text-4xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบไรเดอร์</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">ลองเปลี่ยนเงื่อนไขการค้นหา</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($riders->hasPages())
        <div class="mt-6">
            {{ $riders->links() }}
        </div>
    @endif
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRejectModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-times-circle text-red-500 mr-2"></i>ปฏิเสธไรเดอร์
            </h3>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล</label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-red-500"
                              placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()"
                            class="flex-1 px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-red-500 text-white rounded-xl font-medium hover:bg-red-600 transition">
                        ปฏิเสธ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // อนุมัติไรเดอร์
    function approveRider(id) {
        if (confirm('ยืนยันอนุมัติไรเดอร์นี้?')) {
            fetch(`/admin/riders/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            });
        }
    }

    // เปิด modal ปฏิเสธ
    function rejectRider(id) {
        document.getElementById('rejectForm').action = `/admin/riders/${id}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    // ปิด modal
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
