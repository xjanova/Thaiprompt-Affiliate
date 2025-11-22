@extends('layouts.admin-v3')

@section('title', 'สร้าง LINE Rich Menu')

@section('content')
<div class="container-fluid px-4 py-6" x-data="richMenuCreator()" x-init="init()">

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.line-bot.rich-menu.index') }}"
               class="text-gray-600 dark:text-gray-400 hover:text-[#06C755] transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">สร้าง LINE Rich Menu</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">ออกแบบเมนูอินเทอร์แอกทีฟสำหรับ LINE Chat</p>
            </div>
        </div>
    </div>

    {{-- Error Alert --}}
    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 animate-slide-up">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mt-0.5"></i>
                <div class="flex-1">
                    <h4 class="font-semibold text-red-800 dark:text-red-300 mb-1">พบข้อผิดพลาด</h4>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Progress Steps --}}
    <div class="mb-8">
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 p-6">
            <div class="flex items-center justify-between">
                <template x-for="(step, index) in steps" :key="index">
                    <div class="flex-1 flex items-center">
                        <div class="flex items-center gap-3 w-full">
                            {{-- Step Circle --}}
                            <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg transition-all"
                                 :class="currentStep > index ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white' :
                                         currentStep === index ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white ring-4 ring-[#06C755]/30' :
                                         'bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-gray-400'">
                                <i :class="step.icon" x-show="currentStep !== index || currentStep > index"></i>
                                <i class="fas fa-spinner fa-spin" x-show="currentStep === index && currentStep <= index"></i>
                            </div>
                            {{-- Step Label --}}
                            <div class="flex-1">
                                <p class="font-semibold text-sm"
                                   :class="currentStep >= index ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                                   x-text="step.label"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="step.desc"></p>
                            </div>
                        </div>
                        {{-- Connector Line --}}
                        <div x-show="index < steps.length - 1"
                             class="flex-shrink-0 h-0.5 w-12 mx-2 transition-colors"
                             :class="currentStep > index ? 'bg-gradient-to-r from-[#00B900] to-[#00E600]' : 'bg-gray-200 dark:bg-slate-700'"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.line-bot.rich-menu.store') }}" enctype="multipart/form-data" x-ref="form">
        @csrf

        {{-- Step 1: Template Selection --}}
        <div x-show="currentStep === 0" x-transition class="space-y-6">
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg border border-white/20 dark:border-slate-700/50 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-layer-group text-[#06C755]"></i>
                    เลือก Template
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- 2x2 Template --}}
                    <div @click="selectTemplate('2x2')"
                         :class="selectedTemplate === '2x2' ? 'ring-4 ring-[#06C755] scale-105' : ''"
                         class="glass-fusion cursor-pointer rounded-xl p-4 hover:scale-105 transition-all border border-white/20 dark:border-slate-700/50">
                        <div class="aspect-square bg-gray-900 rounded-lg mb-3 overflow-hidden">
                            <div class="grid grid-cols-2 grid-rows-2 h-full gap-1 p-1">
                                <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] rounded flex items-center justify-center text-white font-bold">1</div>
                                <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] rounded flex items-center justify-center text-white font-bold">2</div>
                                <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] rounded flex items-center justify-center text-white font-bold">3</div>
                                <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] rounded flex items-center justify-center text-white font-bold">4</div>
                            </div>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-1">Grid 2x2</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">4 ปุ่มแบ่งเท่าๆ กัน</p>
                    </div>

                    {{-- 3x3 Template --}}
                    <div @click="selectTemplate('3x3')"
                         :class="selectedTemplate === '3x3' ? 'ring-4 ring-[#06C755] scale-105' : ''"
                         class="glass-fusion cursor-pointer rounded-xl p-4 hover:scale-105 transition-all border border-white/20 dark:border-slate-700/50">
                        <div class="aspect-square bg-gray-900 rounded-lg mb-3 overflow-hidden">
                            <div class="grid grid-cols-3 grid-rows-3 h-full gap-1 p-1">
                                <template x-for="i in 9" :key="i">
                                    <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded flex items-center justify-center text-white text-xs font-bold" x-text="i"></div>
                                </template>
                            </div>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-1">Grid 3x3</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">9 ปุ่มแบบตาราง</p>
                    </div>

                    {{-- Custom Template --}}
                    <div @click="selectTemplate('custom')"
                         :class="selectedTemplate === 'custom' ? 'ring-4 ring-[#06C755] scale-105' : ''"
                         class="glass-fusion cursor-pointer rounded-xl p-4 hover:scale-105 transition-all border border-white/20 dark:border-slate-700/50">
                        <div class="aspect-square bg-gray-900 rounded-lg mb-3 overflow-hidden flex items-center justify-center">
                            <i class="fas fa-pencil-ruler text-4xl text-gray-500"></i>
                        </div>
                        <h4 class="font-bold text-gray-900 dark:text-white mb-1">Custom Layout</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">กำหนดเองตามต้องการ</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Menu Design --}}
        <div x-show="currentStep === 1" x-transition class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Design Section --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-[#06C755]"></i>
                        ข้อมูลพื้นฐาน
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อเมนู *
                            </label>
                            <input type="text" name="name" x-model="formData.name" required
                                   class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความบน Chat Bar * (สูงสุด 14 ตัวอักษร)
                            </label>
                            <input type="text" name="chat_bar_text" x-model="formData.chatBarText" required maxlength="14"
                                   class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] text-gray-900 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span x-text="formData.chatBarText.length"></span>/14 ตัวอักษร
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ขนาดเมนู
                            </label>
                            <select name="size" x-model="formData.size"
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] text-gray-900 dark:text-white">
                                <option value="full">Full (2500x1686px)</option>
                                <option value="half">Half (2500x843px)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                อัปโหลดภาพพื้นหลัง *
                            </label>
                            <input type="file" name="menu_image" accept="image/*" required
                                   @change="handleImageUpload($event)"
                                   class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] text-gray-900 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                PNG, JPG (แนะนำ 2500x1686px หรือ 2500x843px)
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: LINE Preview --}}
            <div class="lg:col-span-1">
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 sticky top-6 border border-white/20 dark:border-slate-700/50">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#06C755]" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        ตัวอย่างใน LINE
                    </h3>

                    {{-- LINE Chat Mockup --}}
                    <div class="bg-gradient-to-b from-gray-100 to-gray-200 dark:from-slate-900 dark:to-slate-800 rounded-2xl p-4">
                        {{-- Chat Header --}}
                        <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-300 dark:border-slate-600">
                            <div class="w-10 h-10 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-full flex items-center justify-center text-white font-bold">
                                B
                            </div>
                            <span class="font-bold text-gray-900 dark:text-white" x-text="formData.name || 'My Bot'"></span>
                        </div>

                        {{-- Rich Menu Preview --}}
                        <div class="relative aspect-[2500/1686] bg-gray-900 rounded-xl overflow-hidden">
                            <img x-show="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                            <div x-show="!imagePreview" class="flex items-center justify-center h-full text-white">
                                <div class="text-center">
                                    <i class="fas fa-image text-4xl mb-2 opacity-50"></i>
                                    <p class="text-sm opacity-75">อัปโหลดภาพพื้นหลัง</p>
                                </div>
                            </div>
                        </div>

                        {{-- Chat Bar --}}
                        <div class="mt-4 bg-white dark:bg-slate-700 rounded-lg px-4 py-2 flex items-center justify-center">
                            <span class="text-sm text-gray-600 dark:text-gray-300" x-text="formData.chatBarText || 'Menu'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 3: Menu Structure (JSON) --}}
        <div x-show="currentStep === 2" x-transition>
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-code text-[#06C755]"></i>
                    กำหนด Menu Structure (JSON)
                </h3>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    กำหนดพื้นที่คลิกได้และ action ของแต่ละพื้นที่ ดูตัวอย่างได้ที่
                    <a href="https://developers.line.biz/en/docs/messaging-api/using-rich-menus/" target="_blank"
                       class="text-[#06C755] hover:underline font-semibold">
                        LINE Rich Menu Documentation
                    </a>
                </p>

                <textarea name="menu_data" rows="20" required
                          x-model="formData.menuData"
                          class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] font-mono text-sm text-gray-900 dark:text-white"
                          placeholder='{"areas": [{"bounds": {"x": 0, "y": 0, "width": 1250, "height": 843}, "action": {"type": "uri", "uri": "https://example.com"}}]}'></textarea>

                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-lightbulb"></i>
                        ตัวอย่าง Template 2x2
                    </h4>
                    <pre class="text-xs text-blue-800 dark:text-blue-200 overflow-x-auto"><code>{
  "areas": [
    {"bounds": {"x": 0, "y": 0, "width": 1250, "height": 843}, "action": {"type": "uri", "uri": "https://example.com/1"}},
    {"bounds": {"x": 1250, "y": 0, "width": 1250, "height": 843}, "action": {"type": "message", "text": "สวัสดี"}},
    {"bounds": {"x": 0, "y": 843, "width": 1250, "height": 843}, "action": {"type": "uri", "uri": "https://example.com/2"}},
    {"bounds": {"x": 1250, "y": 843, "width": 1250, "height": 843}, "action": {"type": "postback", "data": "action=buy"}}
  ]
}</code></pre>
                </div>
            </div>
        </div>

        {{-- Navigation Buttons --}}
        <div class="mt-8 glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
            <div class="flex items-center justify-between">
                {{-- Back Button --}}
                <button type="button" @click="previousStep()"
                        x-show="currentStep > 0"
                        class="px-6 py-3 bg-white dark:bg-slate-700 border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-600 transition-all font-semibold">
                    <i class="fas fa-arrow-left mr-2"></i>ย้อนกลับ
                </button>

                <a href="{{ route('admin.line-bot.rich-menu.index') }}"
                   x-show="currentStep === 0"
                   class="px-6 py-3 bg-white dark:bg-slate-700 border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-600 transition-all font-semibold">
                    <i class="fas fa-times mr-2"></i>ยกเลิก
                </a>

                {{-- Next/Submit Button --}}
                <button type="button" @click="nextStep()"
                        x-show="currentStep < steps.length - 1"
                        class="px-8 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition-all font-bold transform hover:scale-105">
                    ถัดไป<i class="fas fa-arrow-right ml-2"></i>
                </button>

                <button type="submit"
                        x-show="currentStep === steps.length - 1"
                        class="px-8 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition-all font-bold transform hover:scale-105">
                    <i class="fas fa-save mr-2"></i>บันทึก Rich Menu
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
/**
 * Rich Menu Creator Component - สร้าง LINE Rich Menu
 *
 * @returns {object} Alpine component
 */
