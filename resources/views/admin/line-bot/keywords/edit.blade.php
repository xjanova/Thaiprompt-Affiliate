@extends('layouts.admin-v3')

@section('title', 'แก้ไข Keyword')

@section('content')
<div class="container-fluid px-4 py-6 max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('admin.line-bot.keywords.index') }}" class="text-purple-600 hover:text-purple-700 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>กลับไปยังรายการ Keywords
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">แก้ไข Keyword: {{ $keyword->keyword }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">ปรับปรุงข้อมูล Keyword นี้</p>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.line-bot.keywords.update', $keyword) }}" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Basic Information --}}
        <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-info-circle mr-2 text-purple-600"></i>ข้อมูลพื้นฐาน
            </h2>

            <div class="space-y-6">
                {{-- Keyword Name --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        Keyword Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" name="keyword" placeholder="เช่น refund, shipping, payment"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 @error('keyword') border-red-500 @enderror"
                        value="{{ old('keyword', $keyword->keyword) }}" required>
                    @error('keyword')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="description" placeholder="คำอธิบายสำหรับ Keyword นี้"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                        rows="2">{{ old('description', $keyword->description) }}</textarea>
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        หมวดหมู่ <span class="text-red-600">*</span>
                    </label>
                    <select name="category" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $keyword->category) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Priority --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                        Priority (1-100) <span class="text-red-600">*</span>
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="range" name="priority" min="1" max="100" value="{{ old('priority', $keyword->priority) }}"
                            class="flex-1 h-2 bg-gray-300 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer"
                            oninput="document.getElementById('priorityValue').textContent = this.value">
                        <span class="text-lg font-bold text-gray-900 dark:text-white min-w-[50px] text-right" id="priorityValue">{{ old('priority', $keyword->priority) }}</span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">ตัวเลขสูงกว่า = ตรวจสอบก่อน</p>
                </div>

                {{-- Active Status --}}
                <div>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $keyword->is_active) ? 'checked' : '' }} class="w-5 h-5">
                        <span class="text-gray-900 dark:text-white font-semibold">เปิดใช้งาน Keyword นี้</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Trigger Words --}}
        <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                <i class="fas fa-tag mr-2 text-pink-600"></i>Trigger Words
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">คำที่จะสร้างความตรงกับ Keyword นี้</p>

            <textarea name="trigger_words" placeholder="refund, คืนเงิน, return, การคืนเงิน"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                rows="4" required>{{ old('trigger_words', $triggerWordsText) }}</textarea>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">🔍 ใช้ทั้งภาษาอังกฤษและไทย</p>
        </div>

        {{-- Response Type Selection --}}
        <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-reply mr-2 text-blue-600"></i>ประเภทคำตอบ
            </h2>

            <div class="space-y-4 mb-6">
                <label class="flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition @if(old('response_type', $keyword->response_type) === 'text') border-purple-600 bg-purple-50 dark:bg-purple-500/10 @endif">
                    <input type="radio" name="response_type" value="text" class="w-4 h-4" onchange="switchResponseType()" @checked(old('response_type', $keyword->response_type) === 'text')>
                    <span class="ml-3">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">📝 ข้อความธรรมชาติ</span>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">ตอบด้วยข้อความธรรมชาติ</p>
                    </span>
                </label>

                <label class="flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition @if(old('response_type', $keyword->response_type) === 'quick_reply') border-purple-600 bg-purple-50 dark:bg-purple-500/10 @endif">
                    <input type="radio" name="response_type" value="quick_reply" class="w-4 h-4" onchange="switchResponseType()" @checked(old('response_type', $keyword->response_type) === 'quick_reply')>
                    <span class="ml-3">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">⚡ Quick Reply</span>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">ตอบพร้อมปุ่มตัวเลือก</p>
                    </span>
                </label>

                <label class="flex items-center p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition @if(old('response_type', $keyword->response_type) === 'flex_message') border-purple-600 bg-purple-50 dark:bg-purple-500/10 @endif">
                    <input type="radio" name="response_type" value="flex_message" class="w-4 h-4" onchange="switchResponseType()" @checked(old('response_type', $keyword->response_type) === 'flex_message')>
                    <span class="ml-3">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">🎨 Flex Message</span>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">ตอบด้วยการออกแบบ Rich Message</p>
                    </span>
                </label>
            </div>
        </div>

        {{-- Response Content (Dynamic) --}}
        <div id="responseContent" class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            {{-- Text/Quick Reply Response --}}
            <div id="textResponseSection" class="space-y-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-quote-left mr-2 text-blue-600"></i>ข้อความตอบกลับ
                </h2>

                <textarea name="response_text" placeholder="ตอบได้อย่างนี้..."
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                    rows="5">{{ old('response_text', $keyword->response_text) }}</textarea>
                <p class="text-gray-500 dark:text-gray-400 text-sm">💡 ใช้ emojis และการจัดรูปแบบ</p>
            </div>

            {{-- Flex Message Response (Hidden) --}}
            <div id="flexResponseSection" class="hidden space-y-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-code mr-2 text-blue-600"></i>Flex Message JSON
                </h2>

                <textarea name="response_flex_json" placeholder='{"type":"bubble","body":{"type":"box","layout":"vertical","contents":[]}}'
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                    rows="10">{{ old('response_flex_json', $keyword->response_flex_json ? json_encode($keyword->response_flex_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
            </div>
        </div>

        {{-- Additional Notes --}}
        <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-sticky-note mr-2 text-yellow-600"></i>หมายเหตุเพิ่มเติม
            </h2>

            <textarea name="notes" placeholder="หมายเหตุสำหรับทีมหรือบันทึกการปรับแต่ง"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                rows="3">{{ old('notes', $keyword->notes) }}</textarea>
        </div>

        {{-- Metadata --}}
        <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">สร้างเมื่อ</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $keyword->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">แก้ไขล่าสุด</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $keyword->updated_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">ID</p>
                    <p class="font-semibold text-gray-900 dark:text-white">#{{ $keyword->id }}</p>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex gap-4">
            <button type="submit" class="flex-1 px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-lg transition shadow-lg">
                <i class="fas fa-save mr-2"></i>บันทึกการแก้ไข
            </button>
            <a href="{{ route('admin.line-bot.keywords.index') }}" class="flex-1 px-6 py-4 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white font-bold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-center">
                <i class="fas fa-times mr-2"></i>ยกเลิก
            </a>
        </div>
    </form>
</div>

<script>
function switchResponseType() {
    const type = document.querySelector('input[name="response_type"]:checked').value;
    const textSection = document.getElementById('textResponseSection');
    const flexSection = document.getElementById('flexResponseSection');

    if (type === 'flex_message') {
        textSection.classList.add('hidden');
        flexSection.classList.remove('hidden');
    } else {
        textSection.classList.remove('hidden');
        flexSection.classList.add('hidden');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', switchResponseType);
</script>
@endsection
