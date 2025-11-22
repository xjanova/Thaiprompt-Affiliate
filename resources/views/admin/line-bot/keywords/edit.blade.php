@extends('layouts.admin-v3')

@section('title', 'แก้ไข Keyword')

@section('content')
<div class="container-fluid px-4 py-6 max-w-7xl mx-auto" x-data="keywordEditForm">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Header พร้อม LINE Green theme --}}
            <div>
                <a href="{{ route('admin.line-bot.keywords.index') }}"
                   class="inline-flex items-center gap-2 text-[#00B900] hover:text-[#009900] dark:text-[#00E600] dark:hover:text-[#00D000] mb-4 font-semibold transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับไปยังรายการ Keywords</span>
                </a>
                <div class="glass-fusion backdrop-blur-xl rounded-2xl p-8 border border-white/20 dark:border-white/10 shadow-xl bg-gradient-to-br from-[#00B900]/10 via-transparent to-[#00E600]/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                                <i class="fas fa-edit text-white text-3xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-black text-gray-900 dark:text-white">แก้ไข Keyword</h1>
                                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $keyword->keyword }}</p>
                            </div>
                        </div>
                        @if($keyword->is_active)
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-[#00B900]/20 text-[#00B900] dark:text-[#00E600] border border-[#00B900]/30">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        @else
                            <span class="px-4 py-2 rounded-full text-sm font-bold bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300 border border-red-300 dark:border-red-800">
                                <i class="fas fa-ban mr-1"></i>Inactive
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.line-bot.keywords.update', $keyword) }}" class="space-y-6" @submit="validateForm">
                @csrf
                @method('PUT')

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
                                class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 @error('keyword') border-red-500 @enderror"
                                value="{{ old('keyword', $keyword->keyword) }}" required>
                            @error('keyword')
                                <p class="text-red-600 dark:text-red-400 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">คำอธิบาย</label>
                            <textarea name="description" placeholder="คำอธิบายสำหรับ Keyword นี้"
                                x-model="form.description"
                                class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300"
                                rows="2">{{ old('description', $keyword->description) }}</textarea>
                        </div>

                        {{-- Category & Priority --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                    หมวดหมู่ <span class="text-red-600">*</span>
                                </label>
                                <select name="category"
                                        x-model="form.category"
                                        class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300" required>
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}" @selected(old('category', $keyword->category) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                                    Priority (1-100) <span class="text-red-600">*</span>
                                </label>
                                <div class="flex items-center gap-4">
                                    <input type="range" name="priority" min="1" max="100"
                                           x-model="form.priority"
                                           value="{{ old('priority', $keyword->priority) }}"
                                           class="flex-1 h-3 bg-gray-200 dark:bg-gray-600 rounded-full appearance-none cursor-pointer accent-[#00B900]">
                                    <span class="text-2xl font-black bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent min-w-[60px] text-right" x-text="form.priority">{{ $keyword->priority }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Active Status --}}
                        <div class="flex items-center gap-3 p-4 glass-fusion backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-600">
                            <input type="checkbox" name="is_active" value="1"
                                   x-model="form.is_active"
                                   {{ old('is_active', $keyword->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 text-[#00B900] rounded focus:ring-[#00B900]/20">
                            <label class="flex-1">
                                <span class="text-gray-900 dark:text-white font-semibold">เปิดใช้งาน Keyword นี้</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Trigger Words --}}
                <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-tag text-white"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Trigger Words</h2>
                    </div>

                    <textarea name="trigger_words" placeholder="refund, คืนเงิน, return, การคืนเงิน"
                        x-model="form.trigger_words"
                        @input="previewTriggers()"
                        class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 font-mono"
                        rows="4" required>{{ old('trigger_words', $triggerWordsText) }}</textarea>

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
                </div>

                {{-- Response Type --}}
                <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-reply text-white"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">ประเภทคำตอบ</h2>
                    </div>

                    <div class="space-y-4">
                        <label class="flex items-start p-4 glass-fusion backdrop-blur-sm border-2 rounded-xl cursor-pointer transition-all duration-300 hover:shadow-lg"
                               :class="form.response_type === 'text' ? 'border-[#00B900] bg-[#00B900]/5' : 'border-gray-300 dark:border-gray-600'">
                            <input type="radio" name="response_type" value="text"
                                   x-model="form.response_type"
                                   class="w-5 h-5 mt-0.5 text-[#00B900] focus:ring-[#00B900]/20"
                                   @checked(old('response_type', $keyword->response_type) === 'text')>
                            <span class="ml-3 flex-1">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">📝 ข้อความธรรมชาติ</span>
                            </span>
                        </label>

                        <label class="flex items-start p-4 glass-fusion backdrop-blur-sm border-2 rounded-xl cursor-pointer transition-all duration-300 hover:shadow-lg"
                               :class="form.response_type === 'quick_reply' ? 'border-[#00B900] bg-[#00B900]/5' : 'border-gray-300 dark:border-gray-600'">
                            <input type="radio" name="response_type" value="quick_reply"
                                   x-model="form.response_type"
                                   class="w-5 h-5 mt-0.5 text-[#00B900] focus:ring-[#00B900]/20"
                                   @checked(old('response_type', $keyword->response_type) === 'quick_reply')>
                            <span class="ml-3 flex-1">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">⚡ Quick Reply</span>
                            </span>
                        </label>

                        <label class="flex items-start p-4 glass-fusion backdrop-blur-sm border-2 rounded-xl cursor-pointer transition-all duration-300 hover:shadow-lg"
                               :class="form.response_type === 'flex_message' ? 'border-[#00B900] bg-[#00B900]/5' : 'border-gray-300 dark:border-gray-600'">
                            <input type="radio" name="response_type" value="flex_message"
                                   x-model="form.response_type"
                                   class="w-5 h-5 mt-0.5 text-[#00B900] focus:ring-[#00B900]/20"
                                   @checked(old('response_type', $keyword->response_type) === 'flex_message')>
                            <span class="ml-3 flex-1">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">🎨 Flex Message</span>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Response Content --}}
                <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
                    <div x-show="form.response_type === 'text' || form.response_type === 'quick_reply'" x-transition class="space-y-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                                <i class="fas fa-quote-left text-white"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อความตอบกลับ</h2>
                        </div>

                        <textarea name="response_text" placeholder="ตอบได้อย่างนี้..."
                            x-model="form.response_text"
                            class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300"
                            rows="6">{{ old('response_text', $keyword->response_text) }}</textarea>
                    </div>

                    <div x-show="form.response_type === 'flex_message'" x-transition class="space-y-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                                <i class="fas fa-code text-white"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Flex Message JSON</h2>
                        </div>

                        <textarea name="response_flex_json"
                            x-model="form.response_flex_json"
                            @input="validateJSON"
                            class="w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-[#00B900]/20 focus:border-[#00B900] transition-all duration-300 font-mono text-sm"
                            rows="12">{{ old('response_flex_json', $keyword->response_flex_json ? json_encode($keyword->response_flex_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
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
                        rows="3">{{ old('notes', $keyword->notes) }}</textarea>
                </div>

                {{-- Form Actions --}}
                <div class="flex gap-4 sticky bottom-6 z-10">
                    <button type="submit"
                            class="flex-1 px-6 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] hover:from-[#009900] hover:to-[#00D000] text-white font-bold rounded-xl transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 text-lg">
                        <i class="fas fa-save mr-2"></i>บันทึกการแก้ไข
                    </button>
                    <a href="{{ route('admin.line-bot.keywords.index') }}"
                       class="flex-1 px-6 py-4 glass-fusion backdrop-blur-xl border-2 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white font-bold rounded-xl hover:shadow-xl transition-all duration-300 text-center text-lg">
                        <i class="fas fa-times mr-2"></i>ยกเลิก
                    </a>
                </div>
            </form>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Metadata --}}
            <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-6 sticky top-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-[#00B900]"></i>
                    ข้อมูลระบบ
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ID</span>
                        <span class="font-bold text-gray-900 dark:text-white">#{{ $keyword->id }}</span>
                    </div>

                    <div class="py-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400 block mb-2">สร้างเมื่อ</span>
                        <span class="font-semibold text-gray-900 dark:text-white text-sm">{{ $keyword->created_at->format('d/m/Y H:i') }}</span>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $keyword->created_at->diffForHumans() }}</p>
                    </div>

                    <div class="py-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400 block mb-2">แก้ไขล่าสุด</span>
                        <span class="font-semibold text-gray-900 dark:text-white text-sm">{{ $keyword->updated_at->format('d/m/Y H:i') }}</span>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $keyword->updated_at->diffForHumans() }}</p>
                    </div>

                    <div class="py-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400 block mb-2">สถานะ</span>
                        @if($keyword->is_active)
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold bg-[#00B900]/20 text-[#00B900] dark:text-[#00E600]">
                                <i class="fas fa-check-circle"></i>
                                ใช้งาน
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300">
                                <i class="fas fa-ban"></i>
                                ปิดใช้งาน
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-bolt text-yellow-500"></i>
                    Quick Actions
                </h3>

                <div class="space-y-3">
                    <button @click="duplicateKeyword"
                            class="w-full px-4 py-3 glass-fusion backdrop-blur-sm border border-[#00B900]/30 text-[#00B900] dark:text-[#00E600] rounded-xl hover:bg-[#00B900]/10 transition-all duration-300 font-semibold text-sm text-left flex items-center gap-3">
                        <i class="fas fa-copy"></i>
                        <span>ทำสำเนา Keyword นี้</span>
                    </button>

                    <button @click="testKeyword"
                            class="w-full px-4 py-3 glass-fusion backdrop-blur-sm border border-blue-300 dark:border-blue-600 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-500/10 transition-all duration-300 font-semibold text-sm text-left flex items-center gap-3">
                        <i class="fas fa-flask"></i>
                        <span>ทดสอบ Keyword</span>
                    </button>

                    <form method="POST" action="{{ route('admin.line-bot.keywords.destroy', $keyword) }}" onsubmit="return confirm('ยืนยันการลบ Keyword นี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-3 glass-fusion backdrop-blur-sm border border-red-300 dark:border-red-600 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-50 dark:hover:bg-red-500/10 transition-all duration-300 font-semibold text-sm text-left flex items-center gap-3">
                            <i class="fas fa-trash"></i>
                            <span>ลบ Keyword นี้</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('keywordEditForm', () => ({
        form: {
            keyword: '{{ old("keyword", $keyword->keyword) }}',
            description: '{{ old("description", $keyword->description) }}',
            category: '{{ old("category", $keyword->category) }}',
            priority: {{ old('priority', $keyword->priority) }},
            is_active: {{ old('is_active', $keyword->is_active) ? 'true' : 'false' }},
            trigger_words: '{{ old("trigger_words", $triggerWordsText) }}',
            response_type: '{{ old("response_type", $keyword->response_type) }}',
            response_text: '{{ old("response_text", $keyword->response_text) }}',
            response_flex_json: @json(old('response_flex_json', $keyword->response_flex_json ? json_encode($keyword->response_flex_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '')),
            notes: '{{ old("notes", $keyword->notes) }}'
        },
        triggerPreview: [],
        jsonError: '',

        init() {
            this.previewTriggers();
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

        validateForm(e) {
            if (!this.form.keyword.trim() || !this.form.category || !this.form.trigger_words.trim()) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
                return false;
            }
            return true;
        },

        duplicateKeyword() {
            window.location.href = '{{ route("admin.line-bot.keywords.index") }}/{{ $keyword->id }}/clone';
        },

        testKeyword() {
            window.location.href = '{{ route("admin.line-bot.keywords.index") }}?test={{ $keyword->keyword }}';
        }
    }));
});
</script>
@endpush

@vite(['resources/js/app.js'])
@endsection