function richMenuCreator() {
    return {
        currentStep: 0,
        selectedTemplate: null,
        imagePreview: null,

        steps: [
            { icon: 'fas fa-layer-group', label: 'เลือก Template', desc: 'เลือกรูปแบบเมนู' },
            { icon: 'fas fa-paint-brush', label: 'ออกแบบเมนู', desc: 'อัปโหลดภาพและกำหนดข้อมูล' },
            { icon: 'fas fa-cog', label: 'กำหนด Structure', desc: 'ตั้งค่าพื้นที่คลิกและ action' }
        ],

        formData: {
            name: '',
            chatBarText: 'Menu',
            size: 'full',
            menuData: ''
        },

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Rich Menu Creator initialized');

            // Set default template
            this.selectTemplate('2x2');
        },

        /**
         * เลือก template
         */
        selectTemplate(template) {
            this.selectedTemplate = template;

            // Generate default menu data
            const templates = {
                '2x2': {
                    areas: [
                        { bounds: { x: 0, y: 0, width: 1250, height: 843 }, action: { type: 'uri', uri: 'https://example.com/1' } },
                        { bounds: { x: 1250, y: 0, width: 1250, height: 843 }, action: { type: 'message', text: 'สวัสดี' } },
                        { bounds: { x: 0, y: 843, width: 1250, height: 843 }, action: { type: 'uri', uri: 'https://example.com/2' } },
                        { bounds: { x: 1250, y: 843, width: 1250, height: 843 }, action: { type: 'postback', data: 'action=buy' } }
                    ]
                },
                '3x3': {
                    areas: Array.from({ length: 9 }, (_, i) => ({
                        bounds: {
                            x: (i % 3) * 833,
                            y: Math.floor(i / 3) * 562,
                            width: 833,
                            height: 562
                        },
                        action: { type: 'uri', uri: `https://example.com/${i + 1}` }
                    }))
                },
                'custom': {
                    areas: []
                }
            };

            this.formData.menuData = JSON.stringify(templates[template], null, 2);
        },

        /**
         * Handle image upload
         */
        handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        /**
         * ไปขั้นตอนถัดไป
         */
        nextStep() {
            if (this.validateCurrentStep()) {
                this.currentStep++;
            }
        },

        /**
         * ย้อนกลับขั้นตอนก่อนหน้า
         */
        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        /**
         * Validate current step
         */
        validateCurrentStep() {
            if (this.currentStep === 0) {
                if (!this.selectedTemplate) {
                    alert('กรุณาเลือก Template');
                    return false;
                }
            }

            if (this.currentStep === 1) {
                if (!this.formData.name) {
                    alert('กรุณากรอกชื่อเมนู');
                    return false;
                }
                if (!this.formData.chatBarText) {
                    alert('กรุณากรอกข้อความบน Chat Bar');
                    return false;
                }
            }

            return true;
        }
    };
}

// Export global
window.richMenuCreator = richMenuCreator;
</script>
@endpush
@endsection
