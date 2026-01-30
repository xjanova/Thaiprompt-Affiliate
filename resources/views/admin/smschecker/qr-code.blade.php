@extends('layouts.admin-v3')

@section('title', 'QR Code - ' . ($device->device_name ?? $device->device_id))

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                📲 QR Code ตั้งค่าอุปกรณ์
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                สแกน QR Code นี้ด้วยแอพ SMS Checker บน Android
            </p>
        </div>
        <a href="{{ route('admin.smschecker.device-show', $device) }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- QR Code --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 text-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ $device->device_name ?? $device->device_id }}
            </h2>

            {{-- QR Code placeholder (ใช้ JavaScript สร้าง QR Code) --}}
            <div id="qrcode" class="flex justify-center mb-4"></div>

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                เปิดแอพ SMS Checker → กดสแกน QR → เล็งกล้องไปที่ QR Code นี้
            </p>
        </div>

        {{-- ข้อมูลการตั้งค่า (Manual) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                ⚙️ ข้อมูลการตั้งค่า (กรอกเอง)
            </h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Server URL</label>
                    <input type="text" value="{{ config('app.url') }}" readonly
                           class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Device ID</label>
                    <input type="text" value="{{ $device->device_id }}" readonly
                           class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">API Key</label>
                    <input type="text" value="{{ $device->api_key }}" readonly
                           class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono text-gray-900 dark:text-white break-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Secret Key</label>
                    <input type="text" value="{{ $device->secret_key }}" readonly
                           class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono text-gray-900 dark:text-white break-all">
                </div>

                <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-3">
                    <p class="text-xs text-red-700 dark:text-red-300">
                        ⚠️ Secret Key เป็นข้อมูลลับ ห้ามเปิดเผยให้บุคคลอื่น
                        <br>ใช้สำหรับเข้ารหัสและลงนาม SMS data ระหว่างอุปกรณ์กับ server
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- QR Code Generation Script --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var qrData = @json(json_encode($qrData));
        var container = document.getElementById('qrcode');

        // สร้าง QR Code ด้วย canvas (fallback แสดง JSON text)
        if (typeof QRCode !== 'undefined') {
            new QRCode(container, {
                text: qrData,
                width: 280,
                height: 280,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });
        } else {
            // Fallback: แสดง JSON data ให้ copy
            container.innerHTML = '<div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-left">' +
                '<p class="text-sm text-gray-500 dark:text-gray-400 mb-2">QR Code library ไม่พร้อมใช้งาน กรุณาใช้ข้อมูลด้านขวาแทน</p>' +
                '<pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all">' +
                JSON.stringify(JSON.parse(qrData), null, 2) + '</pre></div>';
        }
    });
</script>
@endsection
