{{--
    รายการไรเดอร์ที่รอตรวจสอบ
    แสดงไรเดอร์ที่สมัครใหม่และรอการอนุมัติ
--}}
@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.riders.index') }}"
               class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition backdrop-blur-lg border border-white/10">
                <i class="fas fa-arrow-left text-white"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                    <span>ไรเดอร์รอตรวจสอบ</span>
                    @if($pendingCount > 0)
                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 text-sm rounded-full border border-yellow-500/30 animate-pulse">
                            {{ $pendingCount }} รายการ
                        </span>
                    @endif
                </h1>
                <p class="text-gray-400 text-sm mt-1">ตรวจสอบและอนุมัติไรเดอร์ใหม่</p>
            </div>
        </div>

        {{-- Search --}}
        <form action="" method="GET" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="ค้นหาชื่อ, เบอร์โทร..."
                   class="px-4 py-2 bg-white/10 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500">
            <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl transition">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    {{-- Rider Cards --}}
    @if($riders->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($riders as $rider)
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10 hover:border-yellow-500/30 transition">
                    {{-- Header --}}
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                            @if($rider->profile_image)
                                <img src="{{ asset('storage/' . $rider->profile_image) }}" alt="{{ $rider->full_name }}" class="w-full h-full rounded-full object-cover">
                            @else
                                <i class="fas fa-user text-white text-2xl"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $rider->full_name }}</h3>
                            <p class="text-gray-400">{{ $rider->phone }}</p>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center gap-3 text-gray-300">
                            <i class="fas fa-id-card text-gray-500 w-5"></i>
                            <span>{{ substr($rider->id_card_number ?? '', 0, 4) }}***{{ substr($rider->id_card_number ?? '', -4) }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-300">
                            <i class="fas fa-motorcycle text-gray-500 w-5"></i>
                            @php
                                $vehicleLabels = [
                                    'motorcycle' => 'มอเตอร์ไซค์',
                                    'car' => 'รถยนต์',
                                    'pickup' => 'รถกระบะ',
                                    'van' => 'รถตู้',
                                    'truck' => 'รถบรรทุก',
                                ];
                            @endphp
                            <span>{{ $vehicleLabels[$rider->vehicle_type] ?? $rider->vehicle_type }}</span>
                            @if($rider->vehicle_plate)
                                <span class="text-gray-500">| {{ $rider->vehicle_plate }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-gray-300">
                            <i class="fas fa-calendar text-gray-500 w-5"></i>
                            <span>สมัคร: {{ $rider->created_at->thaidate('j M Y H:i') }}</span>
                        </div>
                    </div>

                    {{-- Documents --}}
                    <div class="flex gap-2 mb-4">
                        @if($rider->id_card_image)
                            <a href="{{ asset('storage/' . $rider->id_card_image) }}" target="_blank"
                               class="flex-1 py-2 bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 text-center rounded-lg text-sm transition">
                                <i class="fas fa-id-card mr-1"></i> บัตรปชช.
                            </a>
                        @endif
                        @if($rider->driving_license_image)
                            <a href="{{ asset('storage/' . $rider->driving_license_image) }}" target="_blank"
                               class="flex-1 py-2 bg-green-500/20 hover:bg-green-500/30 text-green-400 text-center rounded-lg text-sm transition">
                                <i class="fas fa-car mr-1"></i> ใบขับขี่
                            </a>
                        @endif
                        @if($rider->vehicle_registration_image)
                            <a href="{{ asset('storage/' . $rider->vehicle_registration_image) }}" target="_blank"
                               class="flex-1 py-2 bg-purple-500/20 hover:bg-purple-500/30 text-purple-400 text-center rounded-lg text-sm transition">
                                <i class="fas fa-file-alt mr-1"></i> เล่มทะเบียน
                            </a>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        <a href="{{ route('admin.riders.show', $rider) }}"
                           class="flex-1 py-3 bg-white/10 hover:bg-white/20 text-white text-center rounded-xl transition">
                            <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
                        </a>
                        <button onclick="approveRider({{ $rider->id }})"
                                class="flex-1 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl transition">
                            <i class="fas fa-check mr-1"></i> อนุมัติ
                        </button>
                        <button onclick="rejectRider({{ $rider->id }})"
                                class="px-4 py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $riders->withQueryString()->links() }}
        </div>
    @else
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-12 text-center border border-white/10">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-green-500/20 flex items-center justify-center">
                <i class="fas fa-check-circle text-green-400 text-4xl"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">ไม่มีไรเดอร์รอตรวจสอบ</h3>
            <p class="text-gray-400">ไรเดอร์ทั้งหมดได้รับการตรวจสอบเรียบร้อยแล้ว</p>
            <a href="{{ route('admin.riders.index') }}" class="inline-block mt-4 px-6 py-3 bg-purple-600 hover:bg-purple-500 text-white rounded-xl transition">
                <i class="fas fa-arrow-left mr-2"></i> กลับไปรายการไรเดอร์
            </a>
        </div>
    @endif
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm">
    <div class="bg-gray-900 rounded-2xl p-6 w-full max-w-md border border-white/10">
        <h3 class="text-xl font-bold text-white mb-4">ปฏิเสธไรเดอร์</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="text-gray-300 block mb-2">เหตุผล</label>
                <textarea name="reason" required
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-white focus:ring-purple-500 focus:border-purple-500"
                          rows="4"
                          placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeRejectModal()" class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition">
                    ยกเลิก
                </button>
                <button type="submit" class="flex-1 py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl transition">
                    ยืนยันปฏิเสธ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function approveRider(riderId) {
    if (!confirm('ยืนยันอนุมัติไรเดอร์นี้?')) return;

    fetch(`/admin/riders/${riderId}/approve`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
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

function rejectRider(riderId) {
    document.getElementById('rejectForm').action = `/admin/riders/${riderId}/reject`;
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}
</script>
@endpush
