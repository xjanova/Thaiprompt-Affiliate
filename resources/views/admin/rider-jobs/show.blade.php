{{--
    Admin Rider Jobs Show - รายละเอียดงานไรเดอร์
    แสดงข้อมูลงาน, เส้นทาง, สถานะ และการจัดการ
--}}

@extends('layouts.admin-v3')

@section('title', 'รายละเอียดงาน: #' . $job->job_number)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.rider-jobs.index') }}"
               class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition backdrop-blur-lg border border-white/10">
                <i class="fas fa-arrow-left text-white"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                    <i class="fas fa-box text-blue-400"></i>
                    งาน #{{ $job->job_number }}
                </h1>
                <p class="text-gray-400 text-sm mt-1">
                    สร้างเมื่อ {{ $job->created_at->thaidate('j M Y H:i') }}
                </p>
            </div>
        </div>

        {{-- Status Badge --}}
        @php
            $statusColors = [
                'pending' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
                'accepted' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                'picking_up' => 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30',
                'picked_up' => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
                'delivering' => 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30',
                'delivered' => 'bg-teal-500/20 text-teal-400 border-teal-500/30',
                'completed' => 'bg-green-500/20 text-green-400 border-green-500/30',
                'cancelled' => 'bg-red-500/20 text-red-400 border-red-500/30',
                'failed' => 'bg-gray-500/20 text-gray-400 border-gray-500/30',
            ];
        @endphp
        <span class="px-4 py-2 rounded-xl border text-lg font-bold {{ $statusColors[$job->status] ?? '' }}">
            {{ $job->status_text }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Job Info Card --}}
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-400"></i>
                    ข้อมูลงาน
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-gray-400 text-sm">ประเภท</p>
                        <p class="text-white font-bold">{{ $job->job_type_text }}</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-gray-400 text-sm">ระยะทาง</p>
                        <p class="text-white font-bold">{{ number_format($job->distance_km ?? 0, 2) }} กม.</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-gray-400 text-sm">ค่าบริการ</p>
                        <p class="text-white font-bold">฿{{ number_format($job->total_fee ?? 0) }}</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-gray-400 text-sm">รายได้ไรเดอร์</p>
                        <p class="text-green-400 font-bold">฿{{ number_format($job->rider_earnings ?? 0) }}</p>
                    </div>
                </div>

                @if($job->title)
                    <div class="mb-4">
                        <p class="text-gray-400 text-sm">รายละเอียด</p>
                        <p class="text-white">{{ $job->title }}</p>
                        @if($job->description)
                            <p class="text-gray-400 text-sm mt-1">{{ $job->description }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Pickup & Delivery Info --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Pickup --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-location-dot text-green-400"></i>
                        จุดรับ
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-gray-400 text-sm">ที่อยู่</p>
                            <p class="text-white">{{ $job->pickup_address ?? '-' }}</p>
                        </div>
                        @if($job->pickup_contact_name)
                            <div>
                                <p class="text-gray-400 text-sm">ผู้ติดต่อ</p>
                                <p class="text-white">{{ $job->pickup_contact_name }}</p>
                            </div>
                        @endif
                        @if($job->pickup_contact_phone)
                            <div>
                                <p class="text-gray-400 text-sm">เบอร์โทร</p>
                                <a href="tel:{{ $job->pickup_contact_phone }}" class="text-blue-400 hover:underline">
                                    {{ $job->pickup_contact_phone }}
                                </a>
                            </div>
                        @endif
                        @if($job->pickup_notes)
                            <div>
                                <p class="text-gray-400 text-sm">หมายเหตุ</p>
                                <p class="text-yellow-400 text-sm">{{ $job->pickup_notes }}</p>
                            </div>
                        @endif
                        @if($job->picked_up_at)
                            <div class="pt-3 border-t border-white/10">
                                <p class="text-green-400 text-sm flex items-center gap-2">
                                    <i class="fas fa-check-circle"></i>
                                    รับเมื่อ {{ $job->picked_up_at->thaidate('j M Y H:i') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Delivery --}}
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-flag-checkered text-red-400"></i>
                        จุดส่ง
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-gray-400 text-sm">ที่อยู่</p>
                            <p class="text-white">{{ $job->delivery_address ?? '-' }}</p>
                        </div>
                        @if($job->delivery_contact_name)
                            <div>
                                <p class="text-gray-400 text-sm">ผู้ติดต่อ</p>
                                <p class="text-white">{{ $job->delivery_contact_name }}</p>
                            </div>
                        @endif
                        @if($job->delivery_contact_phone)
                            <div>
                                <p class="text-gray-400 text-sm">เบอร์โทร</p>
                                <a href="tel:{{ $job->delivery_contact_phone }}" class="text-blue-400 hover:underline">
                                    {{ $job->delivery_contact_phone }}
                                </a>
                            </div>
                        @endif
                        @if($job->delivery_notes)
                            <div>
                                <p class="text-gray-400 text-sm">หมายเหตุ</p>
                                <p class="text-yellow-400 text-sm">{{ $job->delivery_notes }}</p>
                            </div>
                        @endif
                        @if($job->delivered_at)
                            <div class="pt-3 border-t border-white/10">
                                <p class="text-green-400 text-sm flex items-center gap-2">
                                    <i class="fas fa-check-circle"></i>
                                    ส่งเมื่อ {{ $job->delivered_at->thaidate('j M Y H:i') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Map with Route --}}
            @if($job->pickup_latitude && $job->delivery_latitude)
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-map text-purple-400"></i>
                        เส้นทาง
                    </h3>
                    <div id="jobMap" class="h-[400px] w-full rounded-xl"></div>
                </div>
            @endif

            {{-- Proof Images --}}
            @if($job->pickup_proof_image || $job->delivery_proof_image || $job->signature_image)
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-camera text-cyan-400"></i>
                        หลักฐาน
                    </h3>
                    <div class="grid grid-cols-3 gap-4">
                        @if($job->pickup_proof_image)
                            <div>
                                <p class="text-gray-400 text-sm mb-2">รูปรับของ</p>
                                <a href="{{ asset('storage/' . $job->pickup_proof_image) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $job->pickup_proof_image) }}"
                                         class="w-full h-32 object-cover rounded-xl hover:opacity-80 transition">
                                </a>
                            </div>
                        @endif
                        @if($job->delivery_proof_image)
                            <div>
                                <p class="text-gray-400 text-sm mb-2">รูปส่งของ</p>
                                <a href="{{ asset('storage/' . $job->delivery_proof_image) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $job->delivery_proof_image) }}"
                                         class="w-full h-32 object-cover rounded-xl hover:opacity-80 transition">
                                </a>
                            </div>
                        @endif
                        @if($job->signature_image)
                            <div>
                                <p class="text-gray-400 text-sm mb-2">ลายเซ็น</p>
                                <a href="{{ asset('storage/' . $job->signature_image) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $job->signature_image) }}"
                                         class="w-full h-32 object-cover rounded-xl hover:opacity-80 transition bg-white">
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column: Rider & Actions --}}
        <div class="space-y-6">
            {{-- Rider Card --}}
            @if($job->rider)
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-motorcycle text-orange-400"></i>
                        ไรเดอร์
                    </h3>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
                            @if($job->rider->profile_image)
                                <img src="{{ asset('storage/' . $job->rider->profile_image) }}"
                                     class="w-full h-full rounded-xl object-cover">
                            @else
                                <i class="fas fa-user text-white text-2xl"></i>
                            @endif
                        </div>
                        <div>
                            <p class="text-white font-bold text-lg">{{ $job->rider->full_name }}</p>
                            <p class="text-gray-400">{{ $job->rider->phone }}</p>
                            <div class="flex items-center gap-1 mt-1">
                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                <span class="text-white text-sm">{{ number_format($job->rider->rating ?? 0, 1) }}</span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('admin.riders.show', $job->rider) }}"
                       class="block w-full py-2 bg-white/10 hover:bg-white/20 text-white text-center rounded-xl transition">
                        <i class="fas fa-eye mr-2"></i>ดูโปรไฟล์
                    </a>
                </div>
            @else
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-motorcycle text-orange-400"></i>
                        ไรเดอร์
                    </h3>
                    <div class="text-center py-4 text-gray-400">
                        <i class="fas fa-user-slash text-4xl mb-2"></i>
                        <p>ยังไม่มีไรเดอร์รับงาน</p>
                    </div>
                </div>
            @endif

            {{-- Customer Card --}}
            @if($job->customer)
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-user text-blue-400"></i>
                        ลูกค้า
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-gray-400 text-sm">ชื่อ</p>
                            <p class="text-white">{{ $job->customer->name }}</p>
                        </div>
                        @if($job->customer->phone)
                            <div>
                                <p class="text-gray-400 text-sm">เบอร์โทร</p>
                                <a href="tel:{{ $job->customer->phone }}" class="text-blue-400 hover:underline">
                                    {{ $job->customer->phone }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Timeline --}}
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-clock text-yellow-400"></i>
                    ไทม์ไลน์
                </h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-3 h-3 rounded-full bg-gray-500 mt-1.5"></div>
                        <div>
                            <p class="text-white">สร้างงาน</p>
                            <p class="text-gray-400 text-sm">{{ $job->created_at->thaidate('j M Y H:i:s') }}</p>
                        </div>
                    </div>
                    @if($job->accepted_at)
                        <div class="flex items-start gap-3">
                            <div class="w-3 h-3 rounded-full bg-blue-500 mt-1.5"></div>
                            <div>
                                <p class="text-white">ไรเดอร์รับงาน</p>
                                <p class="text-gray-400 text-sm">{{ $job->accepted_at->thaidate('j M Y H:i:s') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($job->picked_up_at)
                        <div class="flex items-start gap-3">
                            <div class="w-3 h-3 rounded-full bg-purple-500 mt-1.5"></div>
                            <div>
                                <p class="text-white">รับของแล้ว</p>
                                <p class="text-gray-400 text-sm">{{ $job->picked_up_at->thaidate('j M Y H:i:s') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($job->delivered_at)
                        <div class="flex items-start gap-3">
                            <div class="w-3 h-3 rounded-full bg-teal-500 mt-1.5"></div>
                            <div>
                                <p class="text-white">ส่งแล้ว</p>
                                <p class="text-gray-400 text-sm">{{ $job->delivered_at->thaidate('j M Y H:i:s') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($job->completed_at)
                        <div class="flex items-start gap-3">
                            <div class="w-3 h-3 rounded-full bg-green-500 mt-1.5"></div>
                            <div>
                                <p class="text-white">เสร็จสิ้น</p>
                                <p class="text-gray-400 text-sm">{{ $job->completed_at->thaidate('j M Y H:i:s') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($job->cancelled_at)
                        <div class="flex items-start gap-3">
                            <div class="w-3 h-3 rounded-full bg-red-500 mt-1.5"></div>
                            <div>
                                <p class="text-white">ยกเลิก</p>
                                <p class="text-gray-400 text-sm">{{ $job->cancelled_at->thaidate('j M Y H:i:s') }}</p>
                                @if($job->cancellation_reason)
                                    <p class="text-red-400 text-sm mt-1">เหตุผล: {{ $job->cancellation_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            @if(!in_array($job->status, ['completed', 'cancelled']))
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-cog text-gray-400"></i>
                        การจัดการ
                    </h3>
                    <div class="space-y-3">
                        <button onclick="cancelJob()"
                                class="w-full py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl font-medium transition">
                            <i class="fas fa-times mr-2"></i>ยกเลิกงาน
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
<div id="cancelModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm">
    <div class="bg-gray-900 rounded-2xl p-6 w-full max-w-md border border-white/10">
        <h3 class="text-xl font-bold text-white mb-4">ยกเลิกงาน</h3>
        <form method="POST" action="{{ route('admin.rider-jobs.cancel', $job) }}">
            @csrf
            <div class="mb-4">
                <label class="text-gray-300 block mb-2">เหตุผล</label>
                <textarea name="reason" required rows="4"
                          class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-white focus:ring-purple-500 focus:border-purple-500"
                          placeholder="ระบุเหตุผลในการยกเลิก..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeCancelModal()"
                        class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 py-3 bg-red-600 hover:bg-red-500 text-white rounded-xl transition">
                    ยืนยันยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@if($job->pickup_latitude && $job->delivery_latitude)
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key', '') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pickup = { lat: {{ $job->pickup_latitude }}, lng: {{ $job->pickup_longitude }} };
        const delivery = { lat: {{ $job->delivery_latitude }}, lng: {{ $job->delivery_longitude }} };

        const darkStyle = [
            { elementType: 'geometry', stylers: [{ color: '#242f3e' }] },
            { elementType: 'labels.text.stroke', stylers: [{ color: '#242f3e' }] },
            { elementType: 'labels.text.fill', stylers: [{ color: '#746855' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
            { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#17263c' }] },
        ];

        const map = new google.maps.Map(document.getElementById('jobMap'), {
            zoom: 14,
            center: pickup,
            styles: darkStyle,
            disableDefaultUI: true,
            zoomControl: true,
        });

        // Pickup marker
        new google.maps.Marker({
            position: pickup,
            map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 12,
                fillColor: '#22c55e',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 2,
            },
            title: 'จุดรับ',
        });

        // Delivery marker
        new google.maps.Marker({
            position: delivery,
            map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 12,
                fillColor: '#ef4444',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 2,
            },
            title: 'จุดส่ง',
        });

        // Draw line
        new google.maps.Polyline({
            path: [pickup, delivery],
            geodesic: true,
            strokeColor: '#9333ea',
            strokeOpacity: 0.8,
            strokeWeight: 4,
            map: map,
        });

        // Fit bounds
        const bounds = new google.maps.LatLngBounds();
        bounds.extend(pickup);
        bounds.extend(delivery);
        map.fitBounds(bounds);
    });
</script>
@endif
<script>
    function cancelJob() {
        document.getElementById('cancelModal').classList.remove('hidden');
        document.getElementById('cancelModal').classList.add('flex');
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').classList.add('hidden');
        document.getElementById('cancelModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
