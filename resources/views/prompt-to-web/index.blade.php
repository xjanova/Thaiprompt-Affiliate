@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-magic mr-3 text-purple-600 dark:text-purple-400"></i>
                Prompt to Web
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300">
                สร้างหน้าเว็บสวยงามด้วย AI ในไม่กี่วินาที
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                แค่บอกว่าต้องการหน้าเว็บแบบไหน AI จะช่วยสร้างให้คุณทันที
            </p>
        </div>

        <!-- Main Generator Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden mb-8" x-data="promptToWebGenerator()">
            <div class="p-8">
                <!-- Prompt Input -->
                <div class="mb-6">
                    <label for="prompt" class="block text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-pencil-alt mr-2 text-indigo-600"></i>
                        อธิบายหน้าเว็บที่ต้องการ
                    </label>
                    <textarea
                        id="prompt"
                        x-model="prompt"
                        rows="5"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white resize-none"
                        placeholder="ตัวอย่าง: สร้างหน้า landing page สำหรับร้านกาแฟ มีสีน้ำตาล มีรูปภาพกาแฟ มีปุ่มสั่งซื้อ และแสดงเมนูยอดนิยม"
                        :disabled="generating"
                    ></textarea>
                    <div class="flex justify-between items-center mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="prompt.length"></span> / 2000 ตัวอักษร
                        </p>
                    </div>
                </div>

                <!-- Advanced Options (Collapsible) -->
                <div class="mb-6" x-data="{ showAdvanced: false }">
                    <button
                        @click="showAdvanced = !showAdvanced"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium"
                    >
                        <i class="fas fa-cog mr-1"></i>
                        <span x-text="showAdvanced ? 'ซ่อนตัวเลือกขั้นสูง' : 'แสดงตัวเลือกขั้นสูง'"></span>
                    </button>

                    <div x-show="showAdvanced" x-transition class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- AI Provider Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    AI Provider
                                </label>
                                <select
                                    x-model="provider"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-800 dark:text-white"
                                    :disabled="generating"
                                >
                                    <option value="">Auto (แนะนำ)</option>
                                    <option value="claude">Claude (Anthropic)</option>
                                    <option value="openai">OpenAI (GPT)</option>
                                    <option value="gemini">Google Gemini</option>
                                    <option value="deepseek">DeepSeek</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Button -->
                <div class="flex gap-4">
                    <button
                        @click="generatePage()"
                        :disabled="!prompt || prompt.length < 10 || generating"
                        class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold py-4 px-6 rounded-lg shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105"
                    >
                        <span x-show="!generating">
                            <i class="fas fa-wand-magic-sparkles mr-2"></i>
                            สร้างหน้าเว็บด้วย AI
                        </span>
                        <span x-show="generating">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            กำลังสร้าง...
                        </span>
                    </button>
                </div>

                <!-- Error Message -->
                <div x-show="error" x-transition class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-700 dark:text-red-400" x-text="error"></p>
                </div>

                <!-- Success Message & Preview -->
                <div x-show="generatedPage" x-transition class="mt-6">
                    <!-- Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center">
                            <i class="fas fa-check-circle text-2xl text-green-600 dark:text-green-400 mb-2"></i>
                            <p class="text-sm text-gray-600 dark:text-gray-400">สถานะ</p>
                            <p class="font-bold text-green-700 dark:text-green-300">สำเร็จ</p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg text-center">
                            <i class="fas fa-coins text-2xl text-blue-600 dark:text-blue-400 mb-2"></i>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Tokens</p>
                            <p class="font-bold text-blue-700 dark:text-blue-300" x-text="generatedPage?.tokens_used || 0"></p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg text-center">
                            <i class="fas fa-dollar-sign text-2xl text-purple-600 dark:text-purple-400 mb-2"></i>
                            <p class="text-sm text-gray-600 dark:text-gray-400">ค่าใช้จ่าย</p>
                            <p class="font-bold text-purple-700 dark:text-purple-300" x-text="'$' + (generatedPage?.cost || 0).toFixed(4)"></p>
                        </div>
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg text-center">
                            <i class="fas fa-clock text-2xl text-indigo-600 dark:text-indigo-400 mb-2"></i>
                            <p class="text-sm text-gray-600 dark:text-gray-400">เวลา</p>
                            <p class="font-bold text-indigo-700 dark:text-indigo-300" x-text="generationTime + 's'"></p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <a
                            :href="generatedPage?.preview_url"
                            target="_blank"
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow transition-colors"
                        >
                            <i class="fas fa-external-link-alt mr-2"></i>
                            เปิดดูหน้าเว็บ
                        </a>
                        <button
                            @click="copyHtml()"
                            class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition-colors"
                        >
                            <i class="fas fa-copy mr-2"></i>
                            คัดลอก HTML
                        </button>
                        <button
                            @click="downloadHtml()"
                            class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow transition-colors"
                        >
                            <i class="fas fa-download mr-2"></i>
                            ดาวน์โหลด
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Pages -->
        @if(isset($recentPages) && $recentPages->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-history mr-2 text-indigo-600"></i>
                หน้าเว็บที่สร้างล่าสุด
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($recentPages as $page)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-lg transition-shadow">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">{{ $page->title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ $page->description }}</p>
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                        <span><i class="fas fa-eye mr-1"></i> {{ $page->views }} views</span>
                        <span><i class="fas fa-clock mr-1"></i> {{ $page->created_at->diffForHumans() }}</span>
                    </div>
                    <a
                        href="{{ route('prompt-to-web.preview', $page->slug) }}"
                        target="_blank"
                        class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium"
                    >
                        ดูหน้าเว็บ <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function promptToWebGenerator() {
    return {
        prompt: '',
        provider: '',
        model: '',
        generating: false,
        error: null,
        generatedPage: null,
        generationTime: 0,
        startTime: null,

        async generatePage() {
            if (!this.prompt || this.prompt.length < 10) {
                this.error = 'กรุณาใส่คำอธิบายอย่างน้อย 10 ตัวอักษร';
                return;
            }

            this.generating = true;
            this.error = null;
            this.generatedPage = null;
            this.startTime = Date.now();

            try {
                const response = await fetch('{{ route("prompt-to-web.generate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        prompt: this.prompt,
                        provider: this.provider || null,
                        model: this.model || null
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'เกิดข้อผิดพลาด');
                }

                this.generatedPage = data.data;
                this.generationTime = ((Date.now() - this.startTime) / 1000).toFixed(1);

            } catch (error) {
                this.error = error.message;
            } finally {
                this.generating = false;
            }
        },

        copyHtml() {
            if (!this.generatedPage?.full_content) return;

            navigator.clipboard.writeText(this.generatedPage.full_content).then(() => {
                alert('คัดลอก HTML สำเร็จ!');
            });
        },

        downloadHtml() {
            if (!this.generatedPage?.full_content) return;

            const blob = new Blob([this.generatedPage.full_content], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = (this.generatedPage.title || 'generated-page') + '.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    };
}
</script>
@endpush
@endsection
