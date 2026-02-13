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
                @if(!empty($qrCodeSvg))
                    {{-- Server-side SVG QR Code (Error Correction H = สแกนได้ไวมาก) --}}
                    <div id="qrcode-svg" class="p-4 bg-white rounded-2xl shadow-lg">
                        {!! $qrCodeSvg !!}
                    </div>
                @else
                    {{-- Loading indicator สำหรับ JS fallback --}}
                    <div id="qr-loading" class="w-[300px] h-[300px] bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <div class="text-gray-400">
                            <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                            <p class="text-sm">กำลังสร้าง QR Code...</p>
                        </div>
                    </div>
                @endif
            </div>

            @if(!empty($qrCodeSvg))
            <div class="inline-flex items-center px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full text-xs font-medium mb-3">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Error Correction H (สแกนไว)
            </div>
            @endif

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                เปิดแอพ SMS Checker → กดสแกน QR → เล็งกล้องไปที่ QR Code นี้
            </p>

            {{-- ปุ่มดาวน์โหลด QR Code --}}
            <button id="download-qr" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold {{ empty($qrCodeSvg) ? 'hidden' : '' }}">
                <i class="fas fa-download mr-1"></i> ดาวน์โหลด QR Code
            </button>

            {{-- คำเตือน --}}
            <div class="mt-4 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800">
                <p class="text-xs text-amber-700 dark:text-amber-300 flex items-start">
                    <svg class="w-4 h-4 mr-1 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    อย่าแชร์ QR Code นี้กับผู้อื่น เพราะมี API Key สำหรับเข้าถึงระบบ
                </p>
            </div>
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
{{-- QR Code JS Library (fallback เมื่อ server-side SVG ไม่พร้อม) --}}
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var qrData = @json($qrData);
    var qrText = typeof qrData === 'string' ? qrData : JSON.stringify(qrData);
    var container = document.getElementById('qrcode-container');
    var downloadBtn = document.getElementById('download-qr');
    var hasSvg = !!document.getElementById('qrcode-svg');

    if (hasSvg) {
        // Server-side SVG QR มีอยู่แล้ว → ตั้งค่าดาวน์โหลดเท่านั้น
        downloadBtn.addEventListener('click', function() {
            var svgEl = document.querySelector('#qrcode-svg svg');
            if (!svgEl) return;

            // แปลง SVG → Canvas → PNG สำหรับดาวน์โหลด
            var svgData = new XMLSerializer().serializeToString(svgEl);
            var canvas = document.createElement('canvas');
            canvas.width = 600;
            canvas.height = 600;
            var ctx = canvas.getContext('2d');

            var img = new Image();
            img.onload = function() {
                // พื้นขาว
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, 600, 600);
                ctx.drawImage(img, 0, 0, 600, 600);

                var link = document.createElement('a');
                link.download = 'smschecker-qr-{{ $device->device_id }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            };
            img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
        });
        return;
    }

    // === JS Fallback: สร้าง QR Code ด้วย qrcodejs ===
    var loading = document.getElementById('qr-loading');

    if (typeof QRCode !== 'undefined') {
        if (loading) loading.remove();

        var qrDiv = document.createElement('div');
        qrDiv.id = 'qrcode';
        qrDiv.className = 'p-4 bg-white rounded-2xl shadow-lg';
        container.appendChild(qrDiv);

        // ใช้ correctLevel H เหมือน server-side (สแกนได้ไว)
        new QRCode(qrDiv, {
            text: qrText,
            width: 300,
            height: 300,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H,
        });

        downloadBtn.classList.remove('hidden');
        downloadBtn.addEventListener('click', function() {
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
        // Fallback สุดท้าย: แสดง JSON data ให้ copy
        if (loading) loading.remove();
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
