@extends('layouts.admin-v3')

@section('title', 'สร้าง Keyword ใหม่')

@section('content')
<div class="container-fluid px-4 py-6 max-w-5xl mx-auto" x-data="keywordForm">
    {{-- Header พร้อม LINE Green theme --}}
    <div class="mb-8">
        <a href="{{ route('admin.line-bot.keywords.index') }}"
           class="inline-flex items-center gap-2 text-[#00B900] hover:text-[#009900] dark:text-[#00E600] dark:hover:text-[#00D000] mb-4 font-semibold transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปยังรายการ Keywords</span>
        </a>
        <div class="glass-fusion backdrop-blur-xl rounded-2xl p-8 border border-white/20 dark:border-white/10 shadow-xl bg-gradient-to-br from-[#00B900]/10 via-transparent to-[#00E600]/10">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                    <i class="fas fa-plus-circle text-white text-3xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white">สร้าง Keyword ใหม่</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">เพิ่ม Keyword ใหม่ให้กับระบบ Hybrid Bot</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress Indicator --}}
    <div class="mb-8 glass-fusion backdrop-blur-xl rounded-xl p-4 border border-white/20 dark:border-white/10">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">ความคืบหน้า</span>
            <span class="text-sm font-bold text-[#00B900] dark:text-[#00E600]" x-text="`${completionPercent}%`">0%</span>
        </div>
        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-[#00B900] to-[#00E600] transition-all duration-500"
                 :style="`width: ${completionPercent}%`"></div>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.line-bot.keywords.store') }}" class="space-y-8" @submit="validateForm">
        @csrf

        {{-- Basic Information --}}
        <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                    <i class="fas fa-info-circle text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h2>
            </div>

            <div class="space-y-6">
                {{-- Keyword Name --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        Keyword Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="keyword" placeholder="เช่น refund, shipping, payment"
                        x-model="form.keyword"
                        @input="updateCompletion"
                        class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 @error('keyword') border-red-500 @enderror"
                        value="{{ old('keyword') }}" required>
                    @error('keyword')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">ชื่อเฉพาะตัวที่ไม่ซ้ำกัน</p>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description" placeholder="คำอธิบายสำหรับ Keyword นี้"
                        x-model="form.description"
                        class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300"
                        rows="2">{{ old('description') }}</textarea>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">ไม่จำเป็น - ใช้เพื่อบันทึก</p>
                </div>

                {{-- Category & Priority Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                            หมวดหมู่ <span class="text-red-600">*</span>
                        </label>
                        <select name="category"
                                x-model="form.category"
                                @change="updateCompletion"
                                class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 @error('category') border-red-500 @enderror" required>
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="text-red-600 dark:text-red-400 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                            Priority (1-100) <span class="text-red-600">*</span>
                        </label>
                        <div class="flex items-center gap-4">
                            <input type="range" name="priority" min="1" max="100"
                                   x-model="form.priority"
                                   value="{{ old('priority', 50) }}"
                                   class="flex-1 h-3 bg-gray-200 dark:bg-gray-600 rounded-full appearance-none cursor-pointer accent-[#00B900]">
                            <span class="text-2xl font-black bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent min-w-[60px] text-right" x-text="form.priority">50</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">ตัวเลขสูงกว่า = ตรวจสอบก่อน (1 = ต่ำสุด, 100 = สูงสุด)</p>
                    </div>
                </div>

                {{-- Active Status --}}
                <div class="flex items-center gap-3 p-4 glass-fusion backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-600">
                    <input type="checkbox" name="is_active" value="1"
                           x-model="form.is_active"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-5 h-5 text-[#00B900] rounded focus:ring-[#00B900]/20">
                    <label class="flex-1">
                        <span class="text-gray-900 dark:text-white font-semibold">เปิดใช้งาน Keyword นี้</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Keyword จะทำงานทันทีหลังจากบันทึก</p>
                    </label>
                </div>
            </div>
        </div>

        {{-- Trigger Words --}}
        <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-tag text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Trigger Words</h2>
            </div>
            <p class="text-gray-600 dark:text-gray-400 mb-6">คำที่จะสร้างความตรงกับ Keyword นี้ (คั่นด้วยเครื่องหมายจุลภาค)</p>

            <div class="relative">
                <textarea name="trigger_words" placeholder="refund, คืนเงิน, return, การคืนเงิน"
                    x-model="form.trigger_words"
                    @input="updateCompletion; previewTriggers()"
                    class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 font-mono @error('trigger_words') border-red-500 @enderror"
                    rows="4" required>{{ old('trigger_words') }}</textarea>
                @error('trigger_words')
                    <p class="text-red-600 dark:text-red-400 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                @enderror
            </div>

            {{-- Trigger Preview --}}
            <div x-show="triggerPreview.length > 0" x-transition class="mt-4">
                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">
                    Preview (<span x-text="triggerPreview.length"></span> triggers):
                </p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="(trigger, index) in triggerPreview" :key="index">
                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 rounded-full text-sm font-medium">
                            <span x-text="trigger"></span>
                        </span>
                    </template>
                </div>
            </div>

            <p class="text-gray-500 dark:text-gray-400 text-sm mt-4">🔍 ใช้ทั้งภาษาอังกฤษและไทย เพื่อให้มีการจับคู่ที่ดีขึ้น</p>
        </div>

        {{-- Response Type Selection --}}
        <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-reply text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">ประเภทคำตอบ</h2>
            </div>

            <div class="space-y-4 mb-6">
                <label class="flex items-start p-4 glass-fusion backdrop-blur-sm border-2 rounded-xl cursor-pointer transition-all duration-300 hover:shadow-lg"
                       :class="form.response_type === 'text' ? 'border-[#00B900] bg-[#00B900]/5' : 'border-gray-300 dark:border-gray-600'">
                    <input type="radio" name="response_type" value="text"
                           x-model="form.response_type"
                           @change="updateCompletion"
                           class="w-5 h-5 mt-0.5 text-[#00B900] focus:ring-[#00B900]/20"
                           @checked(!old('response_type') || old('response_type') === 'text')>
                    <span class="ml-3 flex-1">
                        <span class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            📝 ข้อความธรรมชาติ
                            <span x-show="form.response_type === 'text'" class="text-xs px-2 py-1 bg-[#00B900] text-white rounded-full">เลือกอยู่</span>
                        </span>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">ตอบด้วยข้อความธรรมชาติ</p>
                    </span>
                </label>

                <label class="flex items-start p-4 glass-fusion backdrop-blur-sm border-2 rounded-xl cursor-pointer transition-all duration-300 hover:shadow-lg"
                       :class="form.response_type === 'quick_reply' ? 'border-[#00B900] bg-[#00B900]/5' : 'border-gray-300 dark:border-gray-600'">
                    <input type="radio" name="response_type" value="quick_reply"
                           x-model="form.response_type"
                           @change="updateCompletion"
                           class="w-5 h-5 mt-0.5 text-[#00B900] focus:ring-[#00B900]/20">
                    <span class="ml-3 flex-1">
                        <span class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            ⚡ Quick Reply
                            <span x-show="form.response_type === 'quick_reply'" class="text-xs px-2 py-1 bg-[#00B900] text-white rounded-full">เลือกอยู่</span>
                        </span>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">ตอบพร้อมปุ่มตัวเลือก</p>
                    </span>
                </label>

                <label class="flex items-start p-4 glass-fusion backdrop-blur-sm border-2 rounded-xl cursor-pointer transition-all duration-300 hover:shadow-lg"
                       :class="form.response_type === 'flex_message' ? 'border-[#00B900] bg-[#00B900]/5' : 'border-gray-300 dark:border-gray-600'">
                    <input type="radio" name="response_type" value="flex_message"
                           x-model="form.response_type"
                           @change="updateCompletion"
                           class="w-5 h-5 mt-0.5 text-[#00B900] focus:ring-[#00B900]/20">
                    <span class="ml-3 flex-1">
                        <span class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            🎨 Flex Message
                            <span x-show="form.response_type === 'flex_message'" class="text-xs px-2 py-1 bg-[#00B900] text-white rounded-full">เลือกอยู่</span>
                        </span>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">ตอบด้วยการออกแบบ Rich Message</p>
                    </span>
                </label>
            </div>

            @error('response_type')
                <p class="text-red-600 dark:text-red-400 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
            @enderror
        </div>

        {{-- Response Content (Dynamic) --}}
        <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
            {{-- Text/Quick Reply Response --}}
            <div x-show="form.response_type === 'text' || form.response_type === 'quick_reply'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="space-y-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-quote-left text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อความตอบกลับ</h2>
                </div>

                <textarea name="response_text" placeholder="ตอบได้อย่างนี้..."
                    x-model="form.response_text"
                    @input="updateCompletion"
                    class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300"
                    rows="6">{{ old('response_text') }}</textarea>
                <p class="text-gray-500 dark:text-gray-400 text-sm">💡 ใช้ emojis และการจัดรูปแบบเพื่อให้ข้อความดูดีขึ้น</p>

                {{-- Preview --}}
                <div x-show="form.response_text.trim()" x-transition class="p-4 glass-fusion backdrop-blur-sm rounded-xl border border-[#00B900]/30">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Preview:</p>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-gray-900 dark:text-white whitespace-pre-wrap" x-text="form.response_text"></p>
                    </div>
                </div>
            </div>

            {{-- Flex Message Response --}}
            <div x-show="form.response_type === 'flex_message'"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="space-y-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-code text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Flex Message JSON</h2>
                </div>

                <textarea name="response_flex_json" placeholder='{"type":"bubble","body":{"type":"box","layout":"vertical","contents":[]}}'
                    x-model="form.response_flex_json"
                    @input="validateJSON"
                    class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 font-mono text-sm"
                    rows="12">{{ old('response_flex_json') }}</textarea>

                {{-- JSON Validation --}}
                <div x-show="jsonError" x-transition class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <p class="text-red-600 dark:text-red-400 text-sm font-semibold">
                        <i class="fas fa-exclamation-triangle mr-2"></i>JSON Format Error
                    </p>
                    <p class="text-red-500 dark:text-red-300 text-xs mt-1" x-text="jsonError"></p>
                </div>

                <div x-show="!jsonError && form.response_flex_json.trim()" x-transition class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                    <p class="text-green-600 dark:text-green-400 text-sm font-semibold">
                        <i class="fas fa-check-circle mr-2"></i>Valid JSON Format
                    </p>
                </div>

                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    📖 <a href="https://developers.line.biz/en/docs/messaging-api/using-flex-messages/" target="_blank" class="text-[#00B900] hover:text-[#009900] dark:text-[#00E600] dark:hover:text-[#00D000] font-semibold hover:underline">ดู Flex Message Documentation</a>
                </p>
            </div>
        </div>

        {{-- Additional Notes --}}
        <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-sticky-note text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">หมายเหตุเพิ่มเติม</h2>
            </div>

            <textarea name="notes" placeholder="หมายเหตุสำหรับทีมหรือบันทึกการปรับแต่ง"
                x-model="form.notes"
                class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300"
                rows="3">{{ old('notes') }}</textarea>
        </div>

        {{-- Form Actions --}}
        <div class="flex gap-4 sticky bottom-6 z-10">
            <button type="submit"
                    :disabled="!isFormValid"
                    :class="isFormValid ? 'opacity-100 cursor-pointer' : 'opacity-50 cursor-not-allowed'"
                    class="flex-1 px-6 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#009900] hover:to-[#00D000] text-white font-bold rounded-xl transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 text-lg">
                <i class="fas fa-save mr-2"></i>สร้าง Keyword
            </button>
            <a href="{{ route('admin.line-bot.keywords.index') }}"
               class="flex-1 px-6 py-4 glass-fusion backdrop-blur-xl border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white font-bold rounded-xl hover:shadow-xl transition-all duration-300 text-center text-lg">
                <i class="fas fa-times mr-2"></i>ยกเลิก
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('keywordForm', () => ({
        form: {
            keyword: '{{ old("keyword") }}',
            description: '{{ old("description") }}',
            category: '{{ old("category") }}',
            priority: {{ old('priority', 50) }},
            is_active: {{ old('is_active', true) ? 'true' : 'false' }},
            trigger_words: '{{ old("trigger_words") }}',
            response_type: '{{ old("response_type", "text") }}',
            response_text: '{{ old("response_text") }}',
            response_flex_json: '{{ old("response_flex_json") }}',
            notes: '{{ old("notes") }}'
        },
        triggerPreview: [],
        jsonError: '',
        completionPercent: 0,

        get isFormValid() {
            return this.form.keyword.trim() &&
                   this.form.category &&
                   this.form.trigger_words.trim() &&
                   this.form.response_type &&
                   (this.form.response_type === 'flex_message' ? !this.jsonError && this.form.response_flex_json.trim() : this.form.response_text.trim());
        },

        init() {
            this.previewTriggers();
            this.updateCompletion();
        },

        previewTriggers() {
            if (this.form.trigger_words.trim()) {
                this.triggerPreview = this.form.trigger_words
                    .split(',')
                    .map(t => t.trim())
                    .filter(t => t.length > 0);
            } else {
                this.triggerPreview = [];
            }
        },

        validateJSON() {
            if (!this.form.response_flex_json.trim()) {
                this.jsonError = '';
                return;
            }

            try {
                JSON.parse(this.form.response_flex_json);
                this.jsonError = '';
            } catch (e) {
                this.jsonError = e.message;
            }
        },

        updateCompletion() {
            let completed = 0;
            const total = 6; // จำนวน fields สำคัญทั้งหมด

            if (this.form.keyword.trim()) completed++;
            if (this.form.category) completed++;
            if (this.form.trigger_words.trim()) completed++;
            if (this.form.response_type) completed++;
            if (this.form.response_type === 'flex_message') {
                if (this.form.response_flex_json.trim() && !this.jsonError) completed++;
            } else {
                if (this.form.response_text.trim()) completed++;
            }
            if (this.form.priority) completed++;

            this.completionPercent = Math.round((completed / total) * 100);
        },

        validateForm(e) {
            if (!this.isFormValid) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }
            return true;
        }
    }));
});
</script>
@endpush

@vite(['resources/js/app.js'])
@endsection
