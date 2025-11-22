@extends('layouts.admin-v3')

@section('title', $mode === 'create' ? 'สร้าง Template ใหม่' : 'แก้ไข Template')

@section('head')
{{-- CodeMirror CSS --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<style>
    .CodeMirror {
        height: 500px;
        border-radius: 0.75rem;
        font-size: 14px;
    }
</style>
@endsection

@section('content')
{{--
    Template Form - V3 Theme
    ใช้ Tailwind CSS + Alpine.js + CodeMirror
    Dark Mode Support + Responsive
--}}
<div class="min-h-screen pb-12" x-data="templateForm()" x-init="init()">
    {{-- Page Header --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-[#00B900] via-[#06C755] to-[#00E600] p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <i class="fas fa-{{ $mode === 'create' ? 'plus-circle' : 'edit' }} text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
                            {{ $mode === 'create' ? 'สร้าง Template ใหม่' : 'แก้ไข Template' }}
                        </h1>
                        <p class="text-white/90 text-sm md:text-base mt-1">
                            {{ $mode === 'create' ? 'สร้าง Flex Message template สำหรับ LINE signup flow' : 'แก้ไข Flex Message template' }}
                        </p>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.line-membership-signup.templates') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 rounded-xl text-white font-medium hover:bg-white/30 transition-all duration-300 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Form --}}
    <form
        action="{{ $mode === 'create' ? route('admin.line-membership-signup.templates.store') : route('admin.line-membership-signup.templates.update', $template) }}"
        method="POST"
        @submit.prevent="submitForm"
        class="space-y-6"
    >
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Form Fields --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Template Key --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-key text-blue-500"></i>
                        ข้อมูลพื้นฐาน
                    </h3>

                    <div class="space-y-4">
                        {{-- Template Key --}}
                        <div>
                            <label for="template_key" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Template Key <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="template_key"
                                name="template_key"
                                value="{{ old('template_key', $template->template_key ?? '') }}"
                                {{ $mode === 'edit' ? 'readonly' : '' }}
                                required
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white {{ $mode === 'edit' ? 'cursor-not-allowed opacity-60' : '' }}"
                                placeholder="welcome_hero"
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                ตัวระบุเฉพาะของ template (a-z, 0-9, _)
                            </p>
                        </div>

                        {{-- Template Name --}}
                        <div>
                            <label for="template_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อ Template <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="template_name"
                                name="template_name"
                                value="{{ old('template_name', $template->template_name ?? '') }}"
                                required
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white"
                                placeholder="Welcome Message"
                            >
                        </div>

                        {{-- Category --}}
                        <div>
                            <label for="category" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                หมวดหมู่
                            </label>
                            <select
                                id="category"
                                name="category"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white"
                            >
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <option value="welcome" {{ old('category', $template->category ?? '') === 'welcome' ? 'selected' : '' }}>ต้อนรับ (Welcome)</option>
                                <option value="earning" {{ old('category', $template->category ?? '') === 'earning' ? 'selected' : '' }}>รายได้ (Earning)</option>
                                <option value="success_story" {{ old('category', $template->category ?? '') === 'success_story' ? 'selected' : '' }}>เรื่องราวความสำเร็จ</option>
                                <option value="training" {{ old('category', $template->category ?? '') === 'training' ? 'selected' : '' }}>อบรม (Training)</option>
                                <option value="guide" {{ old('category', $template->category ?? '') === 'guide' ? 'selected' : '' }}>คู่มือ (Guide)</option>
                                <option value="other" {{ old('category', $template->category ?? '') === 'other' ? 'selected' : '' }}>อื่นๆ</option>
                            </select>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                คำอธิบาย
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white resize-none"
                                placeholder="อธิบายการใช้งาน template นี้..."
                            >{{ old('description', $template->description ?? '') }}</textarea>
                        </div>

                        {{-- Variables --}}
                        <div>
                            <label for="variables" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ตัวแปร (Variables)
                            </label>
                            <input
                                type="text"
                                id="variables"
                                name="variables"
                                value="{{ old('variables', isset($template) && $template->variables ? implode(', ', $template->variables) : '') }}"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white font-mono text-sm"
                                placeholder="user_name, member_code, ..."
                            >
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                ใส่ตัวแปรคั่นด้วย comma (,) เช่น: user_name, email, phone
                            </p>
                        </div>

                        {{-- Is Active --}}
                        <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <input
                                type="checkbox"
                                id="is_active"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}
                                class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                            <label for="is_active" class="text-sm font-medium text-gray-900 dark:text-white">
                                เปิดใช้งาน Template
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Help Card --}}
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl p-6 border border-purple-200 dark:border-purple-700">
                    <h4 class="text-sm font-bold text-purple-900 dark:text-purple-300 mb-3 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i>
                        คำแนะนำ
                    </h4>
                    <ul class="space-y-2 text-xs text-purple-800 dark:text-purple-200">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-purple-500 mt-0.5"></i>
                            <span>ใช้ CodeMirror Editor ด้านขวาสำหรับแก้ไข JSON</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-purple-500 mt-0.5"></i>
                            <span>ระบุตัวแปรด้วย <code class="px-1 py-0.5 bg-purple-200 dark:bg-purple-800 rounded">@{{variable}}</code> ใน JSON</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-purple-500 mt-0.5"></i>
                            <span>ทดสอบ JSON ใน <a href="https://developers.line.biz/flex-simulator/" target="_blank" class="text-purple-600 dark:text-purple-400 underline">LINE Flex Simulator</a></span>
                        </li>
                    </ul>
                </div>

                {{-- LINE Preview Mockup --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#06C755]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        ตัวอย่างใน LINE
                    </h4>

                    <div class="bg-gradient-to-b from-gray-100 to-gray-200 dark:from-slate-900 dark:to-slate-800 rounded-2xl p-4">
                        {{-- LINE Chat Mockup Header --}}
                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-300 dark:border-slate-600">
                            <div class="w-10 h-10 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-full flex items-center justify-center text-white font-bold">
                                B
                            </div>
                            <div>
                                <span class="font-bold text-gray-900 dark:text-white block">Bot Name</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Active now</span>
                            </div>
                        </div>

                        {{-- Message Bubble Preview --}}
                        <div class="flex justify-start mb-2">
                            <div class="max-w-sm">
                                <div class="bg-white dark:bg-slate-700 rounded-2xl rounded-tl-sm p-4 shadow-lg">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2 mb-2 text-[#06C755]">
                                            <i class="fas fa-info-circle"></i>
                                            <span class="font-bold">Flex Message Preview</span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            แก้ไข JSON ด้านขวาเพื่อดูตัวอย่าง<br>
                                            Flex Message จะแสดงที่นี่
                                        </p>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-2">
                                    Just now
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-gradient-to-r from-[#00B900]/10 to-[#00E600]/10 dark:from-[#00B900]/20 dark:to-[#00E600]/20 rounded-xl border border-[#06C755]/30">
                        <p class="text-xs text-gray-600 dark:text-gray-400 flex items-start gap-2">
                            <i class="fas fa-lightbulb text-[#06C755] mt-0.5"></i>
                            <span>ใช้ <a href="https://developers.line.biz/flex-simulator/" target="_blank" class="text-[#06C755] hover:underline font-semibold">LINE Flex Simulator</a> เพื่อดูตัวอย่างแบบเต็มรูปแบบ</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Right Column: JSON Editor --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-code text-green-500"></i>
                            Flex Message JSON
                            <span class="text-red-500">*</span>
                        </h3>

                        <div class="flex items-center gap-2">
                            {{-- Beautify Button --}}
                            <button
                                type="button"
                                @click="beautifyJSON()"
                                class="px-3 py-2 bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white rounded-lg font-medium transition-all duration-300 text-xs flex items-center gap-2"
                            >
                                <i class="fas fa-magic"></i>
                                <span>จัดรูปแบบ</span>
                            </button>

                            {{-- Validate Button --}}
                            <button
                                type="button"
                                @click="validateJSON()"
                                class="px-3 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white rounded-lg font-medium transition-all duration-300 text-xs flex items-center gap-2"
                            >
                                <i class="fas fa-check-circle"></i>
                                <span>ตรวจสอบ</span>
                            </button>
                        </div>
                    </div>

                    {{-- JSON Editor --}}
                    <div class="relative">
                        <textarea
                            id="flex_message_json"
                            name="flex_message_json"
                            required
                            class="hidden"
                        >{{ old('flex_message_json', isset($template) && $template->flex_message_json ? json_encode($template->flex_message_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                        <div id="json-editor-container" class="rounded-xl overflow-hidden border-2 border-gray-200 dark:border-gray-700"></div>
                    </div>

                    {{-- JSON Validation Message --}}
                    <div
                        x-show="validationMessage"
                        x-transition
                        :class="{
                            'bg-green-50 dark:bg-green-900/20 border-green-500 text-green-800 dark:text-green-300': validationStatus === 'success',
                            'bg-red-50 dark:bg-red-900/20 border-red-500 text-red-800 dark:text-red-300': validationStatus === 'error'
                        }"
                        class="mt-4 p-4 border-l-4 rounded-lg"
                    >
                        <div class="flex items-start gap-3">
                            <i :class="validationStatus === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-triangle text-red-500'" class="fas mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-sm" x-text="validationStatus === 'success' ? 'JSON ถูกต้อง!' : 'JSON ไม่ถูกต้อง'"></p>
                                <p class="text-xs mt-1" x-text="validationMessage"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i>
                    <span>กรุณาตรวจสอบข้อมูลก่อนบันทึก</span>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        href="{{ route('admin.line-membership-signup.templates') }}"
                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition-all duration-300"
                    >
                        ยกเลิก
                    </a>

                    <button
                        type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 text-white rounded-xl font-bold transition-all duration-300 hover:shadow-xl transform hover:scale-105 flex items-center gap-2"
                    >
                        <i class="fas fa-save"></i>
                        <span>{{ $mode === 'create' ? 'สร้าง Template' : 'บันทึกการแก้ไข' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
{{-- CodeMirror JS --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>

<script>
/**
 * Template Form Manager - Alpine.js Component
 *
 * จัดการฟอร์มสร้าง/แก้ไข template พร้อม CodeMirror JSON Editor
 */
function templateForm() {
    return {
        editor: null,
        validationStatus: '',
        validationMessage: '',

        /**
         * เริ่มต้น component และ CodeMirror editor
         */
        init() {
            this.initCodeMirror();
        },

        /**
         * เริ่มต้น CodeMirror JSON Editor
         */
        initCodeMirror() {
            const textarea = document.getElementById('flex_message_json');
            const container = document.getElementById('json-editor-container');

            // ตรวจสอบว่ามี dark mode หรือไม่
            const isDarkMode = document.documentElement.classList.contains('dark');

            this.editor = CodeMirror(container, {
                value: textarea.value || '{\n  "type": "bubble",\n  "body": {\n    "type": "box",\n    "layout": "vertical",\n    "contents": []\n  }\n}',
                mode: 'application/json',
                theme: isDarkMode ? 'dracula' : 'monokai',
                lineNumbers: true,
                lineWrapping: true,
                indentUnit: 2,
                tabSize: 2,
                autoCloseBrackets: true,
                matchBrackets: true,
                foldGutter: true,
                gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
            });

            // Sync กับ textarea เมื่อมีการเปลี่ยนแปลง
            this.editor.on('change', (cm) => {
                textarea.value = cm.getValue();
            });
        },

        /**
         * จัดรูปแบบ JSON
         */
        beautifyJSON() {
            try {
                const json = JSON.parse(this.editor.getValue());
                const beautified = JSON.stringify(json, null, 2);
                this.editor.setValue(beautified);

                this.validationStatus = 'success';
                this.validationMessage = 'จัดรูปแบบ JSON สำเร็จ!';
                this.clearValidationMessage();
            } catch (e) {
                this.validationStatus = 'error';
                this.validationMessage = 'JSON ไม่ถูกต้อง: ' + e.message;
            }
        },

        /**
         * ตรวจสอบความถูกต้องของ JSON
         */
        validateJSON() {
            try {
                const json = JSON.parse(this.editor.getValue());

                // ตรวจสอบ required fields ของ LINE Flex Message
                if (!json.type) {
                    throw new Error('ต้องมีฟิลด์ "type"');
                }

                if (!['bubble', 'carousel'].includes(json.type)) {
                    throw new Error('type ต้องเป็น "bubble" หรือ "carousel"');
                }

                if (json.type === 'bubble' && !json.body && !json.hero) {
                    throw new Error('Bubble message ต้องมี "body" หรือ "hero"');
                }

                this.validationStatus = 'success';
                this.validationMessage = 'JSON ถูกต้องและพร้อมใช้งาน!';
                this.clearValidationMessage();
            } catch (e) {
                this.validationStatus = 'error';
                this.validationMessage = e.message;
            }
        },

        /**
         * ล้างข้อความ validation หลังจาก 5 วินาที
         */
        clearValidationMessage() {
            setTimeout(() => {
                this.validationMessage = '';
            }, 5000);
        },

        /**
         * Submit form
         */
        submitForm(event) {
            // ตรวจสอบ JSON ก่อน submit
            try {
                JSON.parse(this.editor.getValue());
                event.target.submit();
            } catch (e) {
                this.validationStatus = 'error';
                this.validationMessage = 'กรุณาแก้ไข JSON ให้ถูกต้องก่อนบันทึก: ' + e.message;
            }
        }
    };
}
</script>

<style>
[x-cloak] {
    display: none !important;
}

/* Dark mode สำหรับ CodeMirror */
.dark .CodeMirror {
    background-color: #1e1e1e;
    color: #d4d4d4;
}
</style>
@endpush
@endsection
