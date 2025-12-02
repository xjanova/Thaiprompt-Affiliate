@extends('layouts.admin')

@section('title', 'NFC Writer - เขียนข้อมูลลงบัตร')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="nfcWriter()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-edit text-blue-500"></i>
                NFC Writer - เขียนข้อมูลลงบัตร
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                เลือกประเภทข้อมูลและเขียนลงบัตร NFC
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('admin.nfc-cards.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับ
            </a>
        </div>
    </div>

    {{-- Web NFC Support Check --}}
    <template x-if="!isNfcSupported">
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-6 py-4 rounded-lg mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
                <div>
                    <h3 class="font-bold">เบราว์เซอร์ไม่รองรับ Web NFC</h3>
                    <p class="text-sm mt-1">กรุณาใช้ Chrome บน Android หรือเปิดใช้งาน Web NFC flag ใน chrome://flags</p>
                </div>
            </div>
        </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Template Selection & Form --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Step 1: เลือกประเภทข้อมูล --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-sm">1</span>
                        เลือกประเภทข้อมูล
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach(config('nfc-templates.templates') as $key => $template)
                        <button type="button"
                                @click="selectTemplate('{{ $key }}')"
                                :class="{
                                    'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/30': selectedTemplate === '{{ $key }}',
                                    'hover:bg-gray-50 dark:hover:bg-gray-700': selectedTemplate !== '{{ $key }}'
                                }"
                                class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 transition-all text-center group">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-{{ $template['color'] }}-100 dark:bg-{{ $template['color'] }}-900/30 flex items-center justify-center">
                                <i class="fas {{ $template['icon'] }} text-{{ $template['color'] }}-500 text-xl"></i>
                            </div>
                            <h3 class="font-medium text-gray-900 dark:text-white text-sm">{{ $template['name'] }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $template['name_en'] }}</p>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Step 2: กรอกข้อมูล --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" x-show="selectedTemplate" x-transition>
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-sm">2</span>
                        กรอกข้อมูล
                        <span class="ml-2 text-purple-200" x-text="templates[selectedTemplate]?.name || ''"></span>
                    </h2>
                </div>
                <div class="p-6">
                    {{-- Dynamic Form Fields --}}
                    @foreach(config('nfc-templates.templates') as $key => $template)
                    <div x-show="selectedTemplate === '{{ $key }}'" class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ $template['description'] }}
                        </p>

                        @foreach($template['fields'] as $fieldKey => $field)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $field['label'] }}
                                @if($field['required'] ?? false)
                                <span class="text-red-500">*</span>
                                @endif
                            </label>

                            @if(($field['type'] ?? 'text') === 'select')
                                <select x-model="formData.{{ $fieldKey }}"
                                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">-- เลือก --</option>
                                    @foreach($field['options'] ?? [] as $optValue => $optLabel)
                                    <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif(($field['type'] ?? 'text') === 'textarea')
                                <textarea x-model="formData.{{ $fieldKey }}"
                                          rows="3"
                                          placeholder="{{ $field['placeholder'] ?? '' }}"
                                          class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            @elseif(($field['type'] ?? 'text') === 'checkbox')
                                <label class="inline-flex items-center">
                                    <input type="checkbox"
                                           x-model="formData.{{ $fieldKey }}"
                                           class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $field['label'] }}</span>
                                </label>
                            @else
                                <input type="{{ $field['type'] ?? 'text' }}"
                                       x-model="formData.{{ $fieldKey }}"
                                       placeholder="{{ $field['placeholder'] ?? '' }}"
                                       class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Step 3: อ่านและเขียน NFC --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" x-show="selectedTemplate" x-transition>
                <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-sm">3</span>
                        อ่านและเขียน NFC
                    </h2>
                </div>
                <div class="p-6">
                    {{-- NFC Status --}}
                    <div class="flex items-center justify-center mb-6">
                        <div class="text-center">
                            {{-- NFC Animation --}}
                            <div class="relative w-32 h-32 mx-auto mb-4">
                                <div class="absolute inset-0 rounded-full border-4 border-gray-200 dark:border-gray-700"></div>
                                <div x-show="isScanning"
                                     class="absolute inset-0 rounded-full border-4 border-blue-500 animate-ping"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <i class="fas fa-wifi text-4xl"
                                       :class="{
                                           'text-gray-400': !isScanning && !nfcUid,
                                           'text-blue-500 animate-pulse': isScanning,
                                           'text-green-500': nfcUid && !isScanning
                                       }"></i>
                                </div>
                            </div>

                            {{-- Status Text --}}
                            <p class="text-lg font-medium"
                               :class="{
                                   'text-gray-600 dark:text-gray-400': !isScanning && !nfcUid,
                                   'text-blue-600 dark:text-blue-400': isScanning,
                                   'text-green-600 dark:text-green-400': nfcUid && !isScanning
                               }">
                                <span x-show="!isScanning && !nfcUid">พร้อมสแกน</span>
                                <span x-show="isScanning">กำลังรอบัตร NFC...</span>
                                <span x-show="nfcUid && !isScanning">พบบัตร NFC</span>
                            </p>

                            {{-- NFC UID --}}
                            <template x-if="nfcUid">
                                <div class="mt-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg inline-block">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">UID:</span>
                                    <span class="font-mono font-bold text-gray-900 dark:text-white" x-text="nfcUid"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        {{-- Read Button --}}
                        <button type="button"
                                @click="startReading()"
                                :disabled="isScanning || isWriting"
                                class="px-6 py-3 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center justify-center gap-2">
                            <i class="fas" :class="isScanning ? 'fa-spinner fa-spin' : 'fa-broadcast-tower'"></i>
                            <span x-text="isScanning ? 'กำลังสแกน...' : 'อ่านบัตร NFC'"></span>
                        </button>

                        {{-- Write Button --}}
                        <button type="button"
                                @click="writeToNfc()"
                                :disabled="!nfcUid || isWriting || !isFormValid()"
                                class="px-6 py-3 bg-green-500 hover:bg-green-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition flex items-center justify-center gap-2">
                            <i class="fas" :class="isWriting ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                            <span x-text="isWriting ? 'กำลังเขียน...' : 'เขียนลงบัตร'"></span>
                        </button>

                        {{-- Stop Button --}}
                        <button type="button"
                                x-show="isScanning"
                                @click="stopReading()"
                                class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition flex items-center justify-center gap-2">
                            <i class="fas fa-stop"></i>
                            หยุดสแกน
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Preview & Status --}}
        <div class="space-y-6">
            {{-- Preview Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden sticky top-6">
                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-eye"></i>
                        ตัวอย่างข้อมูล
                    </h2>
                </div>
                <div class="p-6">
                    <template x-if="!selectedTemplate">
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <i class="fas fa-hand-pointer text-4xl mb-3 opacity-50"></i>
                            <p>เลือกประเภทข้อมูลเพื่อดูตัวอย่าง</p>
                        </div>
                    </template>

                    <template x-if="selectedTemplate">
                        <div class="space-y-3">
                            {{-- Template Icon --}}
                            <div class="text-center mb-4">
                                <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center">
                                    <i class="fas text-white text-2xl" :class="templates[selectedTemplate]?.icon"></i>
                                </div>
                                <h3 class="mt-2 font-bold text-gray-900 dark:text-white" x-text="templates[selectedTemplate]?.name"></h3>
                            </div>

                            {{-- NDEF Records Preview --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
                                    <i class="fas fa-list mr-1"></i>
                                    NDEF Records ที่จะเขียน:
                                </h4>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    <template x-for="(record, index) in previewRecords" :key="index">
                                        <div class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm">
                                            <span class="inline-block px-2 py-0.5 text-xs rounded mr-2"
                                                  :class="{
                                                      'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300': record.type === 'text',
                                                      'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': record.type === 'url'
                                                  }"
                                                  x-text="record.type.toUpperCase()"></span>
                                            <span class="text-gray-700 dark:text-gray-300 break-all" x-text="record.data"></span>
                                        </div>
                                    </template>
                                    <template x-if="previewRecords.length === 0">
                                        <p class="text-gray-500 dark:text-gray-400 text-sm italic">กรอกข้อมูลเพื่อดูตัวอย่าง</p>
                                    </template>
                                </div>
                            </div>

                            {{-- Estimated Size --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">ขนาดโดยประมาณ:</span>
                                    <span class="font-medium"
                                          :class="estimatedSize > 144 ? 'text-orange-500' : 'text-green-500'"
                                          x-text="estimatedSize + ' bytes'"></span>
                                </div>
                                <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-green-500 to-blue-500 transition-all"
                                         :style="'width: ' + Math.min(100, (estimatedSize / 144) * 100) + '%'"></div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    NTAG213: 144 bytes | NTAG215: 504 bytes | NTAG216: 888 bytes
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Write History --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-history"></i>
                        ประวัติการเขียน
                    </h2>
                </div>
                <div class="p-6">
                    <template x-if="writeHistory.length === 0">
                        <p class="text-center text-gray-500 dark:text-gray-400 py-4">
                            ยังไม่มีประวัติการเขียน
                        </p>
                    </template>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <template x-for="(item, index) in writeHistory" :key="index">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                     :class="item.success ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'">
                                    <i class="fas"
                                       :class="item.success ? 'fa-check text-green-500' : 'fa-times text-red-500'"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white text-sm" x-text="item.template"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="item.uid"></p>
                                </div>
                                <span class="text-xs text-gray-400" x-text="item.time"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div x-show="toast.show"
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-6 right-6 z-50">
        <div class="px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3"
             :class="{
                 'bg-green-500 text-white': toast.type === 'success',
                 'bg-red-500 text-white': toast.type === 'error',
                 'bg-blue-500 text-white': toast.type === 'info'
             }">
            <i class="fas text-xl"
               :class="{
                   'fa-check-circle': toast.type === 'success',
                   'fa-exclamation-circle': toast.type === 'error',
                   'fa-info-circle': toast.type === 'info'
               }"></i>
            <span x-text="toast.message"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
function nfcWriter() {
    return {
        // Web NFC Support
        isNfcSupported: 'NDEFReader' in window,

        // Templates Configuration
        templates: @json(config('nfc-templates.templates')),

        // Selected Template
        selectedTemplate: null,

        // Form Data
        formData: {},

        // NFC State
        isScanning: false,
        isWriting: false,
        nfcUid: null,
        nfcReader: null,
        abortController: null,

        // Preview
        previewRecords: [],
        estimatedSize: 0,

        // History
        writeHistory: [],

        // Toast
        toast: {
            show: false,
            message: '',
            type: 'info'
        },

        /**
         * เลือก Template
         */
        selectTemplate(key) {
            this.selectedTemplate = key;
            this.formData = {};
            this.nfcUid = null;
            this.updatePreview();
        },

        /**
         * ตรวจสอบ Form Valid
         */
        isFormValid() {
            if (!this.selectedTemplate) return false;

            const template = this.templates[this.selectedTemplate];
            if (!template) return false;

            // ตรวจสอบ required fields
            for (const [key, field] of Object.entries(template.fields)) {
                if (field.required && !this.formData[key]) {
                    return false;
                }
            }

            return true;
        },

        /**
         * อัพเดท Preview
         */
        updatePreview() {
            if (!this.selectedTemplate) {
                this.previewRecords = [];
                this.estimatedSize = 0;
                return;
            }

            this.previewRecords = this.buildRecords();
            this.estimatedSize = this.calculateSize();
        },

        /**
         * สร้าง NDEF Records
         */
        buildRecords() {
            const template = this.templates[this.selectedTemplate];
            if (!template) return [];

            const records = [];
            const data = this.formData;

            switch (this.selectedTemplate) {
                case 'member_card':
                    if (data.member_id) records.push({ type: 'text', data: `MEMBER:${data.member_id}` });
                    if (data.member_name) records.push({ type: 'text', data: `NAME:${data.member_name}` });
                    if (data.card_number) records.push({ type: 'text', data: `CARD:${data.card_number}` });
                    if (data.rank) records.push({ type: 'text', data: `RANK:${data.rank}` });
                    if (data.expiry_date) records.push({ type: 'text', data: `EXPIRE:${data.expiry_date}` });
                    if (data.card_number) {
                        records.push({ type: 'url', data: `${window.location.origin}/nfc/verify/${data.card_number}` });
                    }
                    break;

                case 'business_card':
                    if (data.name) records.push({ type: 'text', data: `NAME:${data.name}` });
                    if (data.phone) records.push({ type: 'text', data: `TEL:${data.phone}` });
                    if (data.email) records.push({ type: 'text', data: `EMAIL:${data.email}` });
                    if (data.position) records.push({ type: 'text', data: `POSITION:${data.position}` });
                    if (data.company) records.push({ type: 'text', data: `COMPANY:${data.company}` });
                    if (data.website) records.push({ type: 'url', data: data.website });
                    // vCard
                    let vcard = 'BEGIN:VCARD\nVERSION:3.0\n';
                    if (data.name) vcard += `FN:${data.name}\n`;
                    if (data.company) vcard += `ORG:${data.company}\n`;
                    if (data.position) vcard += `TITLE:${data.position}\n`;
                    if (data.phone) vcard += `TEL:${data.phone}\n`;
                    if (data.email) vcard += `EMAIL:${data.email}\n`;
                    if (data.website) vcard += `URL:${data.website}\n`;
                    if (data.address) vcard += `ADR:${data.address}\n`;
                    vcard += 'END:VCARD';
                    records.push({ type: 'text', data: vcard, mediaType: 'text/vcard' });
                    break;

                case 'product_info':
                    if (data.product_name) records.push({ type: 'text', data: `PRODUCT:${data.product_name}` });
                    if (data.sku) records.push({ type: 'text', data: `SKU:${data.sku}` });
                    if (data.price) records.push({ type: 'text', data: `PRICE:${data.price} THB` });
                    if (data.category) records.push({ type: 'text', data: `CATEGORY:${data.category}` });
                    if (data.description) records.push({ type: 'text', data: `DESC:${data.description}` });
                    if (data.product_url) records.push({ type: 'url', data: data.product_url });
                    break;

                case 'access_card':
                    if (data.employee_id) records.push({ type: 'text', data: `EMP:${data.employee_id}` });
                    if (data.employee_name) records.push({ type: 'text', data: `NAME:${data.employee_name}` });
                    if (data.department) records.push({ type: 'text', data: `DEPT:${data.department}` });
                    if (data.access_level) records.push({ type: 'text', data: `ACCESS:${data.access_level}` });
                    if (data.valid_from || data.valid_until) {
                        records.push({ type: 'text', data: `VALID:${data.valid_from || 'N/A'} - ${data.valid_until || 'N/A'}` });
                    }
                    break;

                case 'wifi_share':
                    if (data.ssid && data.password) {
                        const hidden = data.hidden ? 'true' : 'false';
                        const encryption = data.encryption || 'WPA';
                        records.push({
                            type: 'text',
                            data: `WIFI:T:${encryption};S:${data.ssid};P:${data.password};H:${hidden};;`,
                            mediaType: 'application/vnd.wfa.wsc'
                        });
                    }
                    break;

                case 'url_shortcut':
                    if (data.title) records.push({ type: 'text', data: data.title });
                    if (data.url) records.push({ type: 'url', data: data.url });
                    break;

                case 'social_links':
                    if (data.name) records.push({ type: 'text', data: `NAME:${data.name}` });
                    if (data.facebook) records.push({ type: 'url', data: data.facebook });
                    if (data.line) records.push({ type: 'text', data: `LINE:${data.line}` });
                    if (data.instagram) records.push({ type: 'url', data: data.instagram });
                    if (data.twitter) records.push({ type: 'url', data: data.twitter });
                    if (data.tiktok) records.push({ type: 'url', data: data.tiktok });
                    break;
            }

            return records;
        },

        /**
         * คำนวณขนาด
         */
        calculateSize() {
            let size = 0;
            for (const record of this.previewRecords) {
                // Approximate NDEF record size
                size += 3; // Header bytes
                size += new TextEncoder().encode(record.data).length;
            }
            return size;
        },

        /**
         * เริ่มอ่าน NFC
         */
        async startReading() {
            if (!this.isNfcSupported) {
                this.showToast('เบราว์เซอร์ไม่รองรับ Web NFC', 'error');
                return;
            }

            try {
                this.abortController = new AbortController();
                this.nfcReader = new NDEFReader();

                await this.nfcReader.scan({ signal: this.abortController.signal });
                this.isScanning = true;
                this.showToast('กำลังรอบัตร NFC...', 'info');

                this.nfcReader.addEventListener('reading', ({ serialNumber }) => {
                    this.nfcUid = serialNumber.replace(/:/g, '').toUpperCase();
                    this.isScanning = false;
                    this.showToast(`พบบัตร NFC: ${this.nfcUid}`, 'success');
                });

                this.nfcReader.addEventListener('readingerror', () => {
                    this.showToast('ไม่สามารถอ่านบัตร NFC ได้', 'error');
                });

            } catch (error) {
                this.isScanning = false;
                console.error('NFC Error:', error);
                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
        },

        /**
         * หยุดอ่าน NFC
         */
        stopReading() {
            if (this.abortController) {
                this.abortController.abort();
                this.abortController = null;
            }
            this.isScanning = false;
            this.showToast('หยุดสแกนแล้ว', 'info');
        },

        /**
         * เขียนลง NFC
         */
        async writeToNfc() {
            if (!this.isNfcSupported || !this.nfcUid) {
                this.showToast('กรุณาอ่านบัตร NFC ก่อน', 'error');
                return;
            }

            if (!this.isFormValid()) {
                this.showToast('กรุณากรอกข้อมูลที่จำเป็นให้ครบ', 'error');
                return;
            }

            this.isWriting = true;

            try {
                const writer = new NDEFReader();
                const records = [];

                // สร้าง NDEF records
                for (const record of this.previewRecords) {
                    if (record.type === 'url') {
                        records.push({
                            recordType: 'url',
                            data: record.data
                        });
                    } else if (record.type === 'text') {
                        if (record.mediaType) {
                            records.push({
                                recordType: 'mime',
                                mediaType: record.mediaType,
                                data: new TextEncoder().encode(record.data)
                            });
                        } else {
                            records.push({
                                recordType: 'text',
                                data: record.data
                            });
                        }
                    }
                }

                // เขียนลงบัตร
                await writer.write({ records });

                // บันทึกประวัติ
                this.writeHistory.unshift({
                    template: this.templates[this.selectedTemplate]?.name || this.selectedTemplate,
                    uid: this.nfcUid,
                    success: true,
                    time: new Date().toLocaleTimeString('th-TH')
                });

                // บันทึกลง Database
                await this.saveToDatabase();

                this.showToast('เขียนข้อมูลลงบัตร NFC สำเร็จ!', 'success');

            } catch (error) {
                console.error('Write Error:', error);

                this.writeHistory.unshift({
                    template: this.templates[this.selectedTemplate]?.name || this.selectedTemplate,
                    uid: this.nfcUid,
                    success: false,
                    time: new Date().toLocaleTimeString('th-TH')
                });

                this.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.isWriting = false;
            }
        },

        /**
         * บันทึกลง Database
         */
        async saveToDatabase() {
            try {
                const response = await fetch('{{ route("admin.nfc-cards.save-nfc-write-log") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        nfc_uid: this.nfcUid,
                        template: this.selectedTemplate,
                        data: this.formData,
                        records: this.previewRecords
                    })
                });

                const result = await response.json();
                if (!result.success) {
                    console.error('Save error:', result.message);
                }
            } catch (error) {
                console.error('Save error:', error);
            }
        },

        /**
         * แสดง Toast
         */
        showToast(message, type = 'info') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;

            setTimeout(() => {
                this.toast.show = false;
            }, 3000);
        },

        /**
         * Watch formData changes
         */
        init() {
            this.$watch('formData', () => {
                this.updatePreview();
            });
        }
    };
}
</script>
@endpush
@endsection
