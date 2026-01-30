@extends('layouts.admin-v3')

@section('title', 'QR Code - ' . ($device->device_name ?? $device->device_id))

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                QR Code ตั้งค่าอุปกรณ์
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

            {{-- QR Code Container --}}
            <div id="qrcode-container" class="flex justify-center mb-4">
                <div id="qr-loading" class="w-[280px] h-[280px] bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                    <div class="text-gray-400">
                        <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                        <p class="text-sm">กำลังสร้าง QR Code...</p>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">
                เปิดแอพ SMS Checker → กดสแกน QR → เล็งกล้องไปที่ QR Code นี้
            </p>

            {{-- ปุ่มดาวน์โหลด QR Code --}}
            <button id="download-qr" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold hidden">
                <i class="fas fa-download mr-1"></i> ดาวน์โหลด QR Code
            </button>
        </div>

        {{-- ข้อมูลการตั้งค่า (Manual) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                ข้อมูลการตั้งค่า (กรอกเอง)
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
                        Secret Key เป็นข้อมูลลับ ห้ามเปิดเผยให้บุคคลอื่น
                        <br>ใช้สำหรับเข้ารหัสและลงนาม SMS data ระหว่างอุปกรณ์กับ server
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- QR Code Library (qrcodejs) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var qrData = @json($qrData);
    var qrText = typeof qrData === 'string' ? qrData : JSON.stringify(qrData);
    var container = document.getElementById('qrcode-container');
    var loading = document.getElementById('qr-loading');
    var downloadBtn = document.getElementById('download-qr');

    if (typeof QRCode !== 'undefined') {
        // ลบ loading indicator
        loading.remove();

        // สร้าง div สำหรับ QR Code
        var qrDiv = document.createElement('div');
        qrDiv.id = 'qrcode';
        container.appendChild(qrDiv);

        // สร้าง QR Code
        var qr = new QRCode(qrDiv, {
            text: qrText,
            width: 280,
            height: 280,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M,
        });

        // แสดงปุ่มดาวน์โหลด
        downloadBtn.classList.remove('hidden');
        downloadBtn.addEventListener('click', function() {
            // รอให้ QR Code render เสร็จ
            setTimeout(function() {
                var canvas = qrDiv.querySelector('canvas');
                var img = qrDiv.querySelector('img');
                if (canvas) {
                    var link = document.createElement('a');
                    link.download = 'smschecker-qr-{{ $device->device_id }}.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                } else if (img) {
                    var link = document.createElement('a');
                    link.download = 'smschecker-qr-{{ $device->device_id }}.png';
                    link.href = img.src;
                    link.click();
                }
            }, 100);
        });
    } else {
        // Fallback: แสดง JSON data ให้ copy เมื่อโหลด library ไม่ได้
        loading.remove();
        var fallback = document.createElement('div');
        fallback.className = 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 text-left max-w-sm mx-auto';
        fallback.innerHTML = '<p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 mb-2">' +
            '<i class="fas fa-exclamation-triangle mr-1"></i>ไม่สามารถโหลด QR Code library ได้</p>' +
            '<p class="text-xs text-yellow-700 dark:text-yellow-400 mb-3">กรุณาใช้ข้อมูลด้านขวากรอกในแอพแทน หรือลองรีเฟรชหน้านี้</p>' +
            '<pre class="text-xs bg-white dark:bg-gray-800 rounded p-3 text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all border">' +
            JSON.stringify(JSON.parse(qrText), null, 2) + '</pre>';
        container.appendChild(fallback);
    }
});
</script>
@endpush
