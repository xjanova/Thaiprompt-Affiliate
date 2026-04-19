@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="campaignForm()">
    {{-- Breadcrumb --}}
    <div class="mb-4">
        <a href="{{ route('admin.fortune.marketing.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
            &larr; กลับไปรายการแคมเปญ
        </a>
    </div>

    {{-- Header --}}
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
        {{ $campaign ? '✏️' : '📣' }} {{ $pageTitle }}
    </h1>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ $campaign ? route('admin.fortune.marketing.update', $campaign) : route('admin.fortune.marketing.store') }}"
          method="POST">
        @csrf
        @if($campaign) @method('PUT') @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- คอลัมน์ซ้าย: ข้อมูลแคมเปญ --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- ข้อมูลพื้นฐาน --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📋 ข้อมูลแคมเปญ</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อแคมเปญ *</label>
                            <input type="text" name="name" required
                                   value="{{ old('name', $campaign?->name) }}"
                                   placeholder="เช่น โปรโมชั่นดูดวงปีใหม่"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หัวข้อ/เรื่อง (สำหรับ AI สร้างข้อความ)</label>
                            <input type="text" name="topic" x-model="topic"
                                   value="{{ old('topic', $campaign?->topic) }}"
                                   placeholder="เช่น ดวงราศีประจำเดือน, วันวาเลนไทน์, ตรุษจีน"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                            <textarea name="description" rows="2"
                                      placeholder="คำอธิบายเพิ่มเติม (แอดมินเห็น ไม่ได้ส่งให้ผู้ใช้)"
                                      class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">{{ old('description', $campaign?->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ข้อความ --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">💬 ข้อความที่จะส่ง</h2>

                    <div class="mb-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="use_ai_generate" value="1" x-model="useAI"
                                   {{ old('use_ai_generate', $campaign?->use_ai_generate ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-purple-600 focus:ring-purple-500 w-5 h-5">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                🤖 ให้ AI สร้างข้อความอัตโนมัติ (จันทราจะเขียนให้)
                            </span>
                        </label>
                    </div>

                    {{-- AI Prompt --}}
                    <div x-show="useAI" x-cloak class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Prompt สำหรับ AI (ไม่ใส่ = ใช้ค่าเริ่มต้น)
                            </label>
                            <textarea name="ai_prompt" rows="4"
                                      placeholder="ปล่อยว่าง = AI จะใช้หัวข้อด้านบนสร้างข้อความการตลาดแบบแม่หมอจันทราอัตโนมัติ&#10;&#10;หรือใส่ prompt เองเพื่อกำหนดรูปแบบข้อความที่ต้องการ..."
                                      class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-purple-500">{{ old('ai_prompt', $campaign?->ai_prompt) }}</textarea>
                        </div>

                        {{-- ปุ่มทดสอบ AI --}}
                        <button type="button" @click="previewAI()"
                                :disabled="generating"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white rounded-lg transition flex items-center">
                            <span x-show="!generating" class="mr-2">🤖</span>
                            <span x-show="generating" class="mr-2 animate-spin">⏳</span>
                            <span x-text="generating ? 'AI กำลังสร้าง...' : 'ทดสอบ AI สร้างข้อความ'"></span>
                        </button>

                        {{-- ตัวอย่างจาก AI --}}
                        <div x-show="aiPreview" x-cloak
                             class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="text-xs font-medium text-purple-600 dark:text-purple-400 mb-2">ตัวอย่างจาก AI:</div>
                            <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="aiPreview"></div>
                        </div>

                        @if($campaign?->ai_generated_message)
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="text-xs font-medium text-green-600 dark:text-green-400 mb-2">ข้อความ AI ที่สร้างไว้:</div>
                                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $campaign->ai_generated_message }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- ข้อความกำหนดเอง --}}
                    <div x-show="!useAI" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความที่ต้องการส่ง *</label>
                        <textarea name="message_template" rows="6"
                                  placeholder="พิมพ์ข้อความที่ต้องการส่งถึงผู้ใช้..."
                                  class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">{{ old('message_template', $campaign?->message_template) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- คอลัมน์ขวา: ตั้งค่า --}}
            <div class="space-y-6">
                {{-- เป้าหมาย --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🎯 เป้าหมาย</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กลุ่มเป้าหมาย</label>
                            <select name="target_audience"
                                    class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600">
                                <option value="all" {{ old('target_audience', $campaign?->target_audience) === 'all' ? 'selected' : '' }}>ทุกคนที่เคยดูดวง</option>
                                <option value="paid" {{ old('target_audience', $campaign?->target_audience) === 'paid' ? 'selected' : '' }}>ลูกค้าจ่ายเงิน</option>
                                <option value="recent" {{ old('target_audience', $campaign?->target_audience) === 'recent' ? 'selected' : '' }}>ดูดวงภายใน 7 วัน</option>
                                <option value="new" {{ old('target_audience', $campaign?->target_audience) === 'new' ? 'selected' : '' }}>ผู้ใช้ใหม่ (ดู 1 ครั้ง)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Platform</label>
                            <select name="target_platform"
                                    class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600">
                                <option value="all" {{ old('target_platform', $campaign?->target_platform) === 'all' ? 'selected' : '' }}>ทุก Platform</option>
                                <option value="facebook" {{ old('target_platform', $campaign?->target_platform) === 'facebook' ? 'selected' : '' }}>Facebook</option>
                                <option value="line" {{ old('target_platform', $campaign?->target_platform) === 'line' ? 'selected' : '' }}>LINE</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนสูงสุดที่ส่ง</label>
                            <input type="number" name="target_limit" min="1" max="10000"
                                   value="{{ old('target_limit', $campaign?->target_limit) }}"
                                   placeholder="ไม่จำกัด"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ปล่อยว่าง = ส่งทุกคนในกลุ่ม</p>
                        </div>
                    </div>
                </div>

                {{-- ตั้งเวลา --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⏰ ตั้งเวลาส่ง</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                            <select name="schedule_type" x-model="scheduleType"
                                    class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600">
                                <option value="once" {{ old('schedule_type', $campaign?->schedule_type) === 'once' ? 'selected' : '' }}>ส่งครั้งเดียว</option>
                                <option value="daily" {{ old('schedule_type', $campaign?->schedule_type) === 'daily' ? 'selected' : '' }}>ส่งทุกวัน (AI สร้างใหม่ทุกวัน)</option>
                                <option value="weekly" {{ old('schedule_type', $campaign?->schedule_type) === 'weekly' ? 'selected' : '' }}>ส่งทุกสัปดาห์ (AI สร้างใหม่ทุกสัปดาห์)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <span x-text="scheduleType === 'once' ? 'วัน-เวลาที่ส่ง' : 'เริ่มส่งตั้งแต่'"></span>
                            </label>
                            <input type="datetime-local" name="scheduled_at"
                                   value="{{ old('scheduled_at', $campaign?->scheduled_at?->format('Y-m-d\TH:i')) }}"
                                   class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600">
                        </div>

                        <div x-show="scheduleType !== 'once'" x-cloak
                             class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
                            <p x-show="scheduleType === 'daily'">🔄 จะส่งทุกวันตามเวลาที่ตั้ง และ AI จะสร้างข้อความใหม่ทุกครั้ง (อ้างอิงดวงประจำวัน)</p>
                            <p x-show="scheduleType === 'weekly'">🔄 จะส่งทุกสัปดาห์ตามเวลาที่ตั้ง และ AI จะสร้างข้อความใหม่ทุกครั้ง</p>
                        </div>
                    </div>
                </div>

                {{-- ปุ่ม --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <div class="space-y-3">
                        <button type="submit"
                                class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                            {{ $campaign ? 'อัปเดตแคมเปญ' : 'สร้างแคมเปญ' }}
                        </button>
                        <a href="{{ route('admin.fortune.marketing.index') }}"
                           class="block w-full px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition text-center">
                            ยกเลิก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function campaignForm() {
    return {
        useAI: {{ old('use_ai_generate', $campaign?->use_ai_generate ?? true) ? 'true' : 'false' }},
        topic: '{{ old('topic', $campaign?->topic ?? '') }}',
        scheduleType: '{{ old('schedule_type', $campaign?->schedule_type ?? 'once') }}',
        aiPreview: '',
        generating: false,

        async previewAI() {
            if (this.generating) return;
            this.generating = true;
            this.aiPreview = '';

            try {
                const response = await fetch('{{ route('admin.fortune.marketing.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        topic: this.topic || 'ดูดวงฟรี',
                        ai_prompt: document.querySelector('[name="ai_prompt"]')?.value || '',
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.aiPreview = data.message;
                } else {
                    this.aiPreview = '❌ ' + (data.error || 'เกิดข้อผิดพลาด');
                }
            } catch (e) {
                this.aiPreview = '❌ ไม่สามารถเชื่อมต่อ AI ได้: ' + e.message;
            } finally {
                this.generating = false;
            }
        }
    }
}
</script>
@endsection
