@extends('layouts.admin-v3')

@section('title', 'แก้ไข LINE Rich Menu')

@section('content')
<div class="container-fluid px-4 py-6" x-data="richMenuEditor()" x-init="init()">

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.line-bot.rich-menu.index') }}"
                   class="text-gray-600 dark:text-gray-400 hover:text-[#06C755] transition">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">แก้ไข LINE Rich Menu</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">แก้ไขเมนูอินเทอร์แอกทีฟสำหรับ LINE Chat</p>
                </div>
            </div>

            {{-- Duplicate Button --}}
            <form method="POST" action="{{ route('admin.line-bot.rich-menu.store') }}">
                @csrf
                <input type="hidden" name="duplicate_from" value="{{ $richMenu->id ?? '' }}">
                <button type="submit"
                        class="px-6 py-3 bg-white dark:bg-slate-700 border-2 border-[#06C755] text-[#06C755] dark:text-[#00E600] rounded-xl hover:bg-[#06C755] hover:text-white transition-all font-semibold transform hover:scale-105">
                    <i class="fas fa-copy mr-2"></i>ทำสำเนา Rich Menu
                </button>
            </form>
        </div>
    </div>

    {{-- Active Menu Warning --}}
    @if($richMenu->is_default ?? false)
        <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-[#00B900]/10 to-[#00E600]/10 border border-[#06C755]/30 backdrop-blur-sm">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-[#06C755] text-xl mt-0.5"></i>
                <div>
                    <h4 class="font-semibold text-[#00B900] dark:text-[#00E600] mb-1">Rich Menu นี้กำลังใช้งานอยู่</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">การแก้ไขจะมีผลกับผู้ใช้ทันที กรุณาตรวจสอบให้ถี่ถ้วนก่อนบันทึก</p>
                </div>
            </div>
        </div>
    @endif

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

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Main Content (3 columns) --}}
        <div class="lg:col-span-3 space-y-6">
            {{-- Current Preview --}}
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-eye text-[#06C755]"></i>
                    ตัวอย่างปัจจุบัน
                </h3>

                @if($richMenu->menu_image_url ?? false)
                    <div class="relative aspect-[2500/1686] bg-gray-900 rounded-xl overflow-hidden">
                        <img src="{{ $richMenu->menu_image_url }}" alt="{{ $richMenu->name ?? 'Rich Menu' }}" class="w-full h-full object-cover">
                        {{-- LINE Overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent">
                            <div class="absolute bottom-4 left-4 right-4">
                                <div class="flex items-center gap-2 text-white text-sm">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    <span class="font-semibold">{{ $richMenu->chat_bar_text ?? 'Menu' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Edit Form --}}
            <form method="POST" action="{{ route('admin.line-bot.rich-menu.update', $richMenu->id ?? 0) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Basic Info --}}
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 mb-6 border border-white/20 dark:border-slate-700/50">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-[#06C755]"></i>
                        ข้อมูลพื้นฐาน
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อเมนู *
                            </label>
                            <input type="text" name="name" value="{{ old('name', $richMenu->name ?? '') }}" required
                                   class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ข้อความบน Chat Bar * (สูงสุด 14 ตัวอักษร)
                            </label>
                            <input type="text" name="chat_bar_text" value="{{ old('chat_bar_text', $richMenu->chat_bar_text ?? 'Menu') }}" required maxlength="14"
                                   class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ขนาดเมนู
                            </label>
                            <select name="size"
                                    class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] text-gray-900 dark:text-white">
                                <option value="full" {{ old('size', $richMenu->size ?? 'full') === 'full' ? 'selected' : '' }}>Full (2500x1686px)</option>
                                <option value="half" {{ old('size', $richMenu->size ?? '') === 'half' ? 'selected' : '' }}>Half (2500x843px)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                อัปโหลดภาพพื้นหลังใหม่ (ถ้าต้องการเปลี่ยน)
                            </label>
                            <input type="file" name="menu_image" accept="image/*"
                                   class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] text-gray-900 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                PNG, JPG (แนะนำ 2500x1686px หรือ 2500x843px) - เว้นว่างไว้หากไม่ต้องการเปลี่ยน
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Menu Structure --}}
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 mb-6 border border-white/20 dark:border-slate-700/50">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-code text-[#06C755]"></i>
                        Menu Structure (JSON)
                    </h3>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        กำหนดพื้นที่คลิกได้และ action ของแต่ละพื้นที่ ดูตัวอย่างได้ที่
                        <a href="https://developers.line.biz/en/docs/messaging-api/using-rich-menus/" target="_blank"
                           class="text-[#06C755] hover:underline font-semibold">
                            LINE Rich Menu Documentation
                        </a>
                    </p>

                    <textarea name="menu_data" rows="20" required
                              class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[#06C755] font-mono text-sm text-gray-900 dark:text-white"
                              placeholder='{"areas": [{"bounds": {"x": 0, "y": 0, "width": 1250, "height": 843}, "action": {"type": "uri", "uri": "https://example.com"}}]}'>{{ old('menu_data', isset($richMenu) ? json_encode($richMenu->menu_data, JSON_PRETTY_PRINT) : '') }}</textarea>
                </div>

                {{-- Submit Buttons --}}
                <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 border border-white/20 dark:border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('admin.line-bot.rich-menu.index') }}"
                           class="px-6 py-3 bg-white dark:bg-slate-700 border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-600 transition-all font-semibold">
                            <i class="fas fa-times mr-2"></i>ยกเลิก
                        </a>

                        <button type="submit"
                                class="px-8 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition-all font-bold transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>บันทึกการแก้ไข
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Sidebar (1 column) - Statistics --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Stats Card --}}
            <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-2xl shadow-lg p-6 sticky top-6 border border-white/20 dark:border-slate-700/50">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-bar text-[#06C755]"></i>
                    สถิติการใช้งาน
                </h3>

                <div class="space-y-4">
                    {{-- Total Impressions --}}
                    <div class="p-4 bg-gradient-to-br from-blue-50/80 to-indigo-50/80 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Total Impressions</span>
                            <i class="fas fa-eye text-blue-500"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">1,234</h4>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">ครั้งที่แสดง</p>
                    </div>

                    {{-- Total Clicks --}}
                    <div class="p-4 bg-gradient-to-br from-[#00B900]/10 to-[#00E600]/10 dark:from-[#00B900]/20 dark:to-[#00E600]/20 rounded-xl border border-[#06C755]/30">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-[#00B900] dark:text-[#00E600]">Total Clicks</span>
                            <i class="fas fa-mouse-pointer text-[#06C755]"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">567</h4>
                        <p class="text-xs text-[#00B900] dark:text-[#00E600] mt-1">คลิกทั้งหมด</p>
                    </div>

                    {{-- CTR --}}
                    <div class="p-4 bg-gradient-to-br from-purple-50/80 to-pink-50/80 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-purple-700 dark:text-purple-300">Click Through Rate</span>
                            <i class="fas fa-percentage text-purple-500"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">45.9%</h4>
                        <p class="text-xs text-purple-600 dark:text-purple-400 mt-1">อัตราการคลิก</p>
                    </div>

                    {{-- Last Updated --}}
                    <div class="p-4 bg-gray-50 dark:bg-slate-700/50 rounded-xl border border-gray-200 dark:border-slate-600">
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-clock"></i>
                            <span>อัพเดทล่าสุด</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ isset($richMenu->updated_at) ? $richMenu->updated_at->diffForHumans() : 'ไม่ทราบ' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tips Card --}}
            <div class="glass-fusion backdrop-blur-xl bg-gradient-to-br from-[#00B900]/10 to-[#00E600]/10 dark:from-[#00B900]/20 dark:to-[#00E600]/20 rounded-2xl shadow-lg p-6 border border-[#06C755]/30">
                <h3 class="font-bold text-[#00B900] dark:text-[#00E600] mb-4 flex items-center gap-2">
                    <i class="fas fa-lightbulb"></i>
                    เคล็ดลับ
                </h3>

                <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-check text-[#06C755] mt-1"></i>
                        <p>ใช้รูปภาพที่มีความละเอียดสูงเพื่อความคมชัด</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-check text-[#06C755] mt-1"></i>
                        <p>ตรวจสอบพื้นที่คลิกให้ถูกต้องก่อนบันทึก</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="fas fa-check text-[#06C755] mt-1"></i>
                        <p>ทดสอบการทำงานบนมือถือจริงก่อนเผยแพร่</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Rich Menu Editor Component - แก้ไข LINE Rich Menu
 *
 * @returns {object} Alpine component
 */
function richMenuEditor() {
    return {
        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Rich Menu Editor initialized');
        }
    };
}

// Export global
window.richMenuEditor = richMenuEditor;
</script>
@endpush
@endsection
