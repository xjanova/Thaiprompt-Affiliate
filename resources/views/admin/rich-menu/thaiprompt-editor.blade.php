{{--
    Rich Menu Editor — Thaiprompt OA หลัก
    จัดการ Rich Menu แบบ Config mode + Custom mode
    Pattern เดียวกับ Fortune Rich Menu Editor
--}}

@extends('layouts.admin')

@section('title', 'Rich Menu Editor - ไทยพร้อม OA')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="thaipromptRichMenuEditor()" x-init="loadConfig()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                📱 Rich Menu Editor — ไทยพร้อม OA
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">แก้ไขและ Deploy Rich Menu สำหรับ LINE Thaiprompt OA หลัก</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="{{ route('admin.rich-menu.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow transition text-sm">
                <span>📋</span> Rich Menu ทั้งหมด
            </a>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
        <button @click="activeTab = 'config'"
                :class="activeTab === 'config'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400'"
                class="px-6 py-3 text-sm font-semibold border-b-2 transition">
            ✏️ Config Editor
        </button>
        <button @click="activeTab = 'custom'"
                :class="activeTab === 'custom'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400'"
                class="px-6 py-3 text-sm font-semibold border-b-2 transition">
            🖼️ Custom Upload
        </button>
    </div>

    {{-- ========== Tab 1: Config Editor ========== --}}
    <div x-show="activeTab === 'config'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Editor Panel (ซ้าย) --}}
            <div class="space-y-6">
                {{-- Theme Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🎨 ตั้งค่า Theme</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีพื้นหลัง (บน)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="config.theme.bg_gradient_start" class="w-10 h-10 rounded cursor-pointer">
                                <input type="text" x-model="config.theme.bg_gradient_start" class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีพื้นหลัง (ล่าง)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="config.theme.bg_gradient_end" class="w-10 h-10 rounded cursor-pointer">
                                <input type="text" x-model="config.theme.bg_gradient_end" class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Button Settings --}}
                <template x-for="(btn, i) in config.buttons" :key="i">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            <span x-text="'ปุ่ม ' + (i + 1) + ': ' + btn.label"></span>
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความ</label>
                                <input type="text" x-model="btn.label" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                                <input type="text" x-model="btn.subtitle" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท Action</label>
                                <select x-model="btn.action_type" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                    <option value="uri">URI (เปิดลิ้งค์)</option>
                                    <option value="message">Message (ส่งข้อความ)</option>
                                    <option value="postback">Postback</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action Data</label>
                                <input type="text" x-model="btn.action_data" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-mono">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีข้อความ</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="btn.text_color" class="w-8 h-8 rounded cursor-pointer">
                                    <input type="text" x-model="btn.text_color" class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สีคำอธิบาย</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="btn.subtitle_color" class="w-8 h-8 rounded cursor-pointer">
                                    <input type="text" x-model="btn.subtitle_color" class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs font-mono">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Preview Panel (ขวา) --}}
            <div class="lg:sticky lg:top-8 self-start">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">👁️ Preview</h3>

                    {{-- Preview Image --}}
                    <div x-show="previewUrl" class="mb-4">
                        <img :src="previewUrl" alt="Rich Menu Preview" class="w-full rounded-lg shadow">
                    </div>

                    {{-- Preview Grid (CSS) --}}
                    <div x-show="!previewUrl" class="rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600"
                         :style="'background: linear-gradient(to bottom, ' + config.theme.bg_gradient_start + ', ' + config.theme.bg_gradient_end + ')'">
                        <div class="grid grid-cols-3 aspect-[2500/1686]">
                            <template x-for="(btn, i) in config.buttons" :key="'preview-' + i">
                                <div class="flex flex-col items-center justify-center p-2 border border-gray-600/30"
                                     :style="'border-color: ' + config.theme.grid_line_color">
                                    <span class="font-bold text-xs sm:text-sm" :style="'color: ' + btn.text_color" x-text="btn.label"></span>
                                    <span class="text-xs mt-1 opacity-80" :style="'color: ' + btn.subtitle_color" x-text="btn.subtitle"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Deploy Button --}}
                    <div class="mt-6">
                        <button @click="deploy()"
                                :disabled="deploying"
                                class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!deploying">🚀 Deploy ไป LINE API</span>
                            <span x-show="deploying" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                กำลัง Deploy...
                            </span>
                        </button>
                    </div>

                    {{-- Status Message --}}
                    <div x-show="statusMessage" x-transition class="mt-4 p-4 rounded-lg text-sm"
                         :class="statusSuccess ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'"
                         x-text="statusMessage">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== Tab 2: Custom Upload ========== --}}
    <div x-show="activeTab === 'custom'" x-cloak>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700 max-w-2xl mx-auto">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🖼️ อัปโหลดภาพ Rich Menu</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                อัปโหลดภาพ PNG ขนาด 2500x1686 พิกเซล ระบบจะใช้ areas ตำแหน่ง 6 ปุ่ม default
            </p>

            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center">
                <input type="file" accept="image/png,image/jpeg" @change="uploadCustomImage($event)"
                       class="hidden" id="custom-image-input">
                <label for="custom-image-input" class="cursor-pointer">
                    <div class="text-4xl mb-2">📤</div>
                    <p class="text-gray-600 dark:text-gray-400">คลิกเพื่อเลือกภาพ (PNG/JPEG, สูงสุด 2MB)</p>
                </label>
            </div>

            <div x-show="customImageUrl" class="mt-6">
                <img :src="customImageUrl" alt="Custom Rich Menu" class="w-full rounded-lg shadow">
                <button @click="deployCustom()"
                        :disabled="deploying"
                        class="w-full mt-4 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg transition disabled:opacity-50">
                    <span x-show="!deploying">🚀 Deploy ภาพนี้ไป LINE API</span>
                    <span x-show="deploying">กำลัง Deploy...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function thaipromptRichMenuEditor() {
    return {
        activeTab: 'config',
        config: @json($defaultConfig),
        previewUrl: null,
        deploying: false,
        statusMessage: '',
        statusSuccess: false,
        customImageUrl: null,
        customImagePath: null,

        async loadConfig() {
            try {
                const res = await fetch('{{ route("admin.rich-menu.thaiprompt.config") }}');
                const data = await res.json();
                if (data.success && data.config) {
                    this.config = data.config;
                    if (data.image_url) this.previewUrl = data.image_url;
                }
            } catch (e) {
                console.warn('โหลด config ไม่สำเร็จ, ใช้ default');
            }
        },

        async deploy() {
            this.deploying = true;
            this.statusMessage = '';

            try {
                const res = await fetch('{{ route("admin.rich-menu.thaiprompt.deploy") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        editor_mode: 'config',
                        config: this.config,
                    }),
                });

                const data = await res.json();
                this.statusSuccess = data.success;
                this.statusMessage = data.success ? data.message : (data.error || 'เกิดข้อผิดพลาด');
                if (data.image_url) this.previewUrl = data.image_url;
            } catch (e) {
                this.statusSuccess = false;
                this.statusMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            }

            this.deploying = false;
        },

        async uploadCustomImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            try {
                const res = await fetch('{{ route("admin.rich-menu.thaiprompt.upload-image") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: formData,
                });

                const data = await res.json();
                if (data.success) {
                    this.customImageUrl = data.url;
                    this.customImagePath = data.path;
                }
            } catch (e) {
                this.statusMessage = 'อัปโหลดภาพไม่สำเร็จ: ' + e.message;
                this.statusSuccess = false;
            }
        },

        async deployCustom() {
            if (!this.customImagePath) return;
            this.deploying = true;
            this.statusMessage = '';

            try {
                const res = await fetch('{{ route("admin.rich-menu.thaiprompt.deploy") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        editor_mode: 'custom',
                        custom_image_path: this.customImagePath,
                    }),
                });

                const data = await res.json();
                this.statusSuccess = data.success;
                this.statusMessage = data.success ? data.message : (data.error || 'เกิดข้อผิดพลาด');
            } catch (e) {
                this.statusSuccess = false;
                this.statusMessage = 'เกิดข้อผิดพลาด: ' + e.message;
            }

            this.deploying = false;
        },
    };
}
</script>
@endpush
@endsection
