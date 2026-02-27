@extends('layouts.seller')

@section('title', 'อุปกรณ์ SMS Checker')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">อุปกรณ์ SMS Checker</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการอุปกรณ์ที่ใช้รับ SMS จากธนาคาร</p>
        </div>
        <a href="{{ route('seller.sms-gateway.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
            &larr; กลับ
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
        <p class="text-sm text-green-800 dark:text-green-300">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
        <p class="text-sm text-red-800 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- QR Code สำหรับอุปกรณ์ใหม่ --}}
    @if(session('qr_data'))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6 border-2 border-indigo-300 dark:border-indigo-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สแกน QR Code เพื่อจับคู่อุปกรณ์</h3>
        <div class="flex justify-center mb-4">
            <div class="bg-white p-4 rounded-lg">
                {!! QrCode::size(200)->generate(base64_decode(session('qr_data'))) !!}
            </div>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">เปิดแอป SMS Checker แล้วสแกน QR Code นี้เพื่อเชื่อมต่ออุปกรณ์</p>
    </div>
    @endif

    {{-- ดาวน์โหลดแอป --}}
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">ยังไม่มีแอป SMS Checker?</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400">ดาวน์โหลดและติดตั้งบนมือถือ Android ที่รับ SMS จากธนาคาร</p>
            </div>
            <a href="{{ $downloadUrl }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-lg transition flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                ดาวน์โหลด APK
            </a>
        </div>
    </div>

    {{-- เพิ่มอุปกรณ์ --}}
    @if($status['can_add_device'] ?? false)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เพิ่มอุปกรณ์ใหม่</h3>
        <form action="{{ route('seller.sms-gateway.devices.create') }}" method="POST" class="flex gap-4">
            @csrf
            <input type="text" name="device_name" placeholder="ชื่ออุปกรณ์ (เช่น มือถือ Samsung A54)"
                   class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                   required>
            <button type="submit"
                    class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex-shrink-0">
                + เพิ่มอุปกรณ์
            </button>
        </form>
    </div>
    @endif

    {{-- รายการอุปกรณ์ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">รายการอุปกรณ์ ({{ $devices->count() }})</h3>
        </div>

        @forelse($devices as $device)
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 last:border-0 flex items-center justify-between">
            <div>
                <div class="font-medium text-gray-900 dark:text-white">{{ $device->device_name }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    ID: {{ $device->device_id }} | สร้างเมื่อ: {{ $device->created_at->diffForHumans() }}
                </div>
                @if($device->last_active_at)
                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    ใช้งานล่าสุด: {{ $device->last_active_at->diffForHumans() }}
                </div>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $device->status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $device->status === 'active' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                    {{ $device->status === 'active' ? 'ใช้งาน' : 'ปิดใช้งาน' }}
                </span>
                @if($device->status === 'active')
                <form action="{{ route('seller.sms-gateway.devices.delete', $device->id) }}" method="POST"
                      onsubmit="return confirm('ต้องการลบอุปกรณ์นี้?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm">ลบ</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
            <p>ยังไม่มีอุปกรณ์</p>
            <p class="text-sm mt-1">เพิ่มอุปกรณ์ด้านบนเพื่อเริ่มใช้งาน</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
