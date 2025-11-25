{{--
/**
 * OCR Settings V3 - การตั้งค่า Google Cloud Vision API สำหรับ KYC
 *
 * @uses layouts/admin-v3.blade.php
 * @data $ocrSettings - การตั้งค่า OCR ทั้งหมด
 * @data $credentialsExists - มีไฟล์ credentials หรือไม่
 * @data $credentialsInfo - ข้อมูล credentials
 * @data $apiStatus - สถานะ API จาก ThaiIdCardOcrService
 *
 * @version 3.0 - Tailwind CSS + Alpine.js
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'การตั้งค่า OCR - Google Cloud Vision')
@section('page-title', 'OCR Settings')

@section('content')
<div class="space-y-6" x-data="ocrSettings()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                <i class="fas fa-id-card-alt mr-2"></i>
                การตั้งค่า OCR - Google Cloud Vision
            </h1>
            <p class="text-white/70 text-sm mt-1">
                จัดการ API Key หรือ Service Account สำหรับอ่านข้อมูลจากบัตรประชาชน/ใบขับขี่
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.settings.ocr.setup-guide') }}"
               target="_blank"
               class="btn-secondary">
                <i class="fas fa-book mr-2"></i>
                คู่มือการตั้งค่า
            </a>
            <button @click="testConnection"
                    class="btn-accent"
                    :disabled="testing">
                <i class="fas fa-flask mr-2" :class="{'fa-spin': testing}"></i>
                <span x-text="testing ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
            </button>
        </div>
    </div>

    {{-- API Status Card --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30">
        <h3 class="text-xl font-bold text-white mb-4">
            <i class="fas fa-signal mr-2"></i>
            สถานะการเชื่อมต่อ API
        </h3>

        @if($apiStatus && $apiStatus['configured'])
            <div class="bg-green-500/20 border border-green-500/50 rounded-xl p-4 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center">
                        <i class="fas fa-check text-white text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-green-300 font-bold text-lg">API พร้อมใช้งาน</h4>
                        <p class="text-white/70">{{ $apiStatus['method'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- API Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if(!empty($apiStatus['api_key_masked']))
                <div class="bg-white/10 rounded-lg p-4">
                    <div class="text-white/60 text-sm mb-1">API Key</div>
                    <div class="text-white font-mono text-sm">{{ $apiStatus['api_key_masked'] }}</div>
                </div>
                @endif

                @if(!empty($apiStatus['credentials_file']))
                <div class="bg-white/10 rounded-lg p-4">
                    <div class="text-white/60 text-sm mb-1">Credentials File</div>
                    <div class="text-white text-sm truncate">{{ basename($apiStatus['credentials_file']) }}</div>
                </div>
                @endif

                <div class="bg-white/10 rounded-lg p-4">
                    <div class="text-white/60 text-sm mb-1">วิธีการเชื่อมต่อ</div>
                    <div class="text-white text-sm">{{ $apiStatus['method'] ?? 'N/A' }}</div>
                </div>
            </div>

            {{-- Capabilities --}}
            @if(!empty($apiStatus['capabilities']))
            <div class="mt-6">
                <h4 class="text-white font-bold mb-3">
                    <i class="fas fa-list-check mr-2"></i>
                    ความสามารถที่รองรับ
                </h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($apiStatus['capabilities'] as $capability => $enabled)
                        <span class="px-3 py-1 rounded-full text-sm {{ $enabled ? 'bg-green-500/30 text-green-300' : 'bg-gray-500/30 text-gray-300' }}">
                            <i class="fas {{ $enabled ? 'fa-check' : 'fa-times' }} mr-1"></i>
                            {{ str_replace('_', ' ', ucfirst($capability)) }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

        @else
            <div class="bg-red-500/20 border border-red-500/50 rounded-xl p-4 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-red-500 flex items-center justify-center">
                        <i class="fas fa-times text-white text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-red-300 font-bold text-lg">ยังไม่ได้ตั้งค่า API</h4>
                        <p class="text-white/70">{{ $apiStatus['error'] ?? 'กรุณาตั้งค่า API Key หรืออัปโหลด Service Account credentials' }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Settings Form --}}
    <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
        {{-- Tabs --}}
        <div class="flex border-b border-white/20 overflow-x-auto">
            <template x-for="tab in tabs" :key="tab.id">
                <button
                    @click="currentTab = tab.id"
                    :class="{
                        'bg-white/20 border-b-2 border-blue-400': currentTab === tab.id,
                        'hover:bg-white/10': currentTab !== tab.id
                    }"
                    class="px-6 py-4 text-white font-medium transition whitespace-nowrap flex items-center gap-2"
                >
                    <i :class="tab.icon"></i>
                    <span x-text="tab.label"></span>
                </button>
            </template>
        </div>

        <div class="p-6">
            {{-- Tab 1: API Key (แนะนำ) --}}
            <div x-show="currentTab === 'api_key'" x-transition class="space-y-6">
                <div class="bg-blue-500/20 border border-blue-500/50 rounded-lg p-4 mb-4">
                    <h4 class="text-blue-300 font-bold mb-2">
                        <i class="fas fa-star mr-2"></i>
                        วิธีที่ 1: ใช้ API Key (แนะนำ)
                    </h4>
                    <p class="text-white/80 text-sm">
                        วิธีนี้ง่ายที่สุด และสามารถใช้ร่วมกับ Google Maps API Key เดิมได้
                        (ถ้าเปิดใช้งาน Cloud Vision API ใน Google Cloud Console)
                    </p>
                </div>

                {{-- Cloud Vision API Key --}}
                <div>
                    <label class="block text-white/90 font-medium mb-2">
                        <i class="fas fa-key mr-1"></i>
                        Cloud Vision API Key
                    </label>
                    <input
                        type="text"
                        x-model="form.cloud_vision_api_key"
                        class="input-glass w-full font-mono text-sm"
                        placeholder="AIzaSy..."
                    >
                    <p class="text-white/50 text-xs mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        หากไม่ระบุ ระบบจะใช้ Google Maps API Key แทน (ถ้ามี)
                    </p>
                </div>

                {{-- Enable OCR --}}
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-lg">
                    <div>
                        <span class="text-white font-medium">เปิดใช้งาน OCR</span>
                        <p class="text-white/50 text-xs">เปิด/ปิดระบบอ่านบัตรประชาชนอัตโนมัติ</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.google_vision_enabled" class="sr-only peer">
                        <div class="toggle-switch"></div>
                    </label>
                </div>

                {{-- Save Button --}}
                <button @click="saveSettings"
                        class="btn-primary w-full"
                        :disabled="saving">
                    <i class="fas fa-save mr-2"></i>
                    <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า'"></span>
                </button>
            </div>

            {{-- Tab 2: Service Account --}}
            <div x-show="currentTab === 'service_account'" x-transition class="space-y-6">
                <div class="bg-yellow-500/20 border border-yellow-500/50 rounded-lg p-4 mb-4">
                    <h4 class="text-yellow-300 font-bold mb-2">
                        <i class="fas fa-file-code mr-2"></i>
                        วิธีที่ 2: ใช้ Service Account (สำหรับ Server)
                    </h4>
                    <p class="text-white/80 text-sm">
                        วิธีนี้เหมาะสำหรับการใช้งานบน Server และมีความสามารถมากกว่า API Key
                        แต่ต้องอัปโหลดไฟล์ JSON credentials
                    </p>
                </div>

                {{-- Current Credentials Status --}}
                @if($credentialsExists && $credentialsInfo)
                <div class="bg-green-500/20 border border-green-500/50 rounded-lg p-4">
                    <h4 class="text-green-300 font-bold mb-3">
                        <i class="fas fa-check-circle mr-2"></i>
                        พบไฟล์ Credentials
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <span class="text-white/60 text-sm">Project ID</span>
                            <p class="text-white font-mono text-sm">{{ $credentialsInfo['project_id'] }}</p>
                        </div>
                        <div>
                            <span class="text-white/60 text-sm">Client Email</span>
                            <p class="text-white font-mono text-sm truncate">{{ $credentialsInfo['client_email'] }}</p>
                        </div>
                        <div>
                            <span class="text-white/60 text-sm">Type</span>
                            <p class="text-white font-mono text-sm">{{ $credentialsInfo['type'] }}</p>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-500/20 border border-gray-500/50 rounded-lg p-4">
                    <p class="text-white/70">
                        <i class="fas fa-info-circle mr-2"></i>
                        ยังไม่พบไฟล์ credentials - อัปโหลดไฟล์ JSON ที่ได้จาก Google Cloud Console
                    </p>
                </div>
                @endif

                {{-- Upload Form --}}
                <form action="{{ route('admin.settings.ocr.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-white/90 font-medium mb-2">
                            <i class="fas fa-upload mr-1"></i>
                            อัปโหลดไฟล์ Service Account JSON
                        </label>
                        <input
                            type="file"
                            name="credentials_file"
                            accept=".json"
                            class="block w-full text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-500 file:text-white hover:file:bg-blue-600"
                        >
                        <p class="text-white/50 text-xs mt-1">
                            ดาวน์โหลดไฟล์จาก Google Cloud Console > IAM & Admin > Service Accounts
                        </p>
                    </div>

                    <div>
                        <label class="block text-white/90 font-medium mb-2">
                            <i class="fas fa-project-diagram mr-1"></i>
                            Project ID (ถ้ามี)
                        </label>
                        <input
                            type="text"
                            name="google_vision_project_id"
                            value="{{ $ocrSettings['google_vision_project_id']->value ?? '' }}"
                            class="input-glass w-full"
                            placeholder="my-project-id"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="google_vision_enabled" value="0">
                        <input
                            type="checkbox"
                            name="google_vision_enabled"
                            value="1"
                            id="google_vision_enabled"
                            {{ ($ocrSettings['google_vision_enabled']->value ?? 0) ? 'checked' : '' }}
                            class="w-5 h-5 rounded"
                        >
                        <label for="google_vision_enabled" class="text-white">เปิดใช้งาน OCR</label>
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกการตั้งค่า
                    </button>
                </form>
            </div>

            {{-- Tab 3: Usage Guide --}}
            <div x-show="currentTab === 'guide'" x-transition class="space-y-6">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-book mr-2"></i>
                    คู่มือการใช้งาน
                </h3>

                {{-- Step 1: Create Project --}}
                <div class="bg-white/10 rounded-lg p-4">
                    <h4 class="text-white font-bold mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-sm mr-2">1</span>
                        สร้างโปรเจค Google Cloud
                    </h4>
                    <ol class="text-white/80 text-sm list-decimal list-inside space-y-1 ml-8">
                        <li>ไปที่ <a href="https://console.cloud.google.com" target="_blank" class="text-blue-400 hover:underline">Google Cloud Console</a></li>
                        <li>สร้างโปรเจคใหม่หรือเลือกโปรเจคที่มีอยู่</li>
                        <li>เปิดใช้งาน Billing (ต้องมีบัญชี billing ถึงจะใช้ API ได้)</li>
                    </ol>
                </div>

                {{-- Step 2: Enable Vision API --}}
                <div class="bg-white/10 rounded-lg p-4">
                    <h4 class="text-white font-bold mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-sm mr-2">2</span>
                        เปิดใช้งาน Cloud Vision API
                    </h4>
                    <ol class="text-white/80 text-sm list-decimal list-inside space-y-1 ml-8">
                        <li>ไปที่ <a href="https://console.cloud.google.com/apis/library/vision.googleapis.com" target="_blank" class="text-blue-400 hover:underline">Cloud Vision API</a></li>
                        <li>คลิก "Enable" เพื่อเปิดใช้งาน</li>
                    </ol>
                </div>

                {{-- Step 3A: API Key --}}
                <div class="bg-green-500/10 rounded-lg p-4 border border-green-500/30">
                    <h4 class="text-white font-bold mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white text-sm mr-2">3A</span>
                        สร้าง API Key (วิธีง่าย)
                    </h4>
                    <ol class="text-white/80 text-sm list-decimal list-inside space-y-1 ml-8">
                        <li>ไปที่ <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-400 hover:underline">APIs & Services > Credentials</a></li>
                        <li>คลิก "Create Credentials" > "API Key"</li>
                        <li>คัดลอก API Key และนำมาใส่ในช่อง "Cloud Vision API Key"</li>
                        <li>แนะนำให้ Restrict API Key เฉพาะ Cloud Vision API เพื่อความปลอดภัย</li>
                    </ol>
                </div>

                {{-- Step 3B: Service Account --}}
                <div class="bg-yellow-500/10 rounded-lg p-4 border border-yellow-500/30">
                    <h4 class="text-white font-bold mb-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-500 text-white text-sm mr-2">3B</span>
                        สร้าง Service Account (วิธีขั้นสูง)
                    </h4>
                    <ol class="text-white/80 text-sm list-decimal list-inside space-y-1 ml-8">
                        <li>ไปที่ <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" class="text-blue-400 hover:underline">IAM & Admin > Service Accounts</a></li>
                        <li>คลิก "Create Service Account"</li>
                        <li>ตั้งชื่อ (เช่น "vision-api-service")</li>
                        <li>ให้ Role: "Cloud Vision API User" หรือ "Editor"</li>
                        <li>คลิก "Create Key" > "JSON" แล้วดาวน์โหลดไฟล์</li>
                        <li>อัปโหลดไฟล์ JSON ในแท็บ "Service Account"</li>
                    </ol>
                </div>

                {{-- Pricing Info --}}
                <div class="bg-purple-500/20 border border-purple-500/50 rounded-lg p-4">
                    <h4 class="text-purple-300 font-bold mb-2">
                        <i class="fas fa-dollar-sign mr-2"></i>
                        ค่าใช้จ่าย
                    </h4>
                    <ul class="text-white/80 text-sm space-y-1 list-disc list-inside">
                        <li><strong>ฟรี:</strong> 1,000 requests/เดือน สำหรับ Text Detection</li>
                        <li><strong>เกิน 1,000:</strong> $1.50 ต่อ 1,000 requests</li>
                        <li>ดูรายละเอียดที่ <a href="https://cloud.google.com/vision/pricing" target="_blank" class="text-blue-400 hover:underline">Cloud Vision Pricing</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ocrSettings() {
    return {
        currentTab: 'api_key',
        saving: false,
        testing: false,

        tabs: [
            { id: 'api_key', label: 'API Key (แนะนำ)', icon: 'fas fa-key' },
            { id: 'service_account', label: 'Service Account', icon: 'fas fa-file-code' },
            { id: 'guide', label: 'คู่มือการตั้งค่า', icon: 'fas fa-book' },
        ],

        form: {
            cloud_vision_api_key: '{{ $ocrSettings["cloud_vision_api_key"]->value ?? "" }}',
            google_vision_enabled: {{ ($ocrSettings["google_vision_enabled"]->value ?? 0) ? 'true' : 'false' }},
        },

        async saveSettings() {
            this.saving = true;

            try {
                const response = await fetch('{{ route("admin.settings.ocr.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message);
                    // Refresh page to update API status
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showToast('error', data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Save error:', error);
                this.showToast('error', 'เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        },

        async testConnection() {
            this.testing = true;

            try {
                const response = await fetch('{{ route("admin.settings.ocr.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.showToast('success', data.message);
                    console.log('API Status:', data.data);
                } else {
                    this.showToast('error', data.message);
                }
            } catch (error) {
                console.error('Test error:', error);
                this.showToast('error', 'เกิดข้อผิดพลาดในการทดสอบ');
            } finally {
                this.testing = false;
            }
        },

        showToast(type, message) {
            // ใช้ระบบ toast ของ admin-v3 layout
            if (window.showNotification) {
                window.showNotification(type, message);
            } else {
                alert(message);
            }
        },
    };
}
</script>
@endpush
@endsection
