@extends('layouts.admin')

@section('title', 'ตั้งค่า OCR (Google Cloud Vision)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตั้งค่า OCR (Optical Character Recognition)</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                การตั้งค่าระบบอ่านข้อความจากภาพ สำหรับการตรวจจับข้อมูลบัตรประชาชนและใบขับขี่
            </p>
        </div>
        <a href="{{ route('admin.settings.ocr.setup-guide') }}"
           target="_blank"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
            <i class="fas fa-book"></i>
            <span>คู่มือการติดตั้ง</span>
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <!-- Status Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-600"></i>
                สถานะระบบ OCR
            </h2>
            <button type="button"
                    onclick="testConnection()"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                <i class="fas fa-plug"></i>
                <span>ทดสอบการเชื่อมต่อ</span>
            </button>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Google Vision Status -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-cloud text-2xl text-blue-600"></i>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Google Cloud Vision API</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">สถานะ:
                            @if(\App\Models\Setting::get('google_vision_enabled'))
                                <span class="text-green-600 font-semibold">เปิดใช้งาน</span>
                            @else
                                <span class="text-red-600 font-semibold">ปิดใช้งาน</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Credentials Status -->
            <div class="bg-{{ $credentialsExists ? 'green' : 'yellow' }}-50 dark:bg-{{ $credentialsExists ? 'green' : 'yellow' }}-900/20 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-{{ $credentialsExists ? 'check-circle' : 'exclamation-triangle' }} text-2xl text-{{ $credentialsExists ? 'green' : 'yellow' }}-600"></i>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">ไฟล์ Credentials</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @if($credentialsExists)
                                <span class="text-green-600 font-semibold">พบไฟล์แล้ว</span>
                            @else
                                <span class="text-yellow-600 font-semibold">ยังไม่ได้อัปโหลด</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Credentials Info -->
        @if($credentialsExists && $credentialsInfo)
            <div class="mt-4 p-4 bg-gray-50 dark:bg-slate-700 rounded-lg">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-3">ข้อมูล Service Account</h4>
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Project ID:</span>
                        <p class="font-medium text-gray-900 dark:text-white break-all">{{ $credentialsInfo['project_id'] }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Client Email:</span>
                        <p class="font-medium text-gray-900 dark:text-white break-all">{{ $credentialsInfo['client_email'] }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Type:</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $credentialsInfo['type'] }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Settings Form -->
    <form action="{{ route('admin.settings.ocr.update') }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        @csrf

        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-cog text-indigo-600"></i>
            การตั้งค่า
        </h2>

        <!-- Enable/Disable -->
        <div class="mb-6">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox"
                       name="google_vision_enabled"
                       value="1"
                       {{ \App\Models\Setting::get('google_vision_enabled') ? 'checked' : '' }}
                       class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">
                    เปิดใช้งาน Google Cloud Vision API
                </span>
            </label>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 ml-8">
                เมื่อเปิดใช้งาน ระบบจะใช้ Google Cloud Vision API ในการอ่านข้อความจากภาพบัตรประชาชนและใบขับขี่
            </p>
        </div>

        <!-- Project ID -->
        <div class="mb-6">
            <label for="google_vision_project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Google Cloud Project ID (ไม่บังคับ)
            </label>
            <input type="text"
                   name="google_vision_project_id"
                   id="google_vision_project_id"
                   value="{{ \App\Models\Setting::get('google_vision_project_id') }}"
                   placeholder="my-project-id"
                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-slate-700 dark:text-white">
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                รหัสโปรเจกต์จาก Google Cloud Console (โดยปกติจะอยู่ในไฟล์ credentials แล้ว)
            </p>
        </div>

        <!-- Credentials File Upload -->
        <div class="mb-6">
            <label for="credentials_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                อัปโหลดไฟล์ Service Account Key (JSON)
            </label>
            <input type="file"
                   name="credentials_file"
                   id="credentials_file"
                   accept=".json"
                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-slate-700 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                ไฟล์ JSON ที่ดาวน์โหลดจาก Google Cloud Console (ขนาดไม่เกิน 1MB)
            </p>
            @if($credentialsExists)
                <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                    <i class="fas fa-info-circle"></i> มีไฟล์อยู่แล้ว อัปโหลดใหม่เพื่อแทนที่ (ไฟล์เก่าจะถูกสำรองอัตโนมัติ)
                </p>
            @endif
        </div>

        <!-- Info Alert -->
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-semibold mb-2">ขั้นตอนการตั้งค่า Google Cloud Vision API:</p>
                    <ol class="list-decimal list-inside space-y-1 ml-2">
                        <li>สร้างโปรเจกต์ใหม่หรือเลือกโปรเจกต์ที่มีอยู่ใน Google Cloud Console</li>
                        <li>เปิดใช้งาน Cloud Vision API สำหรับโปรเจกต์</li>
                        <li>สร้าง Service Account และดาวน์โหลดไฟล์ Key (JSON)</li>
                        <li>อัปโหลดไฟล์ JSON ที่นี่</li>
                        <li>ทดสอบการเชื่อมต่อ</li>
                    </ol>
                    <a href="{{ route('admin.settings.ocr.setup-guide') }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 mt-3 text-blue-700 hover:text-blue-900 font-medium">
                        <i class="fas fa-external-link-alt"></i>
                        <span>ดูคู่มือโดยละเอียด</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3">
            <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <i class="fas fa-save"></i>
                <span>บันทึกการตั้งค่า</span>
            </button>
            <a href="{{ route('admin.settings.index') }}"
               class="px-6 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">
                กลับ
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function testConnection() {
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังทดสอบ...';

    fetch('{{ route("admin.settings.ocr.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}
</script>
@endpush
@endsection
