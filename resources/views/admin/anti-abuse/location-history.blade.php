{{--
    Location History Viewer - ดูประวัติตำแหน่ง GPS
    แสดงแผนที่และเส้นทางการเดินทางของผู้ใช้และผู้ให้บริการ
--}}
@extends('layouts.admin-v3')

@section('title', 'ประวัติตำแหน่ง GPS')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="locationHistoryViewer()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-map-marked-alt text-green-500 mr-3"></i>
                    ประวัติตำแหน่ง GPS
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    ดูเส้นทางการเดินทางและตำแหน่งของผู้ใช้และผู้ให้บริการ
                </p>
            </div>
            <a href="{{ route('admin.anti-abuse.dashboard') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับไป Dashboard
            </a>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" action="{{ route('admin.anti-abuse.location-history') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Booking ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        รหัสการจอง
                    </label>
                    <input type="number" name="booking_id" value="{{ request('booking_id') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500"
                           placeholder="เช่น 12345">
                </div>

                {{-- User ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        User ID
                    </label>
                    <input type="number" name="user_id" value="{{ request('user_id') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500"
                           placeholder="เช่น 123">
                </div>

                {{-- Provider ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Provider ID
                    </label>
                    <input type="number" name="provider_id" value="{{ request('provider_id') }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500"
                           placeholder="เช่น 456">
                </div>

                {{-- Date Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        วันที่
                    </label>
                    <input type="date" name="date" value="{{ request('date', now()->format('Y-m-d')) }}"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-green-500 focus:border-green-500">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="show_suspected" value="1" {{ request('show_suspected') ? 'checked' : '' }}
                               class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงเฉพาะที่สงสัย GPS Spoofing</span>
                    </label>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-search mr-2"></i>
                        ค้นหา
                    </button>
                    <a href="{{ route('admin.anti-abuse.location-history') }}"
                       class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if($locationLogs->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Map View --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            <i class="fas fa-map text-green-500 mr-2"></i>
                            แผนที่เส้นทาง
                        </h3>
                    </div>
                    <div id="location-map" class="h-[500px] w-full"></div>
                </div>

                {{-- Map Legend --}}
                <div class="mt-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">สัญลักษณ์</h4>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full bg-blue-500 mr-2"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">ผู้ใช้บริการ</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full bg-green-500 mr-2"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">ผู้ให้บริการ</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full bg-red-500 mr-2"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">สงสัย GPS Spoofing</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-1 bg-blue-500 mr-2"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">เส้นทางผู้ใช้</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-1 bg-green-500 mr-2"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">เส้นทางผู้ให้บริการ</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Location Log List --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            <i class="fas fa-history text-blue-500 mr-2"></i>
                            ประวัติตำแหน่ง ({{ $locationLogs->count() }} รายการ)
                        </h3>
                    </div>
                    <div class="max-h-[600px] overflow-y-auto">
                        @foreach($locationLogs as $index => $log)
                            <div class="p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer"
                                 @click="focusLocation({{ $log->latitude }}, {{ $log->longitude }}, {{ $index }})">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3">
                                        {{-- Entity Icon --}}
                                        <div class="flex-shrink-0">
                                            @if($log->entity_type === 'user')
                                                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                    <i class="fas fa-user text-blue-500 text-sm"></i>
                                                </div>
                                            @else
                                                <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                                    <i class="fas fa-store text-green-500 text-sm"></i>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Info --}}
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $log->entity_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ' }}
                                                #{{ $log->entity_type === 'user' ? $log->user_id : $log->provider_id }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                {{ number_format($log->latitude, 6) }}, {{ number_format($log->longitude, 6) }}
                                            </div>
                                            @if($log->accuracy)
                                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                                    ความแม่นยำ: ±{{ number_format($log->accuracy, 0) }}m
                                                </div>
                                            @endif
                                            @if($log->speed)
                                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                                    ความเร็ว: {{ number_format($log->speed * 3.6, 1) }} km/h
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Time & Flags --}}
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $log->created_at->format('H:i:s') }}
                                        </div>
                                        @if($log->is_mock_location)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 mt-1">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Mock
                                            </span>
                                        @endif
                                        @if($log->suspected_spoofing)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 mt-1">
                                                <i class="fas fa-question-circle mr-1"></i>
                                                Spoofing?
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Stats Summary --}}
                @if($locationLogs->count() > 0)
                    <div class="mt-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">สรุป</h4>
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $locationLogs->where('entity_type', 'user')->count() }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">จุดตำแหน่งผู้ใช้</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ $locationLogs->where('entity_type', 'provider')->count() }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">จุดตำแหน่งผู้ให้บริการ</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                                    {{ $locationLogs->where('suspected_spoofing', true)->count() }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">สงสัย Spoofing</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    {{ $locationLogs->where('is_mock_location', true)->count() }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Mock Location</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
            <i class="fas fa-map-marked-alt text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-700 dark:text-gray-300 mb-2">ไม่พบข้อมูลตำแหน่ง</h3>
            <p class="text-gray-500 dark:text-gray-400">กรุณาระบุรหัสการจอง, User ID หรือ Provider ID เพื่อค้นหา</p>
        </div>
    @endif
</div>

@push('scripts')
{{-- เตรียมข้อมูลสำหรับ Map --}}
@php
$mapLocations = $locationLogs->map(function($log) {
    return [
        'lat' => $log->latitude,
        'lng' => $log->longitude,
        'type' => $log->entity_type,
        'time' => $log->created_at->format('H:i:s'),
        'suspected' => $log->suspected_spoofing,
        'mock' => $log->is_mock_location,
        'accuracy' => $log->accuracy,
        'speed' => $log->speed,
    ];
})->values();
@endphp

{{-- Google Maps API --}}
<script>
let map;
let markers = [];
let userPath = [];
let providerPath = [];

function initMap() {
    // ตำแหน่งเริ่มต้น (กรุงเทพฯ)
    const defaultCenter = { lat: 13.7563, lng: 100.5018 };

    map = new google.maps.Map(document.getElementById('location-map'), {
        zoom: 14,
        center: defaultCenter,
        styles: [
            { featureType: 'poi', stylers: [{ visibility: 'simplified' }] }
        ]
    });

    // โหลดข้อมูลตำแหน่ง
    const locations = @json($mapLocations);

    if (locations.length === 0) return;

    // แยกตำแหน่ง User และ Provider
    const userLocations = locations.filter(l => l.type === 'user');
    const providerLocations = locations.filter(l => l.type === 'provider');

    // สร้าง markers และ paths
    const bounds = new google.maps.LatLngBounds();

    // User markers และ path
    userLocations.forEach((loc, index) => {
        const position = { lat: loc.lat, lng: loc.lng };
        bounds.extend(position);

        let iconColor = '#3B82F6'; // blue
        if (loc.suspected || loc.mock) {
            iconColor = '#EF4444'; // red
        }

        const marker = new google.maps.Marker({
            position: position,
            map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: iconColor,
                fillOpacity: 0.8,
                strokeColor: '#ffffff',
                strokeWeight: 2
            },
            title: `ผู้ใช้ - ${loc.time}`
        });

        // Info window
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div class="p-2">
                    <strong>ผู้ใช้บริการ</strong><br>
                    <small>เวลา: ${loc.time}</small><br>
                    <small>ความแม่นยำ: ±${loc.accuracy || 'N/A'}m</small><br>
                    ${loc.speed ? `<small>ความเร็ว: ${(loc.speed * 3.6).toFixed(1)} km/h</small><br>` : ''}
                    ${loc.suspected ? '<span style="color:red">⚠️ สงสัย Spoofing</span><br>' : ''}
                    ${loc.mock ? '<span style="color:red">⚠️ Mock Location</span>' : ''}
                </div>
            `
        });

        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });

        markers.push(marker);
        userPath.push(position);
    });

    // Provider markers และ path
    providerLocations.forEach((loc, index) => {
        const position = { lat: loc.lat, lng: loc.lng };
        bounds.extend(position);

        let iconColor = '#10B981'; // green
        if (loc.suspected || loc.mock) {
            iconColor = '#EF4444'; // red
        }

        const marker = new google.maps.Marker({
            position: position,
            map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: iconColor,
                fillOpacity: 0.8,
                strokeColor: '#ffffff',
                strokeWeight: 2
            },
            title: `ผู้ให้บริการ - ${loc.time}`
        });

        // Info window
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div class="p-2">
                    <strong>ผู้ให้บริการ</strong><br>
                    <small>เวลา: ${loc.time}</small><br>
                    <small>ความแม่นยำ: ±${loc.accuracy || 'N/A'}m</small><br>
                    ${loc.speed ? `<small>ความเร็ว: ${(loc.speed * 3.6).toFixed(1)} km/h</small><br>` : ''}
                    ${loc.suspected ? '<span style="color:red">⚠️ สงสัย Spoofing</span><br>' : ''}
                    ${loc.mock ? '<span style="color:red">⚠️ Mock Location</span>' : ''}
                </div>
            `
        });

        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });

        markers.push(marker);
        providerPath.push(position);
    });

    // วาดเส้นทาง User
    if (userPath.length > 1) {
        new google.maps.Polyline({
            path: userPath,
            geodesic: true,
            strokeColor: '#3B82F6',
            strokeOpacity: 0.8,
            strokeWeight: 3,
            map: map
        });
    }

    // วาดเส้นทาง Provider
    if (providerPath.length > 1) {
        new google.maps.Polyline({
            path: providerPath,
            geodesic: true,
            strokeColor: '#10B981',
            strokeOpacity: 0.8,
            strokeWeight: 3,
            map: map
        });
    }

    // Fit bounds
    if (locations.length > 0) {
        map.fitBounds(bounds);
    }
}

function locationHistoryViewer() {
    return {
        focusLocation(lat, lng, index) {
            if (map && markers[index]) {
                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(16);
                google.maps.event.trigger(markers[index], 'click');
            }
        }
    }
}

// โหลด Google Maps
document.addEventListener('DOMContentLoaded', function() {
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key', '') }}&callback=initMap`;
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
});
</script>
@endpush
@endsection
