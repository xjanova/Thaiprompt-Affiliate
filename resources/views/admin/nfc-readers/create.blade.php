@extends('layouts.admin-v3')

@section('title', 'เพิ่มเครื่องอ่านบัตร NFC')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header Section --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.nfc-readers.index') }}"
           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-broadcast-tower text-blue-600 dark:text-blue-400"></i>
                เพิ่มเครื่องอ่านบัตร NFC
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                กรอกข้อมูลเครื่องอ่านบัตร NFC เพื่อเพิ่มเข้าระบบ
            </p>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg mb-6 shadow-md"
             x-data="{ show: true }"
             x-show="show"
             x-transition>
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                        <span class="font-semibold">พบข้อผิดพลาด:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-sm ml-8">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="text-red-700 dark:text-red-400 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.nfc-readers.store') }}" method="POST" x-data="{
            generatingId: false,
            generateReaderId() {
                this.generatingId = true;
                const timestamp = Date.now().toString(36);
                const random = Math.random().toString(36).substring(2, 7);
                document.getElementById('reader_id').value = `NFC-${timestamp}-${random}`.toUpperCase();
                setTimeout(() => this.generatingId = false, 500);
            }
        }">
            @csrf

            <div class="p-6 space-y-6">
                {{-- Basic Information Section --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                        ข้อมูลพื้นฐาน
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Name --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อเครื่องอ่านบัตร <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   placeholder="เช่น: เครื่องอ่านบัตรหน้าร้าน, เครื่องอ่านบัตรชั้น 2"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        {{-- Reader ID --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Reader ID <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="text"
                                       id="reader_id"
                                       name="reader_id"
                                       value="{{ old('reader_id') }}"
                                       required
                                       placeholder="NFC-XXXXX-XXXXX"
                                       class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono">
                                <button type="button"
                                        @click="generateReaderId"
                                        :disabled="generatingId"
                                        class="px-4 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition-colors flex items-center gap-2">
                                    <i class="fas fa-magic" :class="{ 'fa-spin': generatingId }"></i>
                                    <span x-show="!generatingId">สร้าง ID</span>
                                    <span x-show="generatingId">กำลังสร้าง...</span>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                รหัสเฉพาะสำหรับเครื่องอ่านบัตร (ไม่ซ้ำกับเครื่องอื่น)
                            </p>
                        </div>

                        {{-- Serial Number --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Serial Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="serial_number"
                                   value="{{ old('serial_number') }}"
                                   required
                                   placeholder="SN-XXXXXXXXXX"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Serial Number จากผู้ผลิต
                            </p>
                        </div>

                        {{-- Location --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                สถานที่ติดตั้ง <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="location"
                                   value="{{ old('location') }}"
                                   required
                                   placeholder="เช่น: ห้องสมุด ชั้น 1, ร้านกาแฟ สาขา 2"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบาย
                            </label>
                            <textarea name="description"
                                      rows="3"
                                      placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับเครื่องอ่านบัตร..."
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Network Information Section --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-network-wired text-blue-600 dark:text-blue-400"></i>
                        ข้อมูลเครือข่าย
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- IP Address --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                IP Address
                            </label>
                            <input type="text"
                                   name="ip_address"
                                   value="{{ old('ip_address') }}"
                                   placeholder="192.168.1.100"
                                   pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ที่อยู่ IP ของเครื่องอ่านบัตรในเครือข่าย
                            </p>
                        </div>

                        {{-- MAC Address --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                MAC Address
                            </label>
                            <input type="text"
                                   name="mac_address"
                                   value="{{ old('mac_address') }}"
                                   placeholder="XX:XX:XX:XX:XX:XX"
                                   maxlength="17"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ที่อยู่ MAC ของอุปกรณ์
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Settings Section --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-cog text-blue-600 dark:text-blue-400"></i>
                        การตั้งค่า (JSON)
                    </h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ตั้งค่าเพิ่มเติม (JSON Format)
                        </label>
                        <textarea name="settings"
                                  rows="5"
                                  placeholder='{"timeout": 30, "retry": 3, "beep_on_scan": true}'
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono text-sm resize-none">{{ old('settings') }}</textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ตั้งค่าเพิ่มเติมในรูปแบบ JSON (ถ้ามี)
                        </p>
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-4 rounded-b-xl">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    กรุณากรอกข้อมูลที่มีเครื่องหมาย <span class="text-red-500">*</span> ให้ครบถ้วน
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.nfc-readers.index') }}"
                       class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-semibold shadow-lg transition-all duration-200 flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        บันทึกข้อมูล
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Help Section --}}
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center gap-2">
            <i class="fas fa-lightbulb"></i>
            คำแนะนำ
        </h3>
        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5 text-blue-600 dark:text-blue-400"></i>
                <span><strong>Reader ID:</strong> ใช้ปุ่ม "สร้าง ID" เพื่อสร้างรหัสอัตโนมัติ หรือกรอกเองให้ไม่ซ้ำกับเครื่องอื่น</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5 text-blue-600 dark:text-blue-400"></i>
                <span><strong>Serial Number:</strong> ควรใช้ Serial Number จริงที่อยู่บนเครื่องอ่านบัตร</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5 text-blue-600 dark:text-blue-400"></i>
                <span><strong>IP Address:</strong> ถ้าเครื่องอ่านบัตรเชื่อมต่อผ่าน LAN/WiFi ให้กรอก IP Address</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5 text-blue-600 dark:text-blue-400"></i>
                <span><strong>การตั้งค่า:</strong> ใช้ JSON format เช่น <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{"timeout": 30}</code></span>
            </li>
        </ul>
    </div>
</div>
@endsection
