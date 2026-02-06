@extends('layouts.admin')

@section('title', $pageTitle ?? 'สร้างเทมเพลตใหม่')

@section('content')
<div class="space-y-6" x-data="templateForm()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">สร้างเทมเพลตใหม่</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">กำหนดรูปแบบสำหรับสร้าง Content</p>
        </div>
        <a href="{{ route('admin.platform-revenue.ai-content-writer.templates') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left mr-2"></i> กลับ
        </a>
    </div>

    <form @submit.prevent="saveTemplate">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Info --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อเทมเพลต *</label>
                            <input type="text" x-model="form.name" required
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                   placeholder="เช่น บทความ SEO, Script YouTube">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                            <textarea x-model="form.description" rows="2"
                                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                      placeholder="อธิบายว่าเทมเพลตนี้ใช้ทำอะไร"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท Content *</label>
                                <select x-model="form.content_type" required
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                    @foreach($contentTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่</label>
                                <select x-model="form.category"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Prompts --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Prompts</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                System Prompt
                                <span class="text-xs text-gray-400">(กำหนดบทบาทของ AI)</span>
                            </label>
                            <textarea x-model="form.system_prompt" rows="3"
                                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                                      placeholder="คุณเป็นนักเขียน Content ผู้เชี่ยวชาญ..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                User Prompt Template *
                                <span class="text-xs text-gray-400">(ใช้ @{{variable}} สำหรับตัวแปร)</span>
                            </label>
                            <textarea x-model="form.user_prompt_template" rows="6" required
                                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                                      placeholder="เขียนบทความเกี่ยวกับ @{{topic}} โดยมีความยาว @{{length}} คำ..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- AI Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">การตั้งค่า AI</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">AI Provider *</label>
                            <select x-model="form.default_provider" required
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                @foreach($providers as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                            <select x-model="form.default_model"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                <option value="">-- ใช้ค่าเริ่มต้น --</option>
                                <template x-for="(label, key) in models[form.default_provider] || {}" :key="key">
                                    <option :value="key" x-text="label"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Temperature: <span x-text="form.temperature"></span>
                            </label>
                            <input type="range" x-model="form.temperature" min="0" max="2" step="0.1"
                                   class="w-full">
                            <div class="flex justify-between text-xs text-gray-400">
                                <span>แม่นยำ</span>
                                <span>สร้างสรรค์</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Tokens</label>
                            <input type="number" x-model="form.max_tokens" min="100" max="8000"
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tone</label>
                            <select x-model="form.tone"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                <option value="">-- ไม่ระบุ --</option>
                                @foreach($tones as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Status --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">สถานะ</h3>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" x-model="form.is_featured" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">แนะนำ</span>
                        </label>
                    </div>
                </div>

                {{-- Save Button --}}
                <button type="submit"
                        :disabled="saving"
                        class="w-full px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl transition disabled:opacity-50">
                    <span x-show="!saving">
                        <i class="fas fa-save mr-2"></i> บันทึกเทมเพลต
                    </span>
                    <span x-show="saving" x-cloak>
                        <i class="fas fa-spinner fa-spin mr-2"></i> กำลังบันทึก...
                    </span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function templateForm() {
    return {
        form: {
            name: '',
            description: '',
            content_type: 'general',
            category: '',
            system_prompt: '',
            user_prompt_template: '',
            default_provider: 'openai',
            default_model: '',
            temperature: 0.7,
            max_tokens: 2000,
            tone: '',
            is_active: true,
            is_featured: false,
        },
        models: @json($models),
        saving: false,

        async saveTemplate() {
            this.saving = true;

            try {
                const response = await fetch('{{ route("admin.platform-revenue.ai-content-writer.templates.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form)
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect || '{{ route("admin.platform-revenue.ai-content-writer.templates") }}';
                } else {
                    alert(result.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการบันทึก');
            }

            this.saving = false;
        }
    }
}
</script>
@endpush
@endsection
