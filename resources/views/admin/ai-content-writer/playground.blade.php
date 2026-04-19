@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'AI Playground')

@section('content')
<div class="space-y-6" x-data="playground()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-magic text-purple-500"></i>
                AI Playground
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ทดสอบสร้าง Content ด้วย AI แบบ Real-time</p>
        </div>
        <a href="{{ route('admin.platform-revenue.ai-content-writer.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left mr-2"></i> กลับ
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Input Section --}}
        <div class="space-y-4">
            {{-- Template Selection --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือกเทมเพลต (ถ้ามี)</label>
                <select x-model="selectedTemplate" @change="loadTemplate"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">-- เขียนเอง --</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" data-system="{{ $template->system_prompt }}" data-user="{{ $template->user_prompt_template }}">{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- System Prompt --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">System Prompt</label>
                <textarea x-model="systemPrompt" rows="3"
                          class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
                          placeholder="กำหนดบทบาทของ AI..."></textarea>
            </div>

            {{-- User Prompt --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">User Prompt *</label>
                <textarea x-model="userPrompt" rows="6" required
                          class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
                          placeholder="เขียนคำสั่งให้ AI..."></textarea>
            </div>

            {{-- Settings --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
                        <select x-model="provider"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            @foreach($providers as $key => $info)
                                @if($info['configured'])
                                    <option value="{{ $key }}">{{ $info['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                        <select x-model="model"
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                            <template x-for="(label, key) in models[provider] || {}" :key="key">
                                <option :value="key" x-text="label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Temperature: <span x-text="temperature"></span></label>
                        <input type="range" x-model="temperature" min="0" max="2" step="0.1" class="w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Tokens</label>
                        <input type="number" x-model="maxTokens" min="100" max="8000"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>
            </div>

            {{-- Generate Button --}}
            <button @click="generate"
                    :disabled="generating || !userPrompt"
                    class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!generating">
                    <i class="fas fa-magic mr-2"></i> สร้าง Content
                </span>
                <span x-show="generating" x-cloak>
                    <i class="fas fa-spinner fa-spin mr-2"></i> กำลังสร้าง...
                </span>
            </button>
        </div>

        {{-- Output Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col min-h-[600px]">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">ผลลัพธ์</h3>
                <div x-show="output" class="flex items-center gap-2">
                    <button @click="copyOutput" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <i class="fas fa-copy mr-1"></i> คัดลอก
                    </button>
                </div>
            </div>

            <div class="flex-1 p-4 overflow-auto">
                <div x-show="!output && !generating" class="h-full flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <i class="fas fa-feather-alt text-4xl mb-3"></i>
                        <p>กรอกข้อมูลและกด "สร้าง Content"</p>
                    </div>
                </div>

                <div x-show="generating" x-cloak class="h-full flex items-center justify-center text-gray-400">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin text-4xl mb-3 text-purple-500"></i>
                        <p>กำลังสร้าง Content...</p>
                    </div>
                </div>

                <div x-show="output" x-cloak>
                    <div class="prose dark:prose-invert max-w-none">
                        <pre class="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200" x-text="output"></pre>
                    </div>

                    {{-- Usage Stats --}}
                    <div x-show="usage" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div class="text-center">
                                <p class="text-gray-500 dark:text-gray-400">Tokens</p>
                                <p class="font-semibold text-gray-900 dark:text-white" x-text="usage?.total_tokens || 0"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-500 dark:text-gray-400">Cost</p>
                                <p class="font-semibold text-gray-900 dark:text-white">$<span x-text="(usage?.cost || 0).toFixed(4)"></span></p>
                            </div>
                            <div class="text-center">
                                <p class="text-gray-500 dark:text-gray-400">Time</p>
                                <p class="font-semibold text-gray-900 dark:text-white"><span x-text="((responseTime || 0) / 1000).toFixed(1)"></span>s</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Error --}}
                <div x-show="error" x-cloak class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-4 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span x-text="error"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function playground() {
    return {
        selectedTemplate: '',
        systemPrompt: '',
        userPrompt: '',
        provider: 'openai',
        model: 'gpt-4o-mini',
        temperature: 0.7,
        maxTokens: 2000,
        models: @json($models),

        generating: false,
        output: '',
        usage: null,
        responseTime: 0,
        error: '',

        loadTemplate() {
            const select = document.querySelector('select[x-model="selectedTemplate"]');
            const option = select.options[select.selectedIndex];

            if (option && option.value) {
                this.systemPrompt = option.dataset.system || '';
                this.userPrompt = option.dataset.user || '';
            }
        },

        async generate() {
            if (!this.userPrompt) return;

            this.generating = true;
            this.output = '';
            this.error = '';
            this.usage = null;

            try {
                const response = await fetch('{{ route("admin.platform-revenue.ai-content-writer.playground.generate") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        system_prompt: this.systemPrompt,
                        user_prompt: this.userPrompt,
                        provider: this.provider,
                        model: this.model,
                        temperature: parseFloat(this.temperature),
                        max_tokens: parseInt(this.maxTokens),
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.output = result.content;
                    this.usage = result.usage;
                    this.responseTime = result.response_time_ms;
                } else {
                    this.error = result.error || 'เกิดข้อผิดพลาด';
                }
            } catch (err) {
                this.error = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
            }

            this.generating = false;
        },

        copyOutput() {
            navigator.clipboard.writeText(this.output);
            alert('คัดลอกแล้ว!');
        }
    }
}
</script>
@endpush
@endsection
