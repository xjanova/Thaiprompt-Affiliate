@extends('layouts.admin-v3')

@section('title', 'สร้าง A/B Test')

@section('content')
<div class="container-fluid px-4 py-6" x-data="abTestCreator()" x-init="init()">
    {{-- Header with LINE Green --}}
    <div class="mb-8">
        <a href="{{ route('admin.line-bot.keywords.ab-tests.index') }}"
           class="inline-flex items-center gap-2 text-[#06C755] hover:text-emerald-600 dark:text-green-400 dark:hover:text-green-300 font-semibold mb-4 transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับ</span>
        </a>

        <div class="relative overflow-hidden bg-gradient-to-r from-[#06C755] via-green-500 to-emerald-600 rounded-2xl p-8 shadow-2xl">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.4),transparent_60%)]"></div>
            </div>

            <div class="relative z-10">
                <h1 class="text-4xl font-black text-white mb-2 flex items-center gap-3">
                    <span class="text-5xl">🧪</span>
                    <span>สร้าง A/B Test ใหม่</span>
                </h1>
                <p class="text-white/90 text-lg">
                    ทดสอบประสิทธิภาพของคีย์เวิร์ดและค้นหาผู้ชนะ
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.line-bot.keywords.ab-tests.store') }}" method="POST" @submit="validateForm" class="space-y-6">
        @csrf

        {{-- Section 1: Test Info --}}
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
            <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-white"></i>
                </div>
                <span>ข้อมูลการทดสอบ</span>
            </h2>

            <div class="space-y-4">
                {{-- Keyword Selection --}}
                <div>
                    <label for="keyword_id" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-key mr-2 text-[#06C755]"></i>คีย์เวิร์ดที่ต้องการทดสอบ *
                    </label>
                    <select id="keyword_id" name="keyword_id" x-model="formData.keyword_id" required
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
                        <option value="">-- เลือกคีย์เวิร์ด --</option>
                        @foreach($keywords as $keyword)
                            <option value="{{ $keyword->id }}" {{ $selected_keyword?->id === $keyword->id ? 'selected' : '' }}>
                                {{ $keyword->keyword }}
                            </option>
                        @endforeach
                    </select>
                    @error('keyword_id')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1 flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Test Name --}}
                <div>
                    <label for="test_name" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-tag mr-2 text-[#06C755]"></i>ชื่อการทดสอบ *
                    </label>
                    <input type="text" id="test_name" name="test_name" x-model="formData.test_name" required
                        placeholder="เช่น Test response greeting variations"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all"
                        value="{{ old('test_name') }}">
                    @error('test_name')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1 flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-align-left mr-2 text-[#06C755]"></i>คำอธิบาย (ไม่บังคับ)
                    </label>
                    <textarea id="description" name="description" x-model="formData.description" rows="3"
                        placeholder="คำอธิบายโดยละเอียดเกี่ยวกับการทดสอบนี้"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all resize-none">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Section 2: Test Configuration --}}
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
            <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-cog text-white"></i>
                </div>
                <span>การตั้งค่าการทดสอบ</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                {{-- Variant A % --}}
                <div>
                    <label for="variant_a_percentage" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-percentage mr-2 text-blue-500"></i>Variant A %
                    </label>
                    <input type="number" id="variant_a_percentage" name="variant_a_percentage" x-model.number="formData.variant_a_percentage" required min="1" max="99"
                        @input="formData.variant_b_percentage = 100 - $event.target.value"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-center text-2xl font-black"
                        value="{{ old('variant_a_percentage', 50) }}">
                </div>

                {{-- Variant B % --}}
                <div>
                    <label for="variant_b_percentage" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-percentage mr-2 text-purple-500"></i>Variant B %
                    </label>
                    <input type="number" id="variant_b_percentage" name="variant_b_percentage" x-model.number="formData.variant_b_percentage" required min="1" max="99"
                        @input="formData.variant_a_percentage = 100 - $event.target.value"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-center text-2xl font-black"
                        value="{{ old('variant_b_percentage', 50) }}">
                </div>

                {{-- Winning Criterion --}}
                <div>
                    <label for="winning_criterion" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-trophy mr-2 text-yellow-500"></i>เกณฑ์ชนะ
                    </label>
                    <select id="winning_criterion" name="winning_criterion" x-model="formData.winning_criterion" required
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                        <option value="conversion_rate" selected>Conversion Rate</option>
                        <option value="response_time">Response Time</option>
                        <option value="satisfaction">Satisfaction</option>
                        <option value="interaction_rate">Interaction Rate</option>
                    </select>
                </div>
            </div>

            {{-- Visual Percentage Display --}}
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">การกระจายตัวอย่าง:</p>
                <div class="flex gap-2 h-12 rounded-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 flex items-center justify-center text-white font-black transition-all duration-300"
                         :style="`width: ${formData.variant_a_percentage}%`">
                        <span x-text="`A: ${formData.variant_a_percentage}%`"></span>
                    </div>
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 flex items-center justify-center text-white font-black transition-all duration-300"
                         :style="`width: ${formData.variant_b_percentage}%`">
                        <span x-text="`B: ${formData.variant_b_percentage}%`"></span>
                    </div>
                </div>
            </div>

            {{-- Minimum Samples --}}
            <div>
                <label for="minimum_samples" class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-chart-bar mr-2 text-[#06C755]"></i>จำนวนตัวอย่างต่ำสุด
                </label>
                <input type="number" id="minimum_samples" name="minimum_samples" x-model.number="formData.minimum_samples" required min="10" max="10000"
                    class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all"
                    value="{{ old('minimum_samples', 100) }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>การทดสอบจะจบหลังจากได้ตัวอย่างไม่ต่ำกว่าจำนวนนี้
                </p>
            </div>
        </div>

        {{-- Section 3: Variant A --}}
        <div class="backdrop-blur-xl bg-gradient-to-br from-blue-50/80 to-cyan-50/80 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-2xl shadow-2xl p-6 border-2 border-blue-200 dark:border-blue-700">
            <h2 class="text-2xl font-black text-blue-900 dark:text-blue-100 mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                    <span class="text-white font-black">A</span>
                </div>
                <span>Variant A (Original)</span>
            </h2>

            <div class="space-y-4">
                {{-- Response Text A --}}
                <div>
                    <label for="variant_a_response_text" class="block text-sm font-bold text-blue-900 dark:text-blue-100 mb-2">
                        <i class="fas fa-comment-dots mr-2"></i>ข้อความตอบกลับ *
                    </label>
                    <textarea id="variant_a_response_text" name="variant_a[response_text]" x-model="formData.variant_a_response_text" required rows="4"
                        placeholder="ข้อความที่บอทตอบกลับเมื่อเข้าใจคำหลัก"
                        class="w-full px-4 py-3 border-2 border-blue-200 dark:border-blue-700 rounded-xl bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none">{{ old('variant_a.response_text') }}</textarea>
                    @error('variant_a.response_text')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                {{-- Response Type A --}}
                <div>
                    <label for="variant_a_response_type" class="block text-sm font-bold text-blue-900 dark:text-blue-100 mb-2">
                        <i class="fas fa-list-alt mr-2"></i>ประเภทการตอบกลับ *
                    </label>
                    <select id="variant_a_response_type" name="variant_a[response_type]" x-model="formData.variant_a_response_type" required
                        class="w-full px-4 py-3 border-2 border-blue-200 dark:border-blue-700 rounded-xl bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="text" selected>Text Message</option>
                        <option value="quick_reply">Quick Reply</option>
                        <option value="flex_message">Flex Message</option>
                    </select>
                </div>

                {{-- Description A --}}
                <div>
                    <label for="variant_a_description" class="block text-sm font-bold text-blue-900 dark:text-blue-100 mb-2">
                        <i class="fas fa-tag mr-2"></i>คำอธิบาย (ไม่บังคับ)
                    </label>
                    <input type="text" id="variant_a_description" name="variant_a[description]" x-model="formData.variant_a_description"
                        placeholder="เช่น Original greeting message"
                        class="w-full px-4 py-3 border-2 border-blue-200 dark:border-blue-700 rounded-xl bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        value="{{ old('variant_a.description') }}">
                </div>
            </div>
        </div>

        {{-- Section 4: Variant B --}}
        <div class="backdrop-blur-xl bg-gradient-to-br from-purple-50/80 to-pink-50/80 dark:from-purple-900/30 dark:to-pink-900/30 rounded-2xl shadow-2xl p-6 border-2 border-purple-200 dark:border-purple-700">
            <h2 class="text-2xl font-black text-purple-900 dark:text-purple-100 mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <span class="text-white font-black">B</span>
                </div>
                <span>Variant B (Test)</span>
            </h2>

            <div class="space-y-4">
                {{-- Response Text B --}}
                <div>
                    <label for="variant_b_response_text" class="block text-sm font-bold text-purple-900 dark:text-purple-100 mb-2">
                        <i class="fas fa-comment-dots mr-2"></i>ข้อความตอบกลับ *
                    </label>
                    <textarea id="variant_b_response_text" name="variant_b[response_text]" x-model="formData.variant_b_response_text" required rows="4"
                        placeholder="ข้อความทดสอบที่ต่างจาก variant A"
                        class="w-full px-4 py-3 border-2 border-purple-200 dark:border-purple-700 rounded-xl bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none">{{ old('variant_b.response_text') }}</textarea>
                    @error('variant_b.response_text')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                {{-- Response Type B --}}
                <div>
                    <label for="variant_b_response_type" class="block text-sm font-bold text-purple-900 dark:text-purple-100 mb-2">
                        <i class="fas fa-list-alt mr-2"></i>ประเภทการตอบกลับ *
                    </label>
                    <select id="variant_b_response_type" name="variant_b[response_type]" x-model="formData.variant_b_response_type" required
                        class="w-full px-4 py-3 border-2 border-purple-200 dark:border-purple-700 rounded-xl bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        <option value="text" selected>Text Message</option>
                        <option value="quick_reply">Quick Reply</option>
                        <option value="flex_message">Flex Message</option>
                    </select>
                </div>

                {{-- Description B --}}
                <div>
                    <label for="variant_b_description" class="block text-sm font-bold text-purple-900 dark:text-purple-100 mb-2">
                        <i class="fas fa-tag mr-2"></i>คำอธิบาย (ไม่บังคับ)
                    </label>
                    <input type="text" id="variant_b_description" name="variant_b[description]" x-model="formData.variant_b_description"
                        placeholder="เช่น Friendlier greeting with emoji"
                        class="w-full px-4 py-3 border-2 border-purple-200 dark:border-purple-700 rounded-xl bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                        value="{{ old('variant_b.description') }}">
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="flex gap-4">
            <button type="submit"
                    :disabled="!isFormValid"
                    :class="isFormValid ? 'opacity-100 cursor-pointer hover:shadow-2xl hover:scale-105' : 'opacity-50 cursor-not-allowed'"
                    class="flex-1 px-8 py-4 bg-gradient-to-r from-[#06C755] to-emerald-600 text-white rounded-xl font-black text-lg shadow-lg transition-all flex items-center justify-center gap-3">
                <i class="fas fa-check-circle text-2xl"></i>
                <span>สร้าง A/B Test</span>
            </button>
            <a href="{{ route('admin.line-bot.keywords.ab-tests.index') }}"
                class="px-8 py-4 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-xl font-black text-lg shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-3">
                <i class="fas fa-times-circle text-2xl"></i>
                <span>ยกเลิก</span>
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
/**
 * A/B Test Creator Component
 *
 * จัดการฟอร์มสร้าง A/B Test พร้อม validation
 */
function abTestCreator() {
    return {
        // Form Data
        formData: {
            keyword_id: '',
            test_name: '',
            description: '',
            variant_a_percentage: 50,
            variant_b_percentage: 50,
            winning_criterion: 'conversion_rate',
            minimum_samples: 100,
            variant_a_response_text: '',
            variant_a_response_type: 'text',
            variant_a_description: '',
            variant_b_response_text: '',
            variant_b_response_type: 'text',
            variant_b_description: ''
        },

        // Computed Properties
        get isFormValid() {
            return this.formData.keyword_id &&
                   this.formData.test_name &&
                   this.formData.variant_a_response_text &&
                   this.formData.variant_b_response_text &&
                   (this.formData.variant_a_percentage + this.formData.variant_b_percentage === 100);
        },

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('A/B Test Creator initialized');
        },

        /**
         * Validate form before submit
         */
        validateForm(e) {
            if (!this.isFormValid) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }

            // Check percentages
            const total = this.formData.variant_a_percentage + this.formData.variant_b_percentage;
            if (total !== 100) {
                e.preventDefault();
                alert(`Variant A + B ต้องรวมกันเท่ากับ 100% (ปัจจุบัน: ${total}%)`);
                return false;
            }

            return true;
        }
    };
}
</script>
@endpush
@endsection
