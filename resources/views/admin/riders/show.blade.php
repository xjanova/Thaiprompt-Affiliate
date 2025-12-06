{{--
    รายละเอียดไรเดอร์
    แสดงข้อมูลส่วนตัว, สถิติงาน, ประวัติงาน, ตำแหน่ง GPS ปัจจุบัน
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
                    <span>{{ $rider->full_name }}</span>
                    @if($rider->status === 'approved')
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 text-sm rounded-full border border-green-500/30">
                            <i class="fas fa-check-circle mr-1"></i> อนุมัติแล้ว
                        </span>
                    @elseif($rider->status === 'pending')
                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 text-sm rounded-full border border-yellow-500/30">
                            <i class="fas fa-clock mr-1"></i> รอตรวจสอบ
                        </span>
                    @elseif($rider->status === 'rejected')
                        <span class="px-3 py-1 bg-red-500/20 text-red-400 text-sm rounded-full border border-red-500/30">
                            <i class="fas fa-times-circle mr-1"></i> ถูกปฏิเสธ
                        </span>
                    @elseif($rider->status === 'suspended')
                        <span class="px-3 py-1 bg-gray-500/20 text-gray-400 text-sm rounded-full border border-gray-500/30">
                            <i class="fas fa-ban mr-1"></i> ระงับ
                        </span>
                    @endif
                </h1>
                <p class="text-gray-400 text-sm mt-1">รหัสไรเดอร์: #{{ $rider->id }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($rider->status === 'pending')
                <button onclick="approveRider()" class="px-6 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-check mr-2"></i> อนุมัติ
                </button>
                <button onclick="openRejectModal()" class="px-6 py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-times mr-2"></i> ปฏิเสธ
                </button>
            @elseif($rider->status === 'approved')
                <a href="{{ route('admin.riders.locations', $rider) }}"
                   class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-map-marker-alt mr-2"></i> ดู GPS
                </a>
                <button onclick="suspendRider()" class="px-6 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-ban mr-2"></i> ระงับ
                </button>
            @elseif($rider->status === 'suspended')
                <button onclick="activateRider()" class="px-6 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-check mr-2"></i> เปิดใช้งาน
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Profile & Stats --}}
        <div class="space-y-6">
            {{-- Profile Card --}}
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center">
                        @if($rider->profile_image)
                            <img src="{{ asset('storage/' . $rider->profile_image) }}" alt="{{ $rider->full_name }}" class="w-full h-full rounded-full object-cover">
                        @else
                            <i class="fas fa-user text-white text-3xl"></i>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">{{ $rider->full_name }}</h3>
                        <p class="text-gray-400">{{ $rider->phone }}</p>
                        @if($rider->user)
                            <p class="text-gray-500 text-sm">{{ $rider->user->email }}</p>
                        @endif
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-white/10">
                        <span class="text-gray-400">เลขบัตรประชาชน</span>
                        <span class="text-white font-mono">{{ substr($rider->id_card_number ?? '', 0, 4) }}***{{ substr($rider->id_card_number ?? '', -4) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-white/10">
                        <span class="text-gray-400">ประเภทรถ</span>
                        <span class="text-white">
                            @php
                                $vehicleLabels = [
                                    'motorcycle' => 'มอเตอร์ไซค์',
                                    'car' => 'รถยนต์',
                                    'pickup' => 'รถกระบะ',
                                    'van' => 'รถตู้',
                                    'truck' => 'รถบรรทุก',
                                ];
                            @endphp
                            {{ $vehicleLabels[$rider->vehicle_type] ?? $rider->vehicle_type }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-white/10">
                        <span class="text-gray-400">ทะเบียนรถ</span>
                        <span class="text-white font-mono">{{ $rider->vehicle_plate ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-white/10">
                        <span class="text-gray-400">วันที่สมัคร</span>
                        <span class="text-white">{{ $rider->created_at->thaidate() }}</span>
                    </div>
                    @if($rider->approved_at)
                        <div class="flex justify-between items-center py-3 border-b border-white/10">
                            <span class="text-gray-400">วันที่อนุมัติ</span>
                            <span class="text-green-400">{{ $rider->approved_at->thaidate() }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-400">สถานะพร้อมรับงาน</span>
                        <span class="px-3 py-1 rounded-full text-sm
                            {{ $rider->availability === 'online' ? 'bg-green-500/20 text-green-400' : ($rider->availability === 'busy' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-gray-500/20 text-gray-400') }}">
                            {{ $rider->availability === 'online' ? 'ออนไลน์' : ($rider->availability === 'busy' ? 'กำลังส่งงาน' : 'ออฟไลน์') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Services Card --}}
            @if(count($services) > 0)
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-briefcase text-purple-400"></i>
                        บริการที่ให้บริการ
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($services as $service)
                            <span class="px-3 py-1 bg-purple-500/20 text-purple-400 rounded-full text-sm border border-purple-500/30">
                                {{ $service->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Documents Card --}}
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-file-alt text-blue-400"></i>
                    เอกสาร
                </h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                        <span class="text-gray-300">บัตรประชาชน</span>
                        @if($rider->id_card_image)
                            <a href="{{ asset('storage/' . $rider->id_card_image) }}" target="_blank" class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-eye"></i> ดู
                            </a>
                        @else
                            <span class="text-gray-500">ไม่มี</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                        <span class="text-gray-300">ใบขับขี่</span>
                        @if($rider->driving_license_image)
                            <a href="{{ asset('storage/' . $rider->driving_license_image) }}" target="_blank" class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-eye"></i> ดู
                            </a>
                        @else
                            <span class="text-gray-500">ไม่มี</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                        <span class="text-gray-300">เล่มทะเบียนรถ</span>
                        @if($rider->vehicle_registration_image)
                            <a href="{{ asset('storage/' . $rider->vehicle_registration_image) }}" target="_blank" class="text-blue-400 hover:text-blue-300">
                                <i class="fas fa-eye"></i> ดู
                            </a>
                        @else
                            <span class="text-gray-500">ไม่มี</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Middle Column - Job Stats --}}
        <div class="space-y-6">
            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-blue-600/30 to-blue-700/30 backdrop-blur-xl rounded-2xl p-6 border border-blue-500/30">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/30 flex items-center justify-center">
                            <i class="fas fa-shipping-fast text-blue-400"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ number_format($jobStats['total']) }}</p>
                    <p class="text-blue-400 text-sm">งานทั้งหมด</p>
                </div>

                <div class="bg-gradient-to-br from-green-600/30 to-green-700/30 backdrop-blur-xl rounded-2xl p-6 border border-green-500/30">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-green-500/30 flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ number_format($jobStats['completed']) }}</p>
                    <p class="text-green-400 text-sm">สำเร็จ</p>
                </div>

                <div class="bg-gradient-to-br from-red-600/30 to-red-700/30 backdrop-blur-xl rounded-2xl p-6 border border-red-500/30">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-red-500/30 flex items-center justify-center">
                            <i class="fas fa-times-circle text-red-400"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ number_format($jobStats['cancelled']) }}</p>
                    <p class="text-red-400 text-sm">ยกเลิก</p>
                </div>

                <div class="bg-gradient-to-br from-purple-600/30 to-purple-700/30 backdrop-blur-xl rounded-2xl p-6 border border-purple-500/30">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-500/30 flex items-center justify-center">
                            <i class="fas fa-wallet text-purple-400"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-white">{{ number_format($jobStats['earnings'], 2) }}</p>
                    <p class="text-purple-400 text-sm">รายได้รวม (บาท)</p>
                </div>
            </div>

            {{-- GPS Location --}}
            @if($rider->current_latitude && $rider->current_longitude)
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-green-400"></i>
                        ตำแหน่งปัจจุบัน
                        <span class="text-xs text-gray-400 ml-2">
                            อัพเดท: {{ $rider->location_updated_at ? $rider->location_updated_at->diffForHumans() : 'ไม่ทราบ' }}
                        </span>
                    </h4>
                    <div id="mini-map" class="w-full h-48 rounded-xl overflow-hidden mb-4"></div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.riders.locations', $rider) }}"
                           class="flex-1 py-2 bg-blue-600 hover:bg-blue-500 text-white text-center rounded-lg transition text-sm">
                            <i class="fas fa-history mr-1"></i> ดูประวัติ
                        </a>
                        <a href="{{ route('admin.riders.map') }}"
                           class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg transition text-sm">
                            <i class="fas fa-map"></i>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column - Recent Jobs --}}
        <div class="space-y-6">
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h4 class="text-lg font-bold text-white mb-4 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-history text-yellow-400"></i>
                        งานล่าสุด
                    </span>
                    <a href="#" class="text-sm text-purple-400 hover:text-purple-300">ดูทั้งหมด</a>
                </h4>

                <div class="space-y-3">
                    @forelse($recentJobs as $job)
                        <div class="p-4 bg-white/5 rounded-xl border border-white/5 hover:border-purple-500/30 transition">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-white font-medium">#{{ $job->id }}</span>
                                <span class="px-2 py-0.5 text-xs rounded-full
                                    @if($job->status === 'completed') bg-green-500/20 text-green-400
                                    @elseif($job->status === 'cancelled') bg-red-500/20 text-red-400
                                    @elseif(in_array($job->status, ['accepted', 'picked_up', 'in_transit'])) bg-yellow-500/20 text-yellow-400
                                    @else bg-gray-500/20 text-gray-400
                                    @endif">
                                    {{ $job->status }}
                                </span>
                            </div>
                            <div class="text-gray-400 text-sm space-y-1">
                                <p class="flex items-start gap-2">
                                    <i class="fas fa-map-pin text-blue-400 mt-0.5"></i>
                                    <span class="line-clamp-1">{{ $job->pickup_address ?? 'ไม่ระบุ' }}</span>
                                </p>
                                <p class="flex items-start gap-2">
                                    <i class="fas fa-flag-checkered text-green-400 mt-0.5"></i>
                                    <span class="line-clamp-1">{{ $job->dropoff_address ?? 'ไม่ระบุ' }}</span>
                                </p>
                                @if($job->rider_earnings)
                                    <p class="flex items-center gap-2 text-purple-400">
                                        <i class="fas fa-wallet"></i>
                                        <span>{{ number_format($job->rider_earnings, 2) }} บาท</span>
                                    </p>
                                @endif
                            </div>
                            <p class="text-gray-500 text-xs mt-2">{{ $job->created_at->thaidate('j M Y H:i') }}</p>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p>ยังไม่มีประวัติงาน</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm">
    <div class="bg-gray-900 rounded-2xl p-6 w-full max-w-md border border-white/10">
        <h3 class="text-xl font-bold text-white mb-4">ปฏิเสธไรเดอร์</h3>
        <form id="rejectForm" action="{{ route('admin.riders.reject', $rider) }}" method="POST">
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
// Approve rider
function approveRider() {
    if (!confirm('ยืนยันอนุมัติไรเดอร์นี้?')) return;

    fetch('{{ route('admin.riders.approve', $rider) }}', {
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

// Suspend rider
function suspendRider() {
    const reason = prompt('เหตุผลในการระงับ (ไม่บังคับ):');
    if (reason === null) return;

    fetch('{{ route('admin.riders.suspend', $rider) }}', {
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

// Activate rider
function activateRider() {
    if (!confirm('ยืนยันเปิดใช้งานไรเดอร์นี้?')) return;

    fetch('{{ route('admin.riders.toggle-active', $rider) }}', {
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

// Reject modal
function openRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}

// Mini map
@if($rider->current_latitude && $rider->current_longitude)
function initMiniMap() {
    const darkStyle = [
        { elementType: "geometry", stylers: [{ color: "#1a1a2e" }] },
        { elementType: "labels.text.fill", stylers: [{ color: "#8b5cf6" }] },
        { featureType: "road", elementType: "geometry", stylers: [{ color: "#374151" }] },
        { featureType: "water", elementType: "geometry", stylers: [{ color: "#0f172a" }] },
    ];

    const map = new google.maps.Map(document.getElementById('mini-map'), {
        center: { lat: {{ $rider->current_latitude }}, lng: {{ $rider->current_longitude }} },
        zoom: 15,
        styles: darkStyle,
        disableDefaultUI: true,
    });

    new google.maps.Marker({
        position: { lat: {{ $rider->current_latitude }}, lng: {{ $rider->current_longitude }} },
        map: map,
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
                    <circle cx="20" cy="20" r="18" fill="#10B981" stroke="#fff" stroke-width="3"/>
                    <circle cx="20" cy="20" r="8" fill="white"/>
                    <circle cx="20" cy="20" r="4" fill="#10B981"/>
                </svg>
            `),
            anchor: new google.maps.Point(20, 20),
            scaledSize: new google.maps.Size(40, 40),
        },
    });
}

if (typeof google !== 'undefined') {
    initMiniMap();
} else {
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initMiniMap`;
    script.async = true;
    document.head.appendChild(script);
}
@endif
</script>
@endpush
